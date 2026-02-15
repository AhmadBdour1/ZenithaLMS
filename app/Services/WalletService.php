<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WalletService
{
    /**
     * Get or create user wallet
     */
    public function getUserWallet(User $user): Wallet
    {
        return $user->wallet ?? $user->wallets()->create([
            'balance' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }

    /**
     * Get wallet balance
     */
    public function getBalance(User $user): float
    {
        $wallet = $this->getUserWallet($user);
        return $wallet->balance;
    }

    /**
     * Add funds to wallet
     */
    public function addFunds(User $user, float $amount, string $description = 'Wallet funding'): array
    {
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Amount must be greater than 0',
            ];
        }

        $wallet = $this->getUserWallet($user);

        // Create credit transaction
        $transaction = $wallet->transactions()->create([
            'user_id' => $user->id,
            'transaction_type' => 'credit',
            'amount' => $amount,
            'description' => $description,
        ]);

        // Update wallet balance
        $wallet->update(['balance' => $wallet->balance + $amount]);

        return [
            'success' => true,
            'message' => 'Funds added successfully',
            'transaction' => $transaction,
            'new_balance' => $wallet->balance,
        ];
    }

    /**
     * Withdraw funds from wallet
     */
    public function withdrawFunds(User $user, float $amount, string $description = 'Withdrawal'): array
    {
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Amount must be greater than 0',
            ];
        }

        $wallet = $this->getUserWallet($user);

        if ($wallet->balance < $amount) {
            return [
                'success' => false,
                'message' => 'Insufficient funds',
            ];
        }

        // Create debit transaction
        $transaction = $wallet->transactions()->create([
            'user_id' => $user->id,
            'transaction_type' => 'debit',
            'amount' => $amount,
            'description' => $description,
        ]);

        // Update wallet balance
        $wallet->update(['balance' => $wallet->balance - $amount]);

        return [
            'success' => true,
            'message' => 'Funds withdrawn successfully',
            'transaction' => $transaction,
            'new_balance' => $wallet->balance,
        ];
    }

    /**
     * Process payment from wallet
     */
    public function processPayment(User $user, float $amount, string $description = 'Payment'): array
    {
        return $this->withdrawFunds($user, $amount, $description);
    }

    /**
     * Get transaction history
     */
    public function getTransactionHistory(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $wallet = $this->getUserWallet($user);
        
        $query = $wallet->transactions()->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [
                $filters['start_date'] . ' 00:00:00',
                $filters['end_date'] . ' 23:59:59'
            ]);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('description', 'like', '%' . $search . '%');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions(User $user, int $limit = 10): Collection
    {
        $wallet = $this->getUserWallet($user);
        
        return $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Get wallet statistics
     */
    public function getWalletStats(User $user): array
    {
        $wallet = $this->getUserWallet($user);
        $transactions = $wallet->transactions();

        return [
            'current_balance' => $wallet->balance,
            'total_credits' => $transactions->where('type', 'credit')->sum('amount'),
            'total_debits' => $transactions->where('type', 'debit')->sum('amount'),
            'transaction_count' => $transactions->count(),
            'last_transaction' => $transactions->latest()->first(),
        ];
    }

    /**
     * Check if user has sufficient funds
     */
    public function hasSufficientFunds(User $user, float $amount): bool
    {
        $wallet = $this->getUserWallet($user);
        return $wallet->balance >= $amount;
    }

    /**
     * Transfer funds between users
     */
    public function transferFunds(User $from, User $to, float $amount, string $description = 'Transfer'): array
    {
        if ($from->id === $to->id) {
            return [
                'success' => false,
                'message' => 'Cannot transfer to same account',
            ];
        }

        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Amount must be greater than 0',
            ];
        }

        // Check if sender has sufficient funds
        if (!$this->hasSufficientFunds($from, $amount)) {
            return [
                'success' => false,
                'message' => 'Insufficient funds for transfer',
            ];
        }

        // Withdraw from sender
        $withdrawResult = $this->withdrawFunds($from, $amount, "Transfer to {$to->name}: {$description}");
        
        if (!$withdrawResult['success']) {
            return $withdrawResult;
        }

        // Add to receiver
        $depositResult = $this->addFunds($to, $amount, "Transfer from {$from->name}: {$description}");
        
        if (!$depositResult['success']) {
            // Rollback withdrawal if deposit fails
            $this->addFunds($from, $amount, "Rollback: Failed transfer to {$to->name}");
            return [
                'success' => false,
                'message' => 'Transfer failed - funds have been returned to your account',
            ];
        }

        return [
            'success' => true,
            'message' => 'Transfer completed successfully',
            'amount' => $amount,
            'from_balance' => $withdrawResult['new_balance'],
            'to_balance' => $depositResult['new_balance'],
        ];
    }
}
