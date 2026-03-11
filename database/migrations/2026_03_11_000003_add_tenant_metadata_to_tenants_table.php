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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('organization_name')->nullable()->after('id');
            $table->string('admin_name')->nullable();
            $table->string('admin_email')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('primary_color')->default('#3B82F6');
            $table->string('secondary_color')->default('#10B981');
            $table->text('custom_css')->nullable();
            $table->enum('status', ['active', 'suspended', 'trial'])->default('trial');
            $table->date('trial_ends_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'organization_name',
                'admin_name',
                'admin_email',
                'logo_url',
                'primary_color',
                'secondary_color',
                'custom_css',
                'status',
                'trial_ends_at',
            ]);
        });
    }
};
