<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mix_ingredient_alts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('mix_ingredients')->onDelete('cascade');
            $table->foreignId('tobacco_id')->constrained('tobaccos');
            $table->decimal('grams', 6, 1)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mix_ingredient_alts');
    }
};