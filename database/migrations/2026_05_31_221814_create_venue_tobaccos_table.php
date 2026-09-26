<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_tobaccos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tobacco_id')->constrained('tobaccos');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('tobacco_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_tobaccos');
    }
};