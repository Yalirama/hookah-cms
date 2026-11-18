<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tobacco_receipts', fn (Blueprint $t) => $t->decimal('price_per_gram', 8, 2)->nullable()->after('grams'));
        Schema::table('tobacco_stocks', fn (Blueprint $t) => $t->decimal('avg_cost', 8, 2)->nullable()->after('supplier'));
        Schema::table('stock_movements', fn (Blueprint $t) => $t->decimal('cost', 10, 2)->nullable()->after('grams'));
        Schema::table('order_items', fn (Blueprint $t) => $t->json('alt_choices')->nullable()->after('fixed_percent'));
        Schema::table('mixes', fn (Blueprint $t) => $t->decimal('price', 10, 2)->nullable());

        Schema::create('stock_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tobacco_id')->constrained('tobaccos');
            $table->decimal('expected', 10, 1);
            $table->decimal('actual', 10, 1);
            $table->decimal('diff', 10, 1);
            $table->decimal('diff_cost', 10, 2)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_audits');
        Schema::table('mixes', fn (Blueprint $t) => $t->dropColumn('price'));
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn('alt_choices'));
        Schema::table('stock_movements', fn (Blueprint $t) => $t->dropColumn('cost'));
        Schema::table('tobacco_stocks', fn (Blueprint $t) => $t->dropColumn('avg_cost'));
        Schema::table('tobacco_receipts', fn (Blueprint $t) => $t->dropColumn('price_per_gram'));
    }
};
