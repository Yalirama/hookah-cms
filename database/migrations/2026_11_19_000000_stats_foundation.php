<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venue_tobaccos', fn (Blueprint $t) => $t->decimal('price', 10, 2)->nullable()->after('is_active'));
        Schema::table('order_items', fn (Blueprint $t) => $t->decimal('price', 10, 2)->nullable()->after('grams'));
        Schema::create('shift_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->timestamp('started_at');
            $t->timestamp('ended_at')->nullable();
            $t->index(['user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_logs');
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn('price'));
        Schema::table('venue_tobaccos', fn (Blueprint $t) => $t->dropColumn('price'));
    }
};
