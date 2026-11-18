<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id', 'added_by', 'itemable_type',
        'itemable_id', 'grams', 'price', 'fixed_percent', 'alt_choices', 'note',
    ];

    protected $casts = ['alt_choices' => 'array'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by')->withTrashed();
    }

    public function itemable()
    {
        return $this->morphTo()->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class);
    }
}