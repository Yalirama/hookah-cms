<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlavorTag extends Model
{
    protected $fillable = ['name', 'external_id', 'color', 'flavor_category_id'];

    public function tobaccos()
    {
        return $this->belongsToMany(Tobacco::class, 'tobacco_flavors');
    }

    public function category()
    {
        return $this->belongsTo(FlavorCategory::class, 'flavor_category_id');
    }
}