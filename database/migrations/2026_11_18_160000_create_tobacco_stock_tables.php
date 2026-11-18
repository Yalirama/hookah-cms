<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tobacco_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tobacco_id')->unique()->constrained('tobaccos');
            $table->decimal('on_hand', 10, 1)->default(0);
            $table->decimal('reserved', 10, 1)->default(0);
            $table->decimal('min_stock', 10, 1)->default(0);
            $table->decimal('pack_size', 8, 1)->default(25);
            $table->string('supplier')->nullable();
            $table->timestamps();
        });

        Schema::create('tobacco_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tobacco_id')->constrained('tobaccos');
            $table->decimal('grams', 10, 1);
            $table->string('supplier')->nullable();
            $table->date('received_at');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tobacco_id')->constrained('tobaccos');
            $table->string('type', 20);
            $table->decimal('grams', 10, 1);
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('receipt_id')->nullable()->constrained('tobacco_receipts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['tobacco_id', 'type']);
            $table->index('order_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('bowl_size', 5, 1)->nullable()->after('guest_comment');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('fixed_percent', 5, 1)->nullable()->after('grams');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn('fixed_percent'));
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('bowl_size'));
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('tobacco_receipts');
        Schema::dropIfExists('tobacco_stocks');
    }
};
