<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained('guests')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('action', ['created', 'updated', 'note_added', 'note_deleted', 'status_changed']);
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_logs');
    }
};