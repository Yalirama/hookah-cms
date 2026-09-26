<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_id')->constrained('labels')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('action', ['created', 'updated', 'note_added', 'note_deleted', 'activated', 'deactivated']);
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_logs');
    }
};