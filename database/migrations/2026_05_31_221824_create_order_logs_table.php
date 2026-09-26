<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('action', [
                'created',
                'status_changed',
                'table_changed',
                'item_added',
                'item_removed',
                'rolled_back',
                'master_changed',
                'note_added',
                'redo_requested'
            ]);
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->string('comment')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_logs');
    }
};