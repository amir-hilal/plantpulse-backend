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
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garden_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('category');
            $table->date('planted_date'); // Storing the planted date instead of age
            $table->string('important_note')->nullable();
            $table->date('last_watered')->nullable();
            $table->date('next_time_to_water')->nullable();
            $table->float('height')->nullable();
            $table->string('health_status');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->integer('watering_frequency');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plants');
    }
};
