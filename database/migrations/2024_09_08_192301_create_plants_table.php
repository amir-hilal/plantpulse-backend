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
            $table->foreignId('garden_id')->constrained()->onDelete('cascade'); // links to gardens
            $table->string('name');
            $table->string('category');
            $table->integer('age'); // in days or weeks
            $table->text('important_note')->nullable();
            $table->date('last_watered')->nullable();
            $table->date('next_time_to_water')->nullable();
            $table->decimal('height', 5, 2)->nullable(); // height in cm
            $table->string('health_status')->default('healthy');
            $table->timestamps();
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
