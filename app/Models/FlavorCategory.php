<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlavorCategory extends Model
{
    protected $fillable = ['name', 'color'];

    public function flavorTags()
    {
        return $this->hasMany(FlavorTag::class);
    }
}