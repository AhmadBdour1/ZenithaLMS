<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'balance',
        'total_earned',
        'total_spent',
        'currency',
        'is_active',
        'wallet_data',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'is_active' => 'boolean',
        'wallet_data' => 'array',
    ];

    /**
     * ZenithaLMS: Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * ZenithaLMS: Methods
     */
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 2) . ' ' . $this->currency;
    }

    public function credit($amount, $description = null, $referenceId = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive');
        }

        $this->balance += $amount;
        $this->total_earned += $amount;
        $this->save();

        // Create transaction record
        $this->transactions()->create([
            'transaction_type' => 'credit',
            'amount' => $amount,
            'description' => $description ?? 'Wallet credit',
            'reference_id' => $referenceId,
            'status' => 'completed',
        ]);

        return $this;
    }

    public function debit($amount, $description = null, $referenceId = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Debit amount must be positive');
        }

        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $this->balance -= $amount;
        $this->total_spent += $amount;
        $this->save();

        // Create transaction record
        $this->transactions()->create([
            'transaction_type' => 'debit',
            'amount' => $amount,
            'description' => $description ?? 'Wallet debit',
            'reference_id' => $referenceId,
            'status' => 'completed',
        ]);

        return $this;
    }

    public function canDebit($amount)
    {
        return $this->balance >= $amount;
    }

    public function getTransactionHistory($limit = 50)
    {
        return $this->transactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRecentTransactions($limit = 10)
    {
        return $this->transactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * ZenithaLMS: Analytics Methods
     */
    public function getBalanceHistory($days = 30)
    {
        return $this->transactions()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->created_at->format('Y-m-d');
            })
            ->map(function ($dayTransactions) {
                return [
                    'date' => $dayTransactions->first()->created_at->format('Y-m-d'),
                    'credits' => $dayTransactions->where('transaction_type', 'credit')->sum('amount'),
                    'debits' => $dayTransactions->where('transaction_type', 'debit')->sum('amount'),
                    'net_change' => $dayTransactions->where('transaction_type', 'credit')->sum('amount') - 
                                   $dayTransactions->where('transaction_type', 'debit')->sum('amount'),
                ];
            });
    }

    public function getMonthlyStats()
    {
        return $this->transactions()
            ->where('created_at', '>=', now()->startOfMonth())
            ->get()
            ->groupBy('transaction_type')
            ->map(function ($transactions) {
                return [
                    'count' => $transactions->count(),
                    'total_amount' => $transactions->sum('amount'),
                    'average_amount' => $transactions->avg('amount'),
                ];
            });
    }

    /**
     * ZenithaLMS: Factory Method
     */
    public static function createForUser($userId, $currency = 'USD')
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'balance' => 0,
                'total_earned' => 0,
                'total_spent' => 0,
                'currency' => $currency,
                'is_active' => true,
            ]
        );
    }
}
