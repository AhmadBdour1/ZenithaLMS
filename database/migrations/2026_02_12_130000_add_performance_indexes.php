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
        // Courses table indexes
        Schema::table('courses', function (Blueprint $table) {
            // Check if index exists before creating
            if (!Schema::hasIndex('courses', 'courses_category_id_is_active_index')) {
                $table->index(['category_id', 'is_active']);
            }
            if (!Schema::hasIndex('courses', 'courses_instructor_id_created_at_index')) {
                $table->index(['instructor_id', 'created_at']);
            }
            if (!Schema::hasIndex('courses', 'courses_price_is_active_index')) {
                $table->index(['price', 'is_active']);
            }
            if (!Schema::hasIndex('courses', 'courses_slug_index')) {
                $table->index(['slug']);
            }
            // Skip fulltext for SQLite
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['title', 'description']);
            }
        });

        // Enrollments table indexes
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasIndex('enrollments', 'enrollments_user_id_course_id_index')) {
                $table->index(['user_id', 'course_id']);
            }
            if (!Schema::hasIndex('enrollments', 'enrollments_course_id_created_at_index')) {
                $table->index(['course_id', 'created_at']);
            }
            if (!Schema::hasIndex('enrollments', 'enrollments_status_created_at_index')) {
                $table->index(['status', 'created_at']);
            }
        });

        // Quiz attempts table indexes
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasIndex('quiz_attempts', 'quiz_attempts_user_id_quiz_id_index')) {
                $table->index(['user_id', 'quiz_id']);
            }
            if (!Schema::hasIndex('quiz_attempts', 'quiz_attempts_quiz_id_status_index')) {
                $table->index(['quiz_id', 'status']);
            }
            if (!Schema::hasIndex('quiz_attempts', 'quiz_attempts_user_id_status_created_at_index')) {
                $table->index(['user_id', 'status', 'created_at']);
            }
            if (!Schema::hasIndex('quiz_attempts', 'quiz_attempts_created_at_index')) {
                $table->index(['created_at']);
            }
        });

        // Forums table indexes
        Schema::table('forums', function (Blueprint $table) {
            $table->index(['category', 'is_active']);
            $table->index(['user_id', 'created_at']);
            $table->index(['course_id']);
            // Skip fulltext for SQLite
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['title', 'content']);
            }
        });

        // Virtual classes table indexes
        Schema::table('virtual_classes', function (Blueprint $table) {
            $table->index(['instructor_id', 'status']);
            $table->index(['course_id', 'status']);
            $table->index(['start_time', 'status']);
            $table->index(['meeting_id']);
        });

        // Notifications table indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->index(['created_at']);
            $table->index(['type']);
        });

        // Wallet transactions table indexes (if exists)
        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->index(['wallet_id', 'type', 'status']);
                $table->index(['created_at']);
                $table->index(['type', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes (simplified - in production, you'd want to be more specific)
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'is_active']);
            $table->dropIndex(['instructor_id', 'created_at']);
            $table->dropIndex(['price', 'is_active']);
            $table->dropIndex(['slug']);
            // Skip fulltext for SQLite
            if (config('database.default') !== 'sqlite') {
                $table->dropFullText(['title', 'description']);
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'course_id']);
            $table->dropIndex(['course_id', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'quiz_id']);
            $table->dropIndex(['quiz_id', 'status']);
            $table->dropIndex(['user_id', 'status', 'created_at']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('forums', function (Blueprint $table) {
            $table->dropIndex(['category', 'is_active']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['course_id']);
            // Skip fulltext for SQLite
            if (config('database.default') !== 'sqlite') {
                $table->dropFullText(['title', 'content']);
            }
        });

        Schema::table('virtual_classes', function (Blueprint $table) {
            $table->dropIndex(['instructor_id', 'status']);
            $table->dropIndex(['course_id', 'status']);
            $table->dropIndex(['start_time', 'status']);
            $table->dropIndex(['meeting_id']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['type']);
        });
    }
};
