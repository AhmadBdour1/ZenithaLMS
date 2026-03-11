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
        Schema::table('roles', function (Blueprint $table) {
            // Add missing fields for Role model
            $table->string('slug')->nullable()->after('name');
            $table->boolean('is_system')->default(false)->after('description');
            $table->string('color')->nullable()->after('level');
            $table->string('icon')->nullable()->after('color');
            $table->json('metadata')->nullable()->after('icon');
            
            // Add computed fields (will be updated by model events)
            $table->integer('permissions_count')->default(0)->after('is_active');
            $table->integer('users_count')->default(0)->after('permissions_count');
            
            // Add indexes for performance
            $table->index('slug');
            $table->index('is_system');
            $table->index(['is_active', 'level']);
        });

        // Update existing roles to have slugs and make slug not null afterwards
        \DB::statement("UPDATE roles SET slug = LOWER(REPLACE(name, ' ', '_')) WHERE slug IS NULL");
        
        // Now make slug not null
        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'is_system', 
                'color',
                'icon',
                'metadata',
                'permissions_count',
                'users_count'
            ]);
        });
    }
};
