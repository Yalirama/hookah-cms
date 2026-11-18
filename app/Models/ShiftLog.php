<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'started_at', 'ended_at'];

    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];

    public static function sync(User $user): void
    {
        $open = static::where('user_id', $user->id)->whereNull('ended_at')->latest('id')->first();
        if ($user->on_shift && !$open) {
            static::create(['user_id' => $user->id, 'started_at' => now()]);
        } elseif (!$user->on_shift && $open) {
            static::where('user_id', $user->id)->whereNull('ended_at')->update(['ended_at' => now()]);
        }
    }

    public static function closeFor(int $userId): void
    {
        static::where('user_id', $userId)->whereNull('ended_at')->update(['ended_at' => now()]);
    }
}
