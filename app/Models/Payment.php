<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id',
        'ebook_id',
        'payment_gateway',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'payment_data',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'payment_data' => 'array',
    ];

    /**
     * ZenithaLMS: Payment Status Constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    /**
     * ZenithaLMS: Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function ebook()
    {
        return $this->belongsTo(Ebook::class);
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway', 'gateway_code');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    public function scopeByGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * ZenithaLMS: Methods
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRefunded()
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getItemName()
    {
        if ($this->course) {
            return $this->course->title;
        }
        
        if ($this->ebook) {
            return $this->ebook->title;
        }
        
        return 'Unknown Item';
    }

    public function getItemType()
    {
        if ($this->course) {
            return 'course';
        }
        
        if ($this->ebook) {
            return 'ebook';
        }
        
        return 'unknown';
    }

    public function markAsCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        $this->paid_at = now();
        $this->save();
        
        // ZenithaLMS: Grant access to purchased item
        $this->grantAccess();
    }

    public function markAsFailed($reason = null)
    {
        $this->status = self::STATUS_FAILED;
        if ($reason) {
            $this->payment_data['failure_reason'] = $reason;
        }
        $this->save();
    }

    public function markAsRefunded($refundAmount = null)
    {
        $this->status = self::STATUS_REFUNDED;
        if ($refundAmount) {
            $this->payment_data['refund_amount'] = $refundAmount;
        }
        $this->save();
        
        // ZenithaLMS: Revoke access to refunded item
        $this->revokeAccess();
    }

    /**
     * ZenithaLMS: Access Management
     */
    private function grantAccess()
    {
        if ($this->course) {
            // Create enrollment for course
            Enrollment::firstOrCreate(
                [
                    'user_id' => $this->user_id,
                    'course_id' => $this->course_id,
                ],
                [
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'progress_percentage' => 0,
                ]
            );
        }
        
        if ($this->ebook) {
            // Create access record for ebook
            EbookAccess::firstOrCreate(
                [
                    'user_id' => $this->user_id,
                    'ebook_id' => $this->ebook_id,
                ],
                [
                    'purchase_type' => 'one_time',
                    'access_until' => null, // Lifetime access
                    'created_at' => now(),
                ]
            );
        }
    }

    private function revokeAccess()
    {
        if ($this->course) {
            // Cancel enrollment for course
            Enrollment::where('user_id', $this->user_id)
                ->where('course_id', $this->course_id)
                ->update(['status' => 'cancelled']);
        }
        
        if ($this->ebook) {
            // Revoke access to ebook
            EbookAccess::where('user_id', $this->user_id)
                ->where('ebook_id', $this->ebook_id)
                ->update(['access_until' => now()]);
        }
    }

    /**
     * ZenithaLMS: Analytics Methods
     */
    public function getPaymentAnalytics()
    {
        return [
            'gateway' => $this->payment_gateway,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'paid_at' => $this->paid_at,
            'processing_time' => $this->paid_at ? $this->paid_at->diffInMinutes($this->created_at) : null,
        ];
    }

    public static function getRevenueStats($startDate = null, $endDate = null)
    {
        $query = self::completed();
        
        if ($startDate) {
            $query->where('paid_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('paid_at', '<=', $endDate);
        }
        
        return [
            'total_revenue' => $query->sum('amount'),
            'total_transactions' => $query->count(),
            'average_transaction' => $query->avg('amount'),
            'by_gateway' => $query->groupBy('payment_gateway')
                ->selectRaw('payment_gateway, SUM(amount) as total, COUNT(*) as count')
                ->get(),
        ];
    }
}
