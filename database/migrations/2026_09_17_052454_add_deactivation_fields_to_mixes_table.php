<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mixes', function (Blueprint $table) {
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users');
            $table->string('deactivation_comment')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mixes', function (Blueprint $table) {
            $table->dropForeign(['deactivated_by']);
            $table->dropColumn(['deactivated_at', 'deactivated_by', 'deactivation_comment']);
        });
    }
};