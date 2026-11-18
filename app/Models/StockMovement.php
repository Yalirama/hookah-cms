<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = ['tobacco_id', 'type', 'grams', 'cost', 'order_id', 'receipt_id', 'user_id', 'note'];

    protected $casts = ['created_at' => 'datetime', 'grams' => 'float'];

    public const TYPES = [
        'receipt' => 'Приход', 'reserve' => 'Резерв', 'unreserve' => 'Снятие резерва',
        'consume' => 'Расход', 'writeoff' => 'Списание', 'correction' => 'Корректировка',
    ];

    public function tobacco()
    {
        return $this->belongsTo(Tobacco::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
