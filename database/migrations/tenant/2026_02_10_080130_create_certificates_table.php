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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('certificate_template_id')->constrained()->onDelete('cascade');
            $table->string('certificate_number')->unique();
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('certificate_url')->nullable();
            $table->string('verification_code')->unique();
            $table->json('certificate_data')->nullable(); // ZenithaLMS certificate metadata
            $table->timestamps();
            
            $table->index(['user_id', 'issued_at']);
            $table->index(['verification_code']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
