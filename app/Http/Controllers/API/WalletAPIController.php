<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Http\Requests\AddFundsRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class WalletAPIController extends Controller
{
    /**
     * Get user wallet information.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get user wallet
        $wallet = \DB::table('wallets')
            ->where('user_id', $user->id)
            ->first();

        if (!$wallet) {
            return response()->json([
                'message' => 'Wallet not found'
            ], 404);
        }

        // Get recent transactions (mock for now since wallet_transactions table doesn't exist yet)
        $mockTransactions = [
            [
                'id' => 1,
                'type' => 'credit',
                'amount' => 50.00,
                'description' => 'Initial deposit',
                'status' => 'completed',
                'created_at' => now()->subDays(30),
            ],
            [
                'id' => 2,
                'type' => 'debit',
                'amount' => 25.00,
                'description' => 'Course purchase',
                'status' => 'completed',
                'created_at' => now()->subDays(15),
            ],
        ];

        return response()->json([
            'wallet' => [
                'id' => $wallet->id,
                'balance' => $wallet->balance,
                'currency' => $wallet->currency,
                'status' => $wallet->is_active ? 'active' : 'inactive',
                'created_at' => $wallet->created_at,
                'updated_at' => $wallet->updated_at,
            ],
            'recent_transactions' => $mockTransactions
        ]);
    }

    /**
     * Add funds to wallet.
     */
    public function addFunds(AddFundsRequest $request)
    {
        $user = $request->user();

        // Get user wallet
        $wallet = \DB::table('wallets')
            ->where('user_id', $user->id)
            ->first();

        if (!$wallet) {
            return response()->json([
                'message' => 'Wallet not found'
            ], 404);
        }

        // In a real app, process payment here
        // For now, just add funds directly
        
        $newBalance = $wallet->balance + $request->amount;
        
        // Update wallet
        \DB::table('wallets')
            ->where('user_id', $user->id)
            ->update([
                'balance' => $newBalance,
                'total_earned' => $wallet->total_earned + $request->amount,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Funds added successfully',
            'new_balance' => $newBalance,
        ]);
    }

    /**
     * Get wallet transactions.
     */
    public function transactions(Request $request)
    {
        $user = $request->user();
        
        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();
        
        $query = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20);

        return response()->json([
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ]
        ]);
    }

    /**
     * Get wallet statistics.
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();
        
        // Get transaction statistics
        $totalCredits = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->sum('amount');

        $totalDebits = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'debit')
            ->where('status', 'completed')
            ->sum('amount');

        $transactionCount = WalletTransaction::where('wallet_id', $wallet->id)->count();

        // Monthly statistics
        $monthlyCredits = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonth())
            ->sum('amount');

        $monthlyDebits = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'debit')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonth())
            ->sum('amount');

        return response()->json([
            'current_balance' => $wallet->balance,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'net_amount' => $totalCredits - $totalDebits,
            'transaction_count' => $transactionCount,
            'monthly_credits' => $monthlyCredits,
            'monthly_debits' => $monthlyDebits,
            'monthly_net' => $monthlyCredits - $monthlyDebits,
        ]);
    }

    /**
     * Withdraw funds from wallet.
     */
    public function withdraw(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1|max:10000',
            'withdrawal_method' => 'required|string|in:bank_transfer,paypal',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        return DB::transaction(function () use ($user, $request) {
            $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

            if ($wallet->balance < $request->amount) {
                return response()->json([
                    'message' => 'Insufficient balance',
                    'current_balance' => $wallet->balance,
                    'requested_amount' => $request->amount,
                ], 422);
            }

            // Create transaction
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $request->amount,
                'description' => $request->description ?? 'Withdrawal via ' . $request->withdrawal_method,
                'status' => 'pending', // Pending approval
                'withdrawal_method' => $request->withdrawal_method,
            ]);

            return response()->json([
                'message' => 'Withdrawal request submitted successfully',
                'transaction' => $transaction,
                'current_balance' => $wallet->balance,
            ]);
        });
    }

    /**
     * Transfer funds to another user.
     */
    public function transfer(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'recipient_email' => 'required|email|exists:users,email',
            'amount' => 'required|numeric|min:1|max:10000',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        return DB::transaction(function () use ($user, $request) {
            $senderWallet = Wallet::where('user_id', $user->id)->firstOrFail();
            
            $recipient = \App\Models\User::where('email', $request->recipient_email)->firstOrFail();
            
            // Prevent self-transfer
            if ($user->id === $recipient->id) {
                return response()->json([
                    'message' => 'Cannot transfer to yourself'
                ], 422);
            }

            if ($senderWallet->balance < $request->amount) {
                return response()->json([
                    'message' => 'Insufficient balance',
                    'current_balance' => $senderWallet->balance,
                    'requested_amount' => $request->amount,
                ], 422);
            }

            // Get or create recipient wallet
            $recipientWallet = Wallet::firstOrCreate(
                ['user_id' => $recipient->id],
                [
                    'balance' => 0,
                    'currency' => 'USD',
                    'status' => 'active',
                ]
            );

            // Create debit transaction for sender
            $debitTransaction = WalletTransaction::create([
                'wallet_id' => $senderWallet->id,
                'type' => 'debit',
                'amount' => $request->amount,
                'description' => $request->description ?? 'Transfer to ' . $recipient->email,
                'status' => 'completed',
                'transfer_to' => $recipient->id,
            ]);

            // Create credit transaction for recipient
            $creditTransaction = WalletTransaction::create([
                'wallet_id' => $recipientWallet->id,
                'type' => 'credit',
                'amount' => $request->amount,
                'description' => 'Transfer from ' . $user->email,
                'status' => 'completed',
                'transfer_from' => $user->id,
            ]);

            // Update balances
            $senderWallet->balance -= $request->amount;
            $senderWallet->save();

            $recipientWallet->balance += $request->amount;
            $recipientWallet->save();

            return response()->json([
                'message' => 'Transfer completed successfully',
                'debit_transaction' => $debitTransaction,
                'new_balance' => $senderWallet->balance,
                'recipient' => [
                    'email' => $recipient->email,
                    'name' => $recipient->name,
                ]
            ]);
        });
    }
}
