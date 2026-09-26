<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTableHistory extends Model
{
    protected $table = 'order_table_history';

    public $timestamps = false;

    protected $fillable = [
        'order_id', 'table_id', 'user_id', 'assigned_at', 'removed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'removed_at'  => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}