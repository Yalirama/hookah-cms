<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $actions = [
        'created', 'status_changed', 'table_changed', 'item_added', 'item_removed', 'rolled_back',
        'master_changed', 'note_added', 'redo_requested', 'serviced', 'guest_changed', 'guest_comment_changed',
    ];

    public function up(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->timestamp('corrected_from')->nullable()->after('started_at');
        });

        $list = "'" . implode("','", [...$this->actions, 'time_corrected']) . "'";
        DB::statement("ALTER TABLE order_logs MODIFY action ENUM($list)");
    }

    public function down(): void
    {
        DB::table('order_logs')->where('action', 'time_corrected')->delete();
        $list = "'" . implode("','", $this->actions) . "'";
        DB::statement("ALTER TABLE order_logs MODIFY action ENUM($list)");

        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropColumn('corrected_from');
        });
    }
};
