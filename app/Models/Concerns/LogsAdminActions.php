<?php

namespace App\Models\Concerns;

use App\Models\AdminLog;

trait LogsAdminActions
{
    public static function bootLogsAdminActions(): void
    {
        static::created(function ($m) {
            if (in_array('created', $m->adminLogSkip ?? [])) return;
            AdminLog::record('created', $m->adminEntity(), $m->adminLabel(), $m->adminCreatedText());
        });

        static::updated(function ($m) {
            $parts = [];
            foreach ($m->getChanges() as $field => $new) {
                $text = $m->adminFieldText($field, $m->getOriginal($field), $new);
                if ($text) $parts[] = $text;
            }
            $parts = array_values(array_unique($parts));
            if ($parts) AdminLog::record('updated', $m->adminEntity(), $m->adminLabel(), implode('; ', $parts));
        });

        static::deleted(function ($m) {
            AdminLog::record('deleted', $m->adminEntity(), $m->adminLabel(), $m->adminDeletedText());
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($m) {
                AdminLog::record('restored', $m->adminEntity(), $m->adminLabel(), $m->adminRestoredText());
            });
        }
    }

    public function adminCreatedText(): string
    {
        return 'Создал: ' . mb_strtolower($this->adminEntity()) . ' «' . $this->adminLabel() . '»';
    }

    public function adminDeletedText(): string
    {
        return 'Удалил: ' . mb_strtolower($this->adminEntity()) . ' «' . $this->adminLabel() . '»';
    }

    public function adminRestoredText(): string
    {
        return 'Восстановил: ' . mb_strtolower($this->adminEntity()) . ' «' . $this->adminLabel() . '»';
    }

    public function adminFieldText(string $field, $old, $new): ?string
    {
        $labels = $this->adminFields();
        if (!isset($labels[$field])) return null;
        if ($field === 'is_active') return $new ? 'Активировал' : 'Деактивировал';
        $fmt = fn ($v) => $v === null || $v === '' ? '—' : (string) $v;
        return $labels[$field] . ': ' . $fmt($old) . ' → ' . $fmt($new);
    }
}
