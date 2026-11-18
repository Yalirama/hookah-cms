<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueTobacco extends Model
{
    use \App\Models\Concerns\LogsAdminActions;

    protected $fillable = ['tobacco_id', 'is_active', 'price', 'deactivated_at', 'deactivated_by', 'deactivation_comment'];

    protected $casts = [
        'is_active'      => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    public function tobacco()
    {
        return $this->belongsTo(Tobacco::class)->withTrashed();
    }

    public function deactivatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'deactivated_by')->withTrashed();
    }

    public function logs()
    {
        return $this->hasMany(VenueTobaccoLog::class);
    }

    public function adminEntity(): string
    {
        return 'Табак';
    }

    public function adminLabel(): string
    {
        $t = $this->tobacco()->with('brand')->first();
        return $t ? trim(($t->brand->name ?? '') . ' ' . $t->name) : '#' . $this->tobacco_id;
    }

    public function adminFields(): array
    {
        return ['is_active' => 'Статус'];
    }

    public function adminCreatedText(): string
    {
        return 'Добавил табак в список заведения: «' . $this->adminLabel() . '»';
    }

    public function adminDeletedText(): string
    {
        return 'Убрал табак из списка заведения: «' . $this->adminLabel() . '»';
    }

    public function adminFieldText(string $field, $old, $new): ?string
    {
        if ($field !== 'is_active') return null;
        return $new ? 'Добавил табак в список (активировал)' : 'Убрал табак из списка (деактивировал)';
    }
}
