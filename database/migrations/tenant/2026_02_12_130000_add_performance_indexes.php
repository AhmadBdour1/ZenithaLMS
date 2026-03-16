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
        $driver = Schema::getConnection()->getDriverName();

        // Courses table indexes
        $this->addIndexIfPossible('courses', ['category_id', 'is_published'], 'courses_category_id_is_published_index');
        $this->addIndexIfPossible('courses', ['instructor_id', 'created_at'], 'courses_instructor_id_created_at_index');
        $this->addIndexIfPossible('courses', ['price', 'is_published'], 'courses_price_is_published_index');
        $this->addIndexIfPossible('courses', ['slug'], 'courses_slug_index');
        $this->addFullTextIfPossible($driver, 'courses', ['title', 'description'], 'courses_title_description_fulltext');

        // Enrollments table indexes
        $this->addIndexIfPossible('enrollments', ['user_id', 'course_id'], 'enrollments_user_id_course_id_index');
        $this->addIndexIfPossible('enrollments', ['course_id', 'created_at'], 'enrollments_course_id_created_at_index');
        $this->addIndexIfPossible('enrollments', ['status', 'created_at'], 'enrollments_status_created_at_index');

        // Quiz attempts table indexes
        $this->addIndexIfPossible('quiz_attempts', ['user_id', 'quiz_id'], 'quiz_attempts_user_id_quiz_id_index');
        $this->addIndexIfPossible('quiz_attempts', ['quiz_id', 'status'], 'quiz_attempts_quiz_id_status_index');
        $this->addIndexIfPossible('quiz_attempts', ['user_id', 'status', 'created_at'], 'quiz_attempts_user_id_status_created_at_index');
        $this->addIndexIfPossible('quiz_attempts', ['created_at'], 'quiz_attempts_created_at_index');

        // Forums table indexes
        $this->addIndexIfPossible('forums', ['category', 'is_active'], 'forums_category_is_active_index');
        $this->addIndexIfPossible('forums', ['user_id', 'created_at'], 'forums_user_id_created_at_index');
        $this->addIndexIfPossible('forums', ['course_id'], 'forums_course_id_index');
        $this->addFullTextIfPossible($driver, 'forums', ['title', 'content'], 'forums_title_content_fulltext');

        // Virtual classes table indexes
        $this->addIndexIfPossible('virtual_classes', ['instructor_id', 'status'], 'virtual_classes_instructor_id_status_index');
        $this->addIndexIfPossible('virtual_classes', ['course_id', 'status'], 'virtual_classes_course_id_status_index');

        if ($this->allColumnsExist('virtual_classes', ['start_time', 'status'])) {
            $this->addIndexIfPossible('virtual_classes', ['start_time', 'status'], 'virtual_classes_start_time_status_index');
        } elseif ($this->allColumnsExist('virtual_classes', ['scheduled_at', 'status'])) {
            $this->addIndexIfPossible('virtual_classes', ['scheduled_at', 'status'], 'virtual_classes_scheduled_at_status_index');
        }

        $this->addIndexIfPossible('virtual_classes', ['meeting_id'], 'virtual_classes_meeting_id_index');

        // Notifications table indexes
        $this->addIndexIfPossible('notifications', ['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_type_notifiable_id_read_at_index');
        $this->addIndexIfPossible('notifications', ['created_at'], 'notifications_created_at_index');
        $this->addIndexIfPossible('notifications', ['type'], 'notifications_type_index');

        // Wallet transactions table indexes
        $this->addIndexIfPossible('wallet_transactions', ['wallet_id', 'type', 'status'], 'wallet_transactions_wallet_id_type_status_index');
        $this->addIndexIfPossible('wallet_transactions', ['created_at'], 'wallet_transactions_created_at_index');
        $this->addIndexIfPossible('wallet_transactions', ['type', 'status'], 'wallet_transactions_type_status_index');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Courses table indexes
        $this->dropIndexIfExists('courses', 'courses_category_id_is_published_index');
        $this->dropIndexIfExists('courses', 'courses_instructor_id_created_at_index');
        $this->dropIndexIfExists('courses', 'courses_price_is_published_index');
        $this->dropIndexIfExists('courses', 'courses_slug_index');
        $this->dropFullTextIfExists($driver, 'courses', 'courses_title_description_fulltext');

        // Enrollments table indexes
        $this->dropIndexIfExists('enrollments', 'enrollments_user_id_course_id_index');
        $this->dropIndexIfExists('enrollments', 'enrollments_course_id_created_at_index');
        $this->dropIndexIfExists('enrollments', 'enrollments_status_created_at_index');

        // Quiz attempts table indexes
        $this->dropIndexIfExists('quiz_attempts', 'quiz_attempts_user_id_quiz_id_index');
        $this->dropIndexIfExists('quiz_attempts', 'quiz_attempts_quiz_id_status_index');
        $this->dropIndexIfExists('quiz_attempts', 'quiz_attempts_user_id_status_created_at_index');
        $this->dropIndexIfExists('quiz_attempts', 'quiz_attempts_created_at_index');

        // Forums table indexes
        $this->dropIndexIfExists('forums', 'forums_category_is_active_index');
        $this->dropIndexIfExists('forums', 'forums_user_id_created_at_index');
        $this->dropIndexIfExists('forums', 'forums_course_id_index');
        $this->dropFullTextIfExists($driver, 'forums', 'forums_title_content_fulltext');

        // Virtual classes table indexes
        $this->dropIndexIfExists('virtual_classes', 'virtual_classes_instructor_id_status_index');
        $this->dropIndexIfExists('virtual_classes', 'virtual_classes_course_id_status_index');
        $this->dropIndexIfExists('virtual_classes', 'virtual_classes_start_time_status_index');
        $this->dropIndexIfExists('virtual_classes', 'virtual_classes_scheduled_at_status_index');
        $this->dropIndexIfExists('virtual_classes', 'virtual_classes_meeting_id_index');

        // Notifications table indexes
        $this->dropIndexIfExists('notifications', 'notifications_notifiable_type_notifiable_id_read_at_index');
        $this->dropIndexIfExists('notifications', 'notifications_created_at_index');
        $this->dropIndexIfExists('notifications', 'notifications_type_index');

        // Wallet transactions table indexes
        $this->dropIndexIfExists('wallet_transactions', 'wallet_transactions_wallet_id_type_status_index');
        $this->dropIndexIfExists('wallet_transactions', 'wallet_transactions_created_at_index');
        $this->dropIndexIfExists('wallet_transactions', 'wallet_transactions_type_status_index');
    }

    private function addIndexIfPossible(string $table, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (!$this->allColumnsExist($table, $columns)) {
            return;
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $indexName) {
            $tableBlueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
                $tableBlueprint->dropIndex($indexName);
            });
        } catch (\Throwable $e) {
            // Ignore safe rollback failures.
        }
    }

    private function addFullTextIfPossible(string $driver, string $table, array $columns, string $indexName): void
    {
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        if (!Schema::hasTable($table)) {
            return;
        }

        if (!$this->allColumnsExist($table, $columns)) {
            return;
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $columnList = implode(', ', array_map(fn ($column) => "`{$column}`", $columns));

        DB::statement("ALTER TABLE `{$table}` ADD FULLTEXT `{$indexName}` ({$columnList})");
    }

    private function dropFullTextIfExists(string $driver, string $table, string $indexName): void
    {
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        if (!Schema::hasTable($table)) {
            return;
        }

        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        try {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        } catch (\Throwable $e) {
            // Ignore safe rollback failures.
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

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->exists();
        }

        return false;
    }
};