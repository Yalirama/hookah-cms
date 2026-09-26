<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TableType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description'];

    public function tables()
    {
        return $this->hasMany(Table::class, 'type_id');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withTrashed()->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
    }
}