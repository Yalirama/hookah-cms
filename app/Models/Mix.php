<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mix extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'description_short', 'description_long',
        'strength', 'created_by', 'updated_by', 'is_active',
        'deactivated_at', 'deactivated_by', 'deactivation_comment',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }

    public function ingredients()
    {
        return $this->hasMany(MixIngredient::class);
    }

    public function revisions()
    {
        return $this->hasMany(MixRevision::class)->latest();
    }

    public function notes()
    {
        return $this->hasMany(MixNote::class);
    }

    public function orderItems()
    {
        return $this->morphMany(OrderItem::class, 'itemable');
    }

    public function flavorTagsFromIngredients()
    {
        $tobaccoIds = $this->ingredients()->withTrashed()->pluck('tobacco_id');
        return FlavorTag::whereHas('tobaccos', fn($q) => $q->whereIn('tobaccos.id', $tobaccoIds))->get();
    }

    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    public function logs()
    {
        return $this->hasMany(MixLog::class);
    }
}