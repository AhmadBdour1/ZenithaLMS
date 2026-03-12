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
        Schema::table('virtual_classes', function (Blueprint $table) {
            $table->integer('current_participants')->default(0)->after('max_participants');
            $table->string('status', 20)->default('scheduled')->after('current_participants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_classes', function (Blueprint $table) {
            $table->dropColumn(['current_participants', 'status']);
        });
    }
};
