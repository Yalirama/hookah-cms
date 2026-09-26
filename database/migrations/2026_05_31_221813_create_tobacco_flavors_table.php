<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tobacco_flavors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tobacco_id')->constrained('tobaccos')->onDelete('cascade');
            $table->foreignId('flavor_tag_id')->constrained('flavor_tags')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tobacco_flavors');
    }
};