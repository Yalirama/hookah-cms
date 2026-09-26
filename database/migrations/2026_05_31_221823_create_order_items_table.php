<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('added_by')->constrained('users');
            $table->string('itemable_type'); // mix | tobacco
            $table->unsignedBigInteger('itemable_id');
            $table->decimal('grams', 6, 1)->nullable();
            $table->string('note')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['itemable_type', 'itemable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};