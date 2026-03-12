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
            // Add foreign key constraints for ZenithaLMS fields
            $table->foreign('role_id')
                  ->references('id')
                  ->on('roles')
                  ->onDelete('set null');
                  
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('set null');
                  
            $table->foreign('branch_id')
                  ->references('id')
                  ->on('branches')
                  ->onDelete('set null');
                  
            $table->foreign('department_id')
                  ->references('id')
                  ->on('departments')
                  ->onDelete('set null');
                  
            // Add indexes for better performance
            $table->index('role_id');
            $table->index('organization_id');
            $table->index('branch_id');
            $table->index('department_id');
            $table->index(['is_active', 'last_login_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['department_id']);
            
            $table->dropIndex(['role_id']);
            $table->dropIndex(['organization_id']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['is_active', 'last_login_at']);
        });
    }
};
