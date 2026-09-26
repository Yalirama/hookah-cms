<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE order_logs MODIFY action ENUM(
            'created',
            'status_changed',
            'table_changed',
            'item_added',
            'item_removed',
            'rolled_back',
            'master_changed',
            'note_added',
            'redo_requested',
            'serviced',
            'guest_changed',
            'guest_comment_changed'
        )");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE order_logs MODIFY action ENUM(
            'created',
            'status_changed',
            'table_changed',
            'item_added',
            'item_removed',
            'rolled_back',
            'master_changed',
            'note_added',
            'redo_requested',
            'serviced',
            'guest_changed'
        )");
    }
};