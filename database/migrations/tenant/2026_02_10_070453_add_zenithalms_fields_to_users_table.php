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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id');
            $table->foreignId('organization_id')->nullable()->after('role_id');
            $table->foreignId('branch_id')->nullable()->after('organization_id');
            $table->foreignId('department_id')->nullable()->after('branch_id');
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('avatar');
            $table->boolean('is_active')->default(true)->after('bio');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role_id',
                'organization_id', 
                'branch_id',
                'department_id',
                'phone',
                'avatar',
                'bio',
                'is_active',
                'last_login_at'
            ]);
        });
    }
};
