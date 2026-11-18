<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TobaccoStock extends Model
{
    protected $fillable = ['tobacco_id', 'on_hand', 'reserved', 'min_stock', 'pack_size', 'supplier', 'avg_cost'];

    protected $casts = ['on_hand' => 'float', 'reserved' => 'float', 'min_stock' => 'float', 'pack_size' => 'float'];

    public function tobacco()
    {
        return $this->belongsTo(Tobacco::class)->withTrashed();
    }

    public function getAvailableAttribute(): float
    {
        return round($this->on_hand - $this->reserved, 1);
    }
}
