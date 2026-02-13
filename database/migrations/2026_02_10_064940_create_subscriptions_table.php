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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('tier'); // starter, professional, enterprise
            $table->decimal('price', 10, 2);
            $table->string('billing_cycle'); // monthly, annual
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled', 'trial'])->default('trial');
            $table->string('payment_method')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->json('features'); // Available features for this tier
            $table->integer('max_students');
            $table->integer('max_instructors');
            $table->integer('max_courses');
            $table->boolean('ai_features_enabled')->default(false);
            $table->json('usage_limits')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
