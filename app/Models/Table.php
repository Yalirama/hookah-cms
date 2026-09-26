<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Table extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'type_id', 'capacity', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function type()
    {
        return $this->belongsTo(TableType::class, 'type_id')->withTrashed();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function tableHistory()
    {
        return $this->hasMany(OrderTableHistory::class);
    }
}