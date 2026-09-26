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
        Schema::table('tobaccos', function (Blueprint $table) {
            $table->string('alt_name')->nullable()->after('name');
        });
        DB::statement('UPDATE tobaccos SET alt_name = description_short');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tobaccos', function (Blueprint $table) {
            //
        });
    }
};
