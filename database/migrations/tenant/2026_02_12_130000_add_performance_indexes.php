<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $indexes = [
            'courses' => [
                ['category_id', 'is_published'],
                ['instructor_id', 'is_published'],
                ['level', 'is_published'],
                ['is_free', 'is_published'],
                ['created_at'],
            ],
            'enrollments' => [
                ['user_id', 'status'],
                ['course_id', 'status'],
                ['organization_id', 'status'],
                ['enrolled_at'],
                ['status', 'progress_percentage'],
            ],
            'quiz_attempts' => [
                ['user_id', 'quiz_id'],
                ['quiz_id', 'status'],
                ['submitted_at'],
                ['created_at'],
            ],
            'forums' => [
                ['course_id', 'created_at'],
                ['user_id', 'created_at'],
                ['created_at'],
            ],
            'virtual_classes' => [
                ['course_id', 'scheduled_at'],
                ['instructor_id', 'scheduled_at'],
                ['scheduled_at'],
                ['created_at'],
            ],
            'notifications' => [
                ['user_id', 'read_at'],
                ['type'],
                ['created_at'],
            ],
            'wallet_transactions' => [
                ['user_id', 'type'],
                ['user_id', 'status'],
                ['created_at'],
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($tableIndexes as $columns) {
                if (!$this->allColumnsExist($table, $columns)) {
                    continue;
                }

                $indexName = $this->makeIndexName($table, $columns);

                if ($this->indexExists($table, $indexName)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $indexName) {
                    $tableBlueprint->index($columns, $indexName);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'courses' => [
                ['category_id', 'is_published'],
                ['instructor_id', 'is_published'],
                ['level', 'is_published'],
                ['is_free', 'is_published'],
                ['created_at'],
            ],
            'enrollments' => [
                ['user_id', 'status'],
                ['course_id', 'status'],
                ['organization_id', 'status'],
                ['enrolled_at'],
                ['status', 'progress_percentage'],
            ],
            'quiz_attempts' => [
                ['user_id', 'quiz_id'],
                ['quiz_id', 'status'],
                ['submitted_at'],
                ['created_at'],
            ],
            'forums' => [
                ['course_id', 'created_at'],
                ['user_id', 'created_at'],
                ['created_at'],
            ],
            'virtual_classes' => [
                ['course_id', 'scheduled_at'],
                ['instructor_id', 'scheduled_at'],
                ['scheduled_at'],
                ['created_at'],
            ],
            'notifications' => [
                ['user_id', 'read_at'],
                ['type'],
                ['created_at'],
            ],
            'wallet_transactions' => [
                ['user_id', 'type'],
                ['user_id', 'status'],
                ['created_at'],
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($tableIndexes as $columns) {
                $indexName = $this->makeIndexName($table, $columns);

                if (!$this->indexExists($table, $indexName)) {
                    continue;
                }

                try {
                    Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
                        $tableBlueprint->dropIndex($indexName);
                    });
                } catch (\Throwable $e) {
                    // تجاهل أي خطأ إذا كان الـ index غير قابل للحذف أو غير موجود فعليًا
                }
            }
        }
    }

    private function allColumnsExist(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function makeIndexName(string $table, array $columns): string
    {
        return $table . '_' . implode('_', $columns) . '_index';
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $databaseName = $connection->getDatabaseName();

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