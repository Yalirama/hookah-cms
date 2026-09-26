<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('flavor_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('external_id')->nullable()->unique()->after('id');
            $table->string('color')->nullable();
            $table->foreignId('flavor_category_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flavor_tags', function (Blueprint $table) {
            //
        });
    }
};
