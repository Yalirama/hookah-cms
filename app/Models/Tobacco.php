<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tobacco extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'brand_id', 'name', 'alt_name', 'description_short',
        'description_long', 'strength', 'source_slug', 'is_manual', 'merged_into',
    ];

    protected $casts = ['is_manual' => 'boolean'];

    public function brand()
    {
        return $this->belongsTo(TobaccoBrand::class, 'brand_id')->withTrashed();
    }

    public function flavorTags()
    {
        return $this->belongsToMany(FlavorTag::class, 'tobacco_flavors');
    }

    public function venueTobacco()
    {
        return $this->hasOne(VenueTobacco::class);
    }

    public function notes()
    {
        return $this->hasMany(TobaccoNote::class);
    }

    public function orderItems()
    {
        return $this->morphMany(OrderItem::class, 'itemable');
    }

    // Табак доступен в заведении?
    public function isInVenue(): bool
    {
        return $this->venueTobacco?->is_active ?? false;
    }
}