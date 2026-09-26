<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'guest_id', 'user_id', 'action', 'old_value', 'new_value', 'created_at',
    ];

    protected $casts = [
        'old_value'  => 'array',
        'new_value'  => 'array',
        'created_at' => 'datetime',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}