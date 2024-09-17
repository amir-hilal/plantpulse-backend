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
        Schema::table('plant_timelines', function (Blueprint $table) {
            $table->string('source')->default('user'); // Could be 'user' or 'assistant'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant_timelines', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
