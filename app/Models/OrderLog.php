<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'user_id', 'action',
        'old_value', 'new_value', 'comment', 'created_at',
    ];

    protected $casts = [
        'old_value'  => 'array',
        'new_value'  => 'array',
        'created_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}