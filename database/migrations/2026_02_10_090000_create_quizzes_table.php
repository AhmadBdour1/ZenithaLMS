<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('course_id')->nullable();
            $table->foreignId('instructor_id');
            $table->integer('time_limit_minutes');
            $table->integer('passing_score');
            $table->integer('max_attempts');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false);
            $table->string('difficulty_level');
            $table->text('instructions')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('instructor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('quizzes');
    }
};
