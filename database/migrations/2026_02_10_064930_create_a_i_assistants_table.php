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
        Schema::create('a_i_assistants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('course_id')->nullable();
            $table->foreignId('lesson_id')->nullable();
            $table->string('session_id')->unique();
            $table->enum('type', ['tutor', 'content_generator', 'analyzer', 'translator'])->default('tutor');
            $table->json('conversation_history'); // Store conversation as JSON
            $table->json('context_data')->nullable(); // Course/lesson context
            $table->integer('message_count')->default(0);
            $table->decimal('satisfaction_score', 3, 2)->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('ai_settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a_i_assistants');
    }
};
