<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TableType extends Model
{
    use \App\Models\Concerns\LogsAdminActions;

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

    public function adminEntity(): string
    {
        return 'Тип стола';
    }

    public function adminLabel(): string
    {
        return (string) $this->name;
    }

    public function adminFields(): array
    {
        return ['name' => 'Название', 'description' => 'Описание'];
    }

    public function adminDeletedText(): string
    {
        return 'Отключил тип стола «' . $this->name . '»';
    }

    public function adminRestoredText(): string
    {
        return 'Включил тип стола «' . $this->name . '»';
    }
}
