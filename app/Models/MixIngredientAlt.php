<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MixIngredientAlt extends Model
{
    protected $fillable = ['ingredient_id', 'tobacco_id', 'grams', 'unit'];

    public function ingredient()
    {
        return $this->belongsTo(MixIngredient::class, 'ingredient_id');
    }

    public function tobacco()
    {
        return $this->belongsTo(Tobacco::class)->withTrashed();
    }
}