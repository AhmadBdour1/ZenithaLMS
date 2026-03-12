<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'card', 'bank_account', 'paypal', 'stripe', 'crypto', 'apple_pay', 'google_pay', 'bank_transfer'
            $table->string('provider'); // 'stripe', 'paypal', 'wise', 'coinbase', 'plaid', 'adyen', 'square'
            $table->string('method_identifier'); // token, account_id, email, etc.
            $table->string('last_four')->nullable(); // last 4 digits for cards
            $table->string('brand')->nullable(); // visa, mastercard, etc.
            $table->string('expiry_month')->nullable();
            $table->string('expiry_year')->nullable();
            $table->string('cardholder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_type')->nullable(); // 'checking', 'savings'
            $table->string('bank_routing_number')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('paypal_email')->nullable();
            $table->string('crypto_address')->nullable();
            $table->string('crypto_currency')->nullable(); // 'BTC', 'ETH', 'USDT', etc.
            $table->string('apple_pay_token')->nullable();
            $table->string('google_pay_token')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->json('verification_data')->nullable();
            $table->json('metadata')->nullable();
            $table->json('billing_address')->nullable();
            $table->boolean('auto_renewal_enabled')->default(false);
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('blocked_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'provider']);
            $table->index(['user_id', 'is_default']);
            $table->index(['provider', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
