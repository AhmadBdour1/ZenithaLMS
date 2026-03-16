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

        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $databaseName = $connection->getDatabaseName();

        foreach ($indexes as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($tableIndexes as $columns) {
                $indexName = $table . '_' . implode('_', $columns) . '_index';

                if ($this->indexExists($driver, $databaseName, $table, $indexName)) {
                    continue;
                }

                $columnList = implode(', ', array_map(fn ($column) => "`{$column}`", $columns));

                DB::statement("CREATE INDEX `{$indexName}` ON `{$table}` ({$columnList})");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($tableIndexes as $columns) {
                $indexName = $table . '_' . implode('_', $columns) . '_index';

                try {
                    Schema::table($table, function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                } catch (\Throwable $e) {
                    // Ignore if index does not exist or cannot be dropped safely.
                }
            }
        }
    }

    private function indexExists(string $driver, string $databaseName, string $table, string $indexName): bool
    {
        if ($driver === 'sqlite') {
            $existingIndexes = DB::select("PRAGMA index_list('{$table}')");

            return collect($existingIndexes)->pluck('name')->contains($indexName);
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return DB::table('information_schema.statistics')
                ->where('table_schema', $databaseName)
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists();
        }

        return false;
    }
};