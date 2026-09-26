<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_id')->constrained('labels')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->text('note');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_notes');
    }
};