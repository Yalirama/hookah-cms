<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'status', 'started_at', 'corrected_from'];

    protected $casts = [
        'started_at' => 'datetime',
        'corrected_from' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}