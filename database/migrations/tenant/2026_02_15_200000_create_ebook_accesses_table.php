<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ebook_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('ebook_id');
            $table->integer('download_count')->default(0);
            $table->timestamp('access_granted_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ebook_id')->references('id')->on('ebooks')->onDelete('cascade');
            
            $table->unique(['user_id', 'ebook_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ebook_accesses');
    }
};
