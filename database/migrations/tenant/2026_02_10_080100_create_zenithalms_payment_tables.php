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
        // ZenithaLMS Wallet Table
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->decimal('balance', 10, 2)->default(0);
            $table->decimal('total_earned', 10, 2)->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->boolean('is_active')->default(true);
            $table->json('wallet_data')->nullable(); // ZenithaLMS wallet metadata
            $table->timestamps();
        });

        // ZenithaLMS Wallet Transactions Table
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('wallet_id');
            $table->string('transaction_type'); // credit, debit, refund
            $table->decimal('amount', 10, 2);
            $table->string('description');
            $table->string('reference_id')->nullable(); // payment_id, order_id, etc.
            $table->string('status')->default('completed');
            $table->json('transaction_data')->nullable(); // ZenithaLMS transaction metadata
            $table->timestamps();
        });

        // ZenithaLMS Coupons Table
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('discount_type'); // percentage, fixed
            $table->decimal('discount_value', 10, 2);
            $table->decimal('minimum_amount', 10, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->json('coupon_data')->nullable(); // ZenithaLMS coupon metadata
            $table->json('ai_targeting')->nullable(); // ZenithaLMS AI targeting data
            $table->timestamps();
        });

        // ZenithaLMS Coupon Usages Table
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('coupon_id');
            $table->foreignId('order_id')->nullable();
            $table->decimal('discount_amount', 10, 2);
            $table->timestamp('used_at');
            $table->timestamps();
        });

        // ZenithaLMS Affiliate Users Table
        Schema::create('affiliate_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('affiliate_code')->unique();
            $table->decimal('commission_rate', 5, 2)->default(10.00); // percentage
            $table->decimal('total_earned', 10, 2)->default(0);
            $table->decimal('total_withdrawn', 10, 2)->default(0);
            $table->string('status')->default('active'); // active, inactive, suspended
            $table->json('affiliate_data')->nullable(); // ZenithaLMS affiliate metadata
            $table->timestamps();
        });

        // ZenithaLMS Affiliate Commissions Table
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_user_id');
            $table->foreignId('referred_user_id');
            $table->foreignId('order_id')->nullable();
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('order_amount', 10, 2);
            $table->string('commission_type'); // referral, purchase, recurring
            $table->string('status')->default('pending'); // pending, approved, paid
            $table->timestamp('earned_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('commission_data')->nullable(); // ZenithaLMS commission metadata
            $table->timestamps();
        });

        // ZenithaLMS Payment Gateways Table
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Stripe, PayPal, etc.
            $table->string('gateway_code')->unique(); // stripe, paypal, etc.
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_test_mode')->default(false);
            $table->json('gateway_settings')->nullable(); // ZenithaLMS gateway configuration
            $table->json('supported_currencies')->nullable(); // ZenithaLMS supported currencies
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ZenithaLMS Payment Gateway Configs Table
        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_gateway_id');
            $table->string('config_key');
            $table->text('config_value');
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        // ZenithaLMS Orders Table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('order_number')->unique();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('status')->default('pending'); // pending, completed, cancelled, refunded
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->foreignId('payment_gateway_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->json('order_data')->nullable(); // ZenithaLMS order metadata
            $table->json('ai_analysis')->nullable(); // ZenithaLMS AI order analysis
            $table->timestamp('order_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // ZenithaLMS Order Items Table
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');
            $table->string('item_type'); // course, ebook, subscription
            $table->foreignId('item_id'); // course_id, ebook_id, etc.
            $table->string('item_name');
            $table->decimal('item_price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('total_price', 10, 2);
            $table->json('item_data')->nullable(); // ZenithaLMS item metadata
            $table->timestamps();
        });

        // ZenithaLMS Refunds Table
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('order_id');
            $table->foreignId('payment_id')->nullable();
            $table->decimal('refund_amount', 10, 2);
            $table->string('refund_reason');
            $table->string('status')->default('pending'); // pending, approved, rejected, processed
            $table->text('admin_notes')->nullable();
            $table->json('refund_data')->nullable(); // ZenithaLMS refund metadata
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('payment_gateway_configs');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('affiliate_users');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
