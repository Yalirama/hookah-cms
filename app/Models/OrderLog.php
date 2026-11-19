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

    public function describeAction(): string
    {
        $st = $this->new_value['status'] ?? null;
        $lbl = $st ? (Order::statusVisualMap()[$st]['label'] ?? $st) : null;

        return match ($this->action) {
            'created' => 'Создание заказа',
            'status_changed' => $lbl ? "Статус: {$lbl}" : 'Смена статуса',
            'rolled_back' => $lbl ? "Откат на: {$lbl}" : 'Откат статуса',
            'serviced' => 'Обслуживание',
            'table_changed' => 'Смена стола',
            'item_added' => 'Добавление позиции',
            'item_removed' => 'Удаление позиции',
            'guest_changed' => 'Смена гостя',
            'guest_comment_changed' => 'Комментарий гостя',
            default => $this->action,
        };
    }
}
