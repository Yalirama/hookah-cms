<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained('guests')->onDelete('cascade');
            $table->foreignId('label_id')->constrained('labels')->onDelete('cascade');
            $table->foreignId('added_by')->constrained('users');
            $table->foreignId('removed_by')->nullable()->constrained('users');
            $table->timestamp('added_at');
            $table->timestamp('removed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_labels');
    }
};