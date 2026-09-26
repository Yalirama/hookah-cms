<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->nullable()->after('name');
        });

        foreach (DB::table('users')->select('id', 'name')->get() as $user) {
            $parts = preg_split('/\s+/', trim($user->name), 2);

            DB::table('users')->where('id', $user->id)->update([
                'name'    => $parts[0] ?? $user->name,
                'surname' => $parts[1] ?? null,
            ]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('users')->select('id', 'name', 'surname')->get() as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'name' => trim($user->name . ' ' . $user->surname),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('surname');
        });
    }
};