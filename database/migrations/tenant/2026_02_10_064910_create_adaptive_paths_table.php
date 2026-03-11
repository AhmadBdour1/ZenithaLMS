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
        Schema::create('adaptive_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->nullable()->constrained()->onDelete('set null');
            $table->string('path_type'); // learning, remediation, advanced
            $table->json('conditions'); // Conditions for this path
            $table->json('recommended_lessons'); // Array of lesson IDs
            $table->json('completed_lessons')->nullable(); // Array of completed lesson IDs
            $table->integer('current_position')->default(0);
            $table->enum('status', ['active', 'completed', 'paused'])->default('active');
            $table->decimal('mastery_score', 5, 2)->nullable();
            $table->json('ai_recommendations')->nullable(); // AI-generated recommendations
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adaptive_paths');
    }
};
