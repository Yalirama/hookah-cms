<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestLabel extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'guest_id', 'label_id', 'type', 'added_by', 'removed_by', 'added_at', 'removed_at',
    ];

    protected $casts = [
        'added_at'   => 'datetime',
        'removed_at' => 'datetime',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function label()
    {
        return $this->belongsTo(Label::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by')->withTrashed();
    }

    public function removedBy()
    {
        return $this->belongsTo(User::class, 'removed_by')->withTrashed();
    }

    public function getTypeLabelAttribute(): string
    {
        return ['positive' => 'Позитив', 'neutral' => 'Нейтрал', 'negative' => 'Негатив'][$this->type] ?? '—';
    }

    public function getTypeColorAttribute(): array
    {
        return [
            'positive' => ['#d4edda', '#155724'],
            'neutral'  => ['#e2e3e5', '#383d41'],
            'negative' => ['#f8d7da', '#721c24'],
        ][$this->type] ?? ['#eee', '#333'];
    }
}