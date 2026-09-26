<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mix_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mix_id')->constrained('mixes')->onDelete('cascade');
            $table->foreignId('tobacco_id')->constrained('tobaccos');
            $table->decimal('grams', 6, 1)->default(0);
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mix_ingredients');
    }
};