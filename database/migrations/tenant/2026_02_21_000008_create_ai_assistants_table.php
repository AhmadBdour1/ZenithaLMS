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
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['tutor', 'assistant', 'grader', 'content_generator', 'translator', 'summarizer'])->default('assistant');
            $table->string('model_name')->nullable(); // 'gpt-4', 'claude', etc.
            $table->string('model_version')->nullable();
            $table->json('configuration')->nullable(); // JSON settings for the AI model
            $table->json('capabilities')->nullable(); // JSON array of capabilities
            $table->enum('status', ['active', 'inactive', 'training', 'maintenance'])->default('active');
            $table->foreignId('user_id')->nullable(); // Owner/creator of the assistant
            $table->foreignId('course_id')->nullable(); // Optional course association
            $table->integer('api_usage_limit')->nullable();
            $table->integer('api_usage_current')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_public')->default(false); // Whether other users can access this assistant
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('set null');
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
