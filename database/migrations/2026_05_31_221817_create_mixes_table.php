<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mixes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description_short')->nullable();
            $table->text('description_long')->nullable();
            $table->enum('strength', ['light', 'medium', 'strong'])->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mixes');
    }
};