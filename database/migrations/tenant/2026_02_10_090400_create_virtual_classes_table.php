<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('virtual_classes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('instructor_id');
            $table->foreignId('course_id')->nullable();
            $table->timestamp('scheduled_at');
            $table->integer('duration_minutes');
            $table->string('meeting_link')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->integer('max_participants');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence_pattern')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->foreign('instructor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('virtual_classes');
    }
};
