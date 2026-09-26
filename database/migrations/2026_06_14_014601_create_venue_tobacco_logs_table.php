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
        Schema::create('venue_tobacco_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_tobacco_id')->constrained('venue_tobaccos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('action', ['activated', 'deactivated']);
            $table->string('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_tobacco_logs');
    }
};
