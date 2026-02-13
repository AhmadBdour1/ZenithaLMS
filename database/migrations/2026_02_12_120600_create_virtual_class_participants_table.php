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
        Schema::create('virtual_class_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_class_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->string('status', 20)->default('active'); // active, left, kicked
            $table->timestamps();
            
            $table->unique(['virtual_class_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['virtual_class_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_class_participants');
    }
};
