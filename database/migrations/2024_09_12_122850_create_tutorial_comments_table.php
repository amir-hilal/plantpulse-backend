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
        Schema::create('tutorial_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tutorial_id');
            $table->unsignedBigInteger('user_id');
            $table->text('comment_text');
            $table->timestamps();

            // Foreign keys
            $table->foreign('tutorial_id')->references('id')->on('tutorials')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutorial_comments');
    }
};
