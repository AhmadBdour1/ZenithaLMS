<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payout extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type', // 'vendor_earnings', 'affiliate_commission', 'instructor_revenue', 'marketplace_sales', 'subscription_earnings'
        'amount',
        'currency',
        'status', // 'pending', 'processing', 'completed', 'failed', 'cancelled', 'reversed'
        'payment_method_id',
        'payment_method_type', // 'bank_account', 'paypal', 'wise', 'crypto', 'check', 'wire'
        'recipient_info',
        'bank_account_details',
        'paypal_email',
        'crypto_address',
        'crypto_currency',
        'check_address',
        'wire_details',
        'processing_fee',
        'net_amount',
        'tax_withheld',
        'tax_amount',
        'exchange_rate',
        'original_currency',
        'original_amount',
        'reference_number',
        'transaction_id',
        'batch_id',
        'requested_at',
        'processed_at',
        'completed_at',
        'estimated_completion_date',
        'notes',
        'internal_notes',
        'failure_reason',
        'retry_count',
        'next_retry_at',
        'auto_retry_enabled',
        'verification_required',
        'verification_status', // 'pending', 'verified', 'rejected'
        'verification_documents',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'original_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_completion_date' => 'datetime',
        'next_retry_at' => 'datetime',
        'auto_retry_enabled' => 'boolean',
        'verification_required' => 'boolean',
        'verification_documents' => 'array',
        'metadata' => 'array',
        'recipient_info' => 'array',
        'bank_account_details' => 'array',
        'wire_details' => 'array',
        'check_address' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function batch()
    {
        return $this->belongsTo(PayoutBatch::class, 'batch_id');
    }

    public function verificationDocuments()
    {
        return $this->hasMany(PayoutVerificationDocument::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRequiresVerification($query)
    {
        return $query->where('verification_required', true);
    }

    public function scopePendingRetry($query)
    {
        return $query->where('status', 'failed')
                    ->where('auto_retry_enabled', true)
                    ->where('next_retry_at', '<=', now());
    }

    // Methods
    public function generateReferenceNumber()
    {
        do {
            $number = 'PAYOUT-' . date('Y') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
        } while (static::where('reference_number', $number)->exists());

        $this->reference_number = $number;
        $this->save();

        return $number;
    }

    public function calculateNetAmount()
    {
        $netAmount = $this->amount;
        
        // Subtract processing fee
        if ($this->processing_fee) {
            $netAmount -= $this->processing_fee;
        }
        
        // Subtract tax
        if ($this->tax_amount) {
            $netAmount -= $this->tax_amount;
        }
        
        $this->net_amount = $netAmount;
        $this->save();
        
        return $netAmount;
    }

    public function calculateProcessingFee()
    {
        $fee = 0;
        
        switch ($this->payment_method_type) {
            case 'bank_account':
                $fee = $this->amount * 0.025; // 2.5%
                $fee = min($fee, 25); // Max $25
                break;
            case 'paypal':
                $fee = $this->amount * 0.029; // 2.9%
                $fee += 0.30; // Fixed fee
                break;
            case 'wise':
                $fee = $this->amount * 0.005; // 0.5%
                $fee = max($fee, 0.50); // Min $0.50
                break;
            case 'crypto':
                $fee = $this->amount * 0.01; // 1%
                break;
            case 'check':
                $fee = 5.00; // Fixed fee
                break;
            case 'wire':
                $fee = 15.00; // Fixed fee
                break;
        }
        
        $this->processing_fee = $fee;
        $this->save();
        
        return $fee;
    }

    public function calculateTax()
    {
        // Simplified tax calculation
        $taxRate = $this->getUserTaxRate();
        $taxAmount = $this->amount * $taxRate;
        
        $this->tax_amount = $taxAmount;
        $this->tax_withheld = $taxAmount > 0;
        $this->save();
        
        return $taxAmount;
    }

    public function getUserTaxRate()
    {
        $user = $this->user;
        
        // Get user's tax information
        $taxInfo = $user->taxInformation;
        
        if (!$taxInfo) {
            return 0; // No tax info, no withholding
        }
        
        // Simplified tax rates by country
        $taxRates = [
            'US' => 0.30, // 30% US tax withholding
            'GB' => 0.20, // 20% UK tax
            'CA' => 0.15, // 15% Canada tax
            'AU' => 0.10, // 10% Australia tax
            'DE' => 0.25, // 25% Germany tax
            'FR' => 0.25, // 25% France tax
        ];
        
        return $taxRates[$taxInfo->country] ?? 0;
    }

    public function process()
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
            'estimated_completion_date' => $this->calculateEstimatedCompletionDate(),
        ]);

        try {
            $result = $this->sendPayout();
            
            if ($result['success']) {
                $this->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'transaction_id' => $result['transaction_id'],
                ]);
                
                // Send notification to user
                $this->notifyUser('completed');
                
                return true;
            } else {
                $this->handleFailure($result['error']);
                return false;
            }
            
        } catch (\Exception $e) {
            $this->handleFailure($e->getMessage());
            return false;
        }
    }

    private function sendPayout()
    {
        switch ($this->payment_method_type) {
            case 'bank_account':
                return $this->sendBankTransfer();
            case 'paypal':
                return $this->sendPayPalPayment();
            case 'wise':
                return $this->sendWiseTransfer();
            case 'crypto':
                return $this->sendCryptoTransfer();
            case 'check':
                return $this->sendCheck();
            case 'wire':
                return $this->sendWireTransfer();
            default:
                throw new \Exception('Unsupported payout method');
        }
    }

    private function sendBankTransfer()
    {
        // Bank transfer implementation
        $bankDetails = $this->bank_account_details;
        
        // Integration with banking API (Plaid, Stripe, etc.)
        $transferResult = [
            'success' => true,
            'transaction_id' => 'BANK_' . time(),
            'estimated_arrival' => now()->addDays(3),
        ];
        
        return $transferResult;
    }

    private function sendPayPalPayment()
    {
        // PayPal payout implementation
        $paypalEmail = $this->paypal_email;
        
        // PayPal API call
        $payoutResult = [
            'success' => true,
            'transaction_id' => 'PP_' . time(),
            'estimated_arrival' => now()->addHours(24),
        ];
        
        return $payoutResult;
    }

    private function sendWiseTransfer()
    {
        // Wise transfer implementation
        $wiseResult = [
            'success' => true,
            'transaction_id' => 'WISE_' . time(),
            'estimated_arrival' => now()->addDays(2),
        ];
        
        return $wiseResult;
    }

    private function sendCryptoTransfer()
    {
        // Crypto transfer implementation
        $cryptoResult = [
            'success' => true,
            'transaction_id' => 'CRYPTO_' . time(),
            'estimated_arrival' => now()->addMinutes(30),
        ];
        
        return $cryptoResult;
    }

    private function sendCheck()
    {
        // Check mailing implementation
        $checkResult = [
            'success' => true,
            'transaction_id' => 'CHECK_' . time(),
            'estimated_arrival' => now()->addDays(7),
        ];
        
        return $checkResult;
    }

    private function sendWireTransfer()
    {
        // Wire transfer implementation
        $wireResult = [
            'success' => true,
            'transaction_id' => 'WIRE_' . time(),
            'estimated_arrival' => now()->addDays(1),
        ];
        
        return $wireResult;
    }

    private function handleFailure($reason)
    {
        $this->increment('retry_count');
        
        if ($this->retry_count >= 3) {
            $this->update([
                'status' => 'failed',
                'failure_reason' => $reason,
                'auto_retry_enabled' => false,
            ]);
            
            // Send failure notification
            $this->notifyUser('failed');
            
        } else {
            // Schedule retry
            $retryDelay = $this->calculateRetryDelay();
            $this->update([
                'status' => 'pending',
                'failure_reason' => $reason,
                'next_retry_at' => now()->addHours($retryDelay),
            ]);
        }
    }

    private function calculateRetryDelay()
    {
        // Exponential backoff: 1 hour, 4 hours, 24 hours
        $delays = [1, 4, 24];
        return $delays[min($this->retry_count - 1, 2)];
    }

    private function calculateEstimatedCompletionDate()
    {
        switch ($this->payment_method_type) {
            case 'bank_account':
                return now()->addDays(3);
            case 'paypal':
                return now()->addHours(24);
            case 'wise':
                return now()->addDays(2);
            case 'crypto':
                return now()->addMinutes(30);
            case 'check':
                return now()->addDays(7);
            case 'wire':
                return now()->addDays(1);
            default:
                return now()->addDays(3);
        }
    }

    public function cancel($reason = '')
    {
        if ($this->status === 'completed') {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'notes' => $reason,
        ]);

        return true;
    }

    public function reverse($reason = '')
    {
        if ($this->status !== 'completed') {
            return false;
        }

        $this->update([
            'status' => 'reversed',
            'notes' => $reason,
        ]);

        return true;
    }

    public function requireVerification($documents = [])
    {
        $this->update([
            'verification_required' => true,
            'verification_status' => 'pending',
            'verification_documents' => $documents,
        ]);

        // Send verification request to user
        $this->notifyUser('verification_required');
    }

    public function verify($approved = true, $notes = '')
    {
        $this->update([
            'verification_status' => $approved ? 'verified' : 'rejected',
            'verification_required' => false,
            'notes' => $notes,
        ]);

        if ($approved) {
            // Continue with processing
            $this->process();
        } else {
            // Cancel the payout
            $this->cancel('Verification rejected: ' . $notes);
        }
    }

    public function getFormattedAmount()
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getFormattedNetAmount()
    {
        return number_format($this->net_amount, 2) . ' ' . $this->currency;
    }

    public function getFormattedProcessingFee()
    {
        return number_format($this->processing_fee, 2) . ' ' . $this->currency;
    }

    public function getFormattedTaxAmount()
    {
        return number_format($this->tax_amount, 2) . ' ' . $this->currency;
    }

    public function getStatusColor()
    {
        switch ($this->status) {
            case 'pending':
                return 'yellow';
            case 'processing':
                return 'blue';
            case 'completed':
                return 'green';
            case 'failed':
                return 'red';
            case 'cancelled':
                return 'gray';
            case 'reversed':
                return 'orange';
            default:
                return 'gray';
        }
    }

    public function getStatusText()
    {
        switch ($this->status) {
            case 'pending':
                return 'Pending';
            case 'processing':
                return 'Processing';
            case 'completed':
                return 'Completed';
            case 'failed':
                return 'Failed';
            case 'cancelled':
                return 'Cancelled';
            case 'reversed':
                return 'Reversed';
            default:
                return 'Unknown';
        }
    }

    public function getPaymentMethodIcon()
    {
        switch ($this->payment_method_type) {
            case 'bank_account':
                return 'https://img.icons8.com/color/48/bank.png';
            case 'paypal':
                return 'https://img.icons8.com/color/48/paypal.png';
            case 'wise':
                return 'https://img.icons8.com/color/48/wise.png';
            case 'crypto':
                return 'https://img.icons8.com/color/48/bitcoin.png';
            case 'check':
                return 'https://img.icons8.com/color/48/check.png';
            case 'wire':
                return 'https://img.icons8.com/color/48/wire-transfer.png';
            default:
                return 'https://img.icons8.com/color/48/money.png';
        }
    }

    public function getEstimatedArrivalDate()
    {
        if ($this->status === 'completed' && $this->completed_at) {
            return $this->completed_at->addDays(3)->format('Y-m-d');
        }
        
        if ($this->estimated_completion_date) {
            return $this->estimated_completion_date->format('Y-m-d');
        }
        
        return null;
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function canBeReversed()
    {
        return $this->status === 'completed' && 
               $this->completed_at && 
               $this->completed_at->diffInDays(now()) <= 30;
    }

    public function notifyUser($type)
    {
        // Send notification based on type
        switch ($type) {
            case 'completed':
                // Send completion notification
                break;
            case 'failed':
                // Send failure notification
                break;
            case 'verification_required':
                // Send verification request
                break;
            case 'cancelled':
                // Send cancellation notification
                break;
        }
    }

    public function addToBatch($batchId)
    {
        $this->update(['batch_id' => $batchId]);
    }

    public static function getPendingPayouts()
    {
        return static::pending()
            ->with(['user', 'paymentMethod'])
            ->orderBy('requested_at')
            ->get();
    }

    public static function getFailedPayouts()
    {
        return static::failed()
            ->where('auto_retry_enabled', true)
            ->where('next_retry_at', '<=', now())
            ->with(['user', 'paymentMethod'])
            ->orderBy('next_retry_at')
            ->get();
    }

    public static function getWeeklyReport($userId = null)
    {
        $query = static::where('created_at', '>=', now()->subWeek());
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return [
            'total_payouts' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'completed_amount' => $query->where('status', 'completed')->sum('net_amount'),
            'pending_amount' => $query->where('status', 'pending')->sum('amount'),
            'failed_amount' => $query->where('status', 'failed')->sum('amount'),
            'by_status' => $query->selectRaw('status, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('status')
                ->get(),
            'by_method' => $query->selectRaw('payment_method_type, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('payment_method_type')
                ->get(),
        ];
    }
}
