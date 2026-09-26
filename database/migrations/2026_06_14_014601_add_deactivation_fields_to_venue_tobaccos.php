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
        Schema::table('venue_tobaccos', function (Blueprint $table) {
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete()->after('deactivated_at');
            $table->string('deactivation_comment')->nullable()->after('deactivated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venue_tobaccos', function (Blueprint $table) {
            //
        });
    }
};
