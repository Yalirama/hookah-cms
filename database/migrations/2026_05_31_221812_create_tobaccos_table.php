<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tobaccos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('tobacco_brands');
            $table->string('name');
            $table->string('description_short')->nullable();
            $table->text('description_long')->nullable();
            $table->enum('strength', ['light', 'medium', 'strong'])->default('medium');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tobaccos');
    }
};