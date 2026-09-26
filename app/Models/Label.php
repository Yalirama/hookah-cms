<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    protected $fillable = [
        'name', 'description', 'is_active', 'created_by',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function notes()
    {
        return $this->hasMany(LabelNote::class);
    }

    public function logs()
    {
        return $this->hasMany(LabelLog::class)->orderBy('created_at');
    }

    public function guestLabels()
    {
        return $this->hasMany(GuestLabel::class);
    }

    public function activeGuestLabels()
    {
        return $this->hasMany(GuestLabel::class)->whereNull('removed_at');
    }

    // Сколько раз тег висит в каждой категории
    public function countByType(string $type): int
    {
        return $this->activeGuestLabels()->where('type', $type)->count();
    }
}