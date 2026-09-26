<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mix_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mix_id')->constrained('mixes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->json('changed_fields')->nullable();
            $table->json('snapshot');
            $table->string('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mix_revisions');
    }
};