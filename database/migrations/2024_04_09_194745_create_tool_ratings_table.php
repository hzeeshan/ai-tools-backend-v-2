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
        Schema::create('tool_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tool_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->foreign('tool_id')->references('id')->on('tools')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_ratings');
    }
};
