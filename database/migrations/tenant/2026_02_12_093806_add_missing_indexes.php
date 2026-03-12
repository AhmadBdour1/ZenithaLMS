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
        // Add missing indexes safely using raw SQL
        $indexes = [
            'users' => [
                ['role_id', 'is_active'],
                ['organization_id', 'is_active'],
                ['email'],
                ['created_at'],
            ],
            'courses' => [
                ['is_published', 'is_featured'],
                ['category_id', 'is_published'],
                ['instructor_id', 'is_published'],
                ['level', 'is_published'],
                ['is_free', 'is_published'],
                ['slug'],
                ['created_at'],
            ],
            'enrollments' => [
                ['user_id', 'status'],
                ['course_id', 'status'],
                ['organization_id', 'status'],
                ['enrolled_at'],
                ['status', 'progress_percentage'],
            ],
            'lessons' => [
                ['course_id', 'sort_order'],
                ['course_id', 'is_published'],
                ['type', 'is_published'],
                ['is_free', 'is_published'],
                ['slug'],
            ],
            'categories' => [
                ['is_active', 'sort_order'],
                ['slug'],
            ],
            'organizations' => [
                ['is_active'],
                ['created_at'],
            ],
            'student_progress' => [
                ['user_id', 'course_id'],
                ['user_id', 'lesson_id'],
                ['course_id', 'status'],
                ['status', 'completed_at'],
                ['started_at'],
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (Schema::hasTable($table)) {
                foreach ($tableIndexes as $columns) {
                    $indexName = $table . '_' . implode('_', $columns) . '_index';
                    
                    // Check if index exists
                    $existingIndexes = DB::select("PRAGMA index_list('{$table}')");
                    $indexExists = collect($existingIndexes)->pluck('name')->contains($indexName);
                    
                    if (!$indexExists) {
                        $columnList = implode(', ', $columns);
                        DB::statement("CREATE INDEX {$indexName} ON {$table} ({$columnList})");
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes safely
        $indexes = [
            'users' => [
                ['role_id', 'is_active'],
                ['organization_id', 'is_active'],
                ['email'],
                ['created_at'],
            ],
            'courses' => [
                ['is_published', 'is_featured'],
                ['category_id', 'is_published'],
                ['instructor_id', 'is_published'],
                ['level', 'is_published'],
                ['is_free', 'is_published'],
                ['slug'],
                ['created_at'],
            ],
            'enrollments' => [
                ['user_id', 'status'],
                ['course_id', 'status'],
                ['organization_id', 'status'],
                ['enrolled_at'],
                ['status', 'progress_percentage'],
            ],
            'lessons' => [
                ['course_id', 'sort_order'],
                ['course_id', 'is_published'],
                ['type', 'is_published'],
                ['is_free', 'is_published'],
                ['slug'],
            ],
            'categories' => [
                ['is_active', 'sort_order'],
                ['slug'],
            ],
            'organizations' => [
                ['is_active'],
                ['created_at'],
            ],
            'student_progress' => [
                ['user_id', 'course_id'],
                ['user_id', 'lesson_id'],
                ['course_id', 'status'],
                ['status', 'completed_at'],
                ['started_at'],
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (Schema::hasTable($table)) {
                foreach ($tableIndexes as $columns) {
                    $indexName = $table . '_' . implode('_', $columns) . '_index';
                    
                    try {
                        Schema::table($table, function (Blueprint $table) use ($indexName) {
                            $table->dropIndex($indexName);
                        });
                    } catch (\Exception $e) {
                        // Index doesn't exist, continue
                    }
                }
            }
        }
    }
};
