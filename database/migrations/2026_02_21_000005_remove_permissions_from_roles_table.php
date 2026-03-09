<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to handle this differently due to index constraints
        if (DB::getDriverName() === 'sqlite') {
            // Skip removing the column for now to avoid index issues
            // The column will be ignored by the application anyway
            return;
        }
        
        Schema::table('roles', function (Blueprint $table) {
            // Remove the conflicting JSON permissions field
            // We're using many-to-many relationship instead
            $table->dropColumn('permissions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Add back the permissions field for rollback
            $table->json('permissions')->nullable();
        });
    }
};
