<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Table extends Model
{
    use \App\Models\Concerns\LogsAdminActions;

    use SoftDeletes;

    protected $fillable = ['name', 'type_id', 'capacity', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function type()
    {
        return $this->belongsTo(TableType::class, 'type_id')->withTrashed();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function tableHistory()
    {
        return $this->hasMany(OrderTableHistory::class);
    }

    public function adminEntity(): string
    {
        return 'Стол';
    }

    public function adminLabel(): string
    {
        return (string) $this->name;
    }

    public function adminFields(): array
    {
        return ['name' => 'Название', 'type_id' => 'Тип', 'capacity' => 'Вместимость', 'is_active' => 'Статус'];
    }

    public function adminFieldText(string $field, $old, $new): ?string
    {
        if ($field === 'is_active') return $new ? 'Включил стол' : 'Отключил стол';
        if ($field === 'type_id') return 'Тип: ' . (TableType::withTrashed()->find($old)?->name ?? '—') . ' → ' . (TableType::withTrashed()->find($new)?->name ?? '—');
        $labels = $this->adminFields();
        return isset($labels[$field]) ? $labels[$field] . ': ' . ($old ?: '—') . ' → ' . ($new ?: '—') : null;
    }
}
