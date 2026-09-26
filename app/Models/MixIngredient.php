<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MixIngredient extends Model
{
    use SoftDeletes;

    protected $fillable = ['mix_id', 'tobacco_id', 'grams', 'unit', 'sort_order'];

    public function mix()
    {
        return $this->belongsTo(Mix::class);
    }

    public function tobacco()
    {
        return $this->belongsTo(Tobacco::class)->withTrashed();
    }

    public function alternatives()
    {
        return $this->hasMany(MixIngredientAlt::class, 'ingredient_id');
    }
}