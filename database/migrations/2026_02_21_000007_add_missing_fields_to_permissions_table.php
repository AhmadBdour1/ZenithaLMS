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
        Schema::table('permissions', function (Blueprint $table) {
            // Add missing fields for Permission model
            $table->string('slug')->nullable()->after('name');
            $table->string('type')->after('group'); // 'create', 'read', 'update', 'delete', 'manage', 'admin'
            $table->string('entity')->after('type'); // 'user', 'course', 'subscription', etc.
            $table->string('action')->after('entity'); // 'create', 'view', 'edit', 'delete', etc.
            $table->boolean('is_system')->default(false)->after('is_active');
            $table->integer('sort_order')->default(0)->after('is_system');
            $table->json('metadata')->nullable()->after('sort_order');
            
            // Add indexes for performance
            $table->index('slug');
            $table->index(['group', 'type']);
            $table->index(['entity', 'action']);
            $table->index('is_system');
        });

        // Update existing permissions to have slugs based on entity.action
        \DB::statement("UPDATE permissions SET slug = CONCAT(entity, '.', action) WHERE slug IS NULL AND entity IS NOT NULL AND action IS NOT NULL");
        
        // Make slug not null for records that have it
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'type',
                'entity', 
                'action',
                'is_system',
                'sort_order',
                'metadata'
            ]);
        });
    }
};
