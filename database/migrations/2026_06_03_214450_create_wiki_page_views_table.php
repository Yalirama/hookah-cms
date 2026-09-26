<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained('wiki_pages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('viewed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_views');
    }
};