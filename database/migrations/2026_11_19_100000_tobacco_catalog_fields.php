<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tobacco_brands', function (Blueprint $t) {
            $t->string('source_slug')->nullable()->after('logo');
            $t->timestamp('synced_at')->nullable()->after('source_slug');
        });
        Schema::table('tobaccos', function (Blueprint $t) {
            $t->string('source_slug')->nullable()->after('strength');
            $t->boolean('is_manual')->default(false)->after('source_slug');
            $t->unsignedBigInteger('merged_into')->nullable()->after('is_manual');
            $t->index('source_slug');
        });
    }

    public function down(): void
    {
        Schema::table('tobaccos', function (Blueprint $t) {
            $t->dropIndex(['source_slug']);
            $t->dropColumn(['source_slug', 'is_manual', 'merged_into']);
        });
        Schema::table('tobacco_brands', fn (Blueprint $t) => $t->dropColumn(['source_slug', 'synced_at']));
    }
};
