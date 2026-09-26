<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('table_id')->constrained('tables');
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', [
                'pending',
                'in_progress',
                'ready',
                'served',
                'service',
                'redo',
                'closed',
                'cancelled'
            ])->default('pending');
            $table->text('guest_comment')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};