<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_table_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('table_id')->constrained('tables');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('assigned_at');
            $table->timestamp('removed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_table_history');
    }
};