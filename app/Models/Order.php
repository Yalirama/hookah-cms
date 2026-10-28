<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'name', 'table_id', 'created_by', 'status',
        'guest_id', 'guest_comment', 'started_at', 'closed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'closed_at'  => 'datetime',
    ];

    // Автогенерация имени если не указано
    protected static function booted(): void
    {
        static::created(function (Order $order) {
            if (!$order->name) {
                $order->updateQuietly(['name' => 'Заказ #' . $order->id]);
            }
        });
    }

    public static function statusVisualMap(): array
    {
        return [
            'pending'     => ['card' => 'statusWait',   'top' => 'orderCreated', 'label' => 'Новый'],
            'in_progress' => ['card' => 'statusActive',  'top' => 'orderActive',  'label' => 'В работе'],
            'ready'       => ['card' => 'statusDone',    'top' => 'orderDone',    'label' => 'Готов'],
            'served'      => ['card' => 'statusActive',  'top' => 'orderActive',  'label' => 'У гостя'],
            'redo'        => ['card' => 'statusWait',    'top' => 'orderCreated', 'label' => 'Переделка'],
            'closed'      => ['card' => 'statusClose',   'top' => 'orderClosed',  'label' => 'Завершён'],
            'cancelled'   => ['card' => 'statusClose',   'top' => 'orderClosed',  'label' => 'Отменён'],
        ];
    }

    public function table()
    {
        return $this->belongsTo(Table::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class)->withTrashed();
    }

    public function logs()
    {
        return $this->hasMany(OrderLog::class)->orderBy('created_at');
    }

    public function tableHistory()
    {
        return $this->hasMany(OrderTableHistory::class)->orderBy('assigned_at');
    }

    public function isActive(): bool
    {
        return !in_array($this->status, ['closed', 'cancelled', 'redo']);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('started_at');
    }

    public function readyLog()
    {
        return $this->hasOne(OrderLog::class)
            ->where('action', 'status_changed')
            ->where('new_value->status', 'ready')
            ->latest('id');
    }
}