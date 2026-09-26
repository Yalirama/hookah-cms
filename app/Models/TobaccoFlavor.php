<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TobaccoFlavor extends Model
{
    protected $fillable = ['tobacco_id', 'flavor_tag_id'];

    public function tobacco()
    {
        return $this->belongsTo(Tobacco::class);
    }

    public function flavorTag()
    {
        return $this->belongsTo(FlavorTag::class);
    }
}