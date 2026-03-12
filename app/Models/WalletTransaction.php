<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'transaction_type',
        'amount',
        'description',
        'reference_id',
        'status',
        'transaction_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_data' => 'array',
    ];

    /**
     * Get the wallet that owns the transaction.
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Scope a query to only include credit transactions.
     */
    public function scopeCredit($query)
    {
        return $query->where('transaction_type', 'credit');
    }

    /**
     * Scope a query to only include debit transactions.
     */
    public function scopeDebit($query)
    {
        return $query->where('transaction_type', 'debit');
    }
}
