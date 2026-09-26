<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_labels', function (Blueprint $table) {
            $table->enum('type', ['positive', 'neutral', 'negative'])->after('label_id');
        });
    }

    public function down(): void
    {
        Schema::table('guest_labels', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};