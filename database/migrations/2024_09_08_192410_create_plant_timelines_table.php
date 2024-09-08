<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plant_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained()->onDelete('cascade'); // links to plants
            $table->text('description')->nullable(); // user message
            $table->string('image_path')->nullable(); // image associated with the timeline event
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plant_timelines');
    }
};
