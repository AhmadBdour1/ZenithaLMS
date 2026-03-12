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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('video_url')->nullable();
            $table->string('video_file')->nullable();
            $table->enum('type', ['video', 'text', 'quiz', 'assignment', 'live'])->default('video');
            $table->integer('duration_minutes')->default(0);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_published')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->index(['course_id', 'sort_order']);
            $table->index(['course_id', 'is_published']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
