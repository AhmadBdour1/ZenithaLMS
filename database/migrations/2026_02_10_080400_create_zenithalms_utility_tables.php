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
        // ZenithaLMS Backups Table
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('backup_name');
            $table->string('backup_type'); // full, database, files
            $table->string('backup_path');
            $table->integer('file_size');
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('backup_settings')->nullable(); // ZenithaLMS backup configuration
            $table->json('backup_metadata')->nullable(); // ZenithaLMS backup metadata
            $table->timestamps();
        });

        // ZenithaLMS Languages Table
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // en, ar, fr, etc.
            $table->string('native_name');
            $table->string('flag_icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('language_settings')->nullable(); // ZenithaLMS language settings
            $table->timestamps();
        });

        // ZenithaLMS Translations Table
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id');
            $table->string('key');
            $table->text('value');
            $table->string('group')->default('messages'); // messages, validation, etc.
            $table->json('translation_data')->nullable(); // ZenithaLMS translation metadata
            $table->timestamps();
        });

        // ZenithaLMS Newsletters Table
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subject');
            $table->text('content');
            $table->string('template')->default('default');
            $table->string('status')->default('draft'); // draft, scheduled, sent
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('total_sent')->default(0);
            $table->integer('total_opened')->default(0);
            $table->integer('total_clicked')->default(0);
            $table->json('newsletter_settings')->nullable(); // ZenithaLMS newsletter settings
            $table->json('ai_content')->nullable(); // ZenithaLMS AI-generated content
            $table->timestamps();
        });

        // ZenithaLMS Newsletter Subscribers Table
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->foreignId('language_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('subscribed'); // subscribed, unsubscribed, bounced
            $table->timestamp('subscribed_at');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->json('subscriber_data')->nullable(); // ZenithaLMS subscriber metadata
            $table->timestamps();
        });

        // ZenithaLMS Newsletter Campaigns Table
        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_id');
            $table->string('campaign_name');
            $table->json('recipient_segments')->nullable(); // ZenithaLMS targeting segments
            $table->string('status')->default('draft'); // draft, running, completed, paused
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('total_sent')->default(0);
            $table->integer('total_opened')->default(0);
            $table->integer('total_clicked')->default(0);
            $table->json('campaign_data')->nullable(); // ZenithaLMS campaign metadata
            $table->timestamps();
        });

        // ZenithaLMS Notification Templates Table
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // email, push, sms, in_app
            $table->string('trigger_event'); // course_enrolled, payment_completed, etc.
            $table->string('subject')->nullable();
            $table->text('content');
            $table->json('template_variables')->nullable(); // ZenithaLMS template variables
            $table->json('template_settings')->nullable(); // ZenithaLMS template settings
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ZenithaLMS Student Settings Table
        Schema::create('student_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->json('preferences')->nullable(); // ZenithaLMS student preferences
            $table->json('notification_settings')->nullable(); // ZenithaLMS notification preferences
            $table->json('privacy_settings')->nullable(); // ZenithaLMS privacy settings
            $table->json('learning_settings')->nullable(); // ZenithaLMS learning preferences
            $table->json('accessibility_settings')->nullable(); // ZenithaLMS accessibility settings
            $table->timestamps();
        });

        // ZenithaLMS Video Settings Table
        Schema::create('video_settings', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // vimeo, youtube, vdocipher, etc.
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->json('platform_settings')->nullable(); // ZenithaLMS platform configuration
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ZenithaLMS Video Analytics Table
        Schema::create('video_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('lesson_id');
            $table->string('video_platform');
            $table->string('video_id');
            $table->integer('watch_time_seconds')->default(0);
            $table->integer('total_duration_seconds')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->integer('pause_count')->default(0);
            $table->integer('seek_count')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('analytics_data')->nullable(); // ZenithaLMS video analytics
            $table->timestamps();
        });

        // ZenithaLMS System Logs Table
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('log_level'); // info, warning, error, critical
            $table->string('category'); // auth, payment, course, etc.
            $table->text('message');
            $table->json('context')->nullable(); // ZenithaLMS log context
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('request_data')->nullable(); // ZenithaLMS request data
            $table->timestamps();
        });

        // ZenithaLMS Settings Table
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('text'); // text, boolean, number, json
            $table->string('group')->default('general'); // general, payment, email, etc.
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->json('setting_data')->nullable(); // ZenithaLMS setting metadata
            $table->timestamps();
        });

        // ZenithaLMS Cache Settings Table
        Schema::create('cache_settings', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key');
            $table->string('cache_type'); // page, query, api, etc.
            $table->integer('ttl_minutes')->default(60);
            $table->json('cache_tags')->nullable(); // ZenithaLMS cache tags
            $table->boolean('is_active')->default(true);
            $table->json('cache_settings')->nullable(); // ZenithaLMS cache configuration
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache_settings');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('video_analytics');
        Schema::dropIfExists('video_settings');
        Schema::dropIfExists('student_settings');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('newsletter_campaigns');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('newsletters');
        Schema::dropIfExists('translations');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('backups');
    }
};
