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
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('notifications', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
