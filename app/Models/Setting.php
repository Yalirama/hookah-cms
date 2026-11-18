<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use \App\Models\Concerns\LogsAdminActions;

    protected $fillable = ['key', 'value', 'type', 'description'];

    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        self::$cache ??= static::all()->keyBy('key')->all();
        $setting = self::$cache[$key] ?? null;
        if (!$setting) return $default;

        return match($setting->type) {
            'boolean' => (bool) $setting->value,
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    public static function set(string $key, mixed $value): void
    {
        self::$cache = null;

        $data = ['value' => is_array($value) ? json_encode($value) : $value];

        if (is_array($value)) {
            $data['type'] = 'json';
        }

        static::updateOrCreate(['key' => $key], $data);
    }

    private const ADMIN_KEYS = [
        'venue_name' => 'Название заведения',
        'bowl_size' => 'Размер чаши по умолчанию',
        'stock_lead_days' => 'Срок поставки, дней',
        'stock_buffer_days' => 'Запас, дней',
        'stock_min_default' => 'Минимальный остаток по умолчанию, г',
        'stock_price_alert' => 'Порог роста закупочной цены, %',
        'work_hours' => 'Время работы заведения',
        'time_slots' => 'Временные слоты',
        'analytics_access' => 'Доступ к аналитике',
        'notifications_enabled' => 'Уведомления',
        'service_reminder_enabled' => 'Напоминание об обслуживании',
        'service_reminder_interval' => 'Интервал напоминания об обслуживании',
        'guest_new_threshold' => 'Порог гостя «Новый»',
        'guest_regular_threshold' => 'Порог гостя «Регулярный»',
        'guest_permanent_threshold' => 'Порог гостя «Постоянный»',
        'guest_vip_threshold' => 'Порог гостя «VIP»',
        'guest_inactive_days' => 'Дней до «давно не приходил»',
    ];

    public function adminEntity(): string
    {
        return 'Настройка';
    }

    public function adminLabel(): string
    {
        return self::ADMIN_KEYS[$this->key] ?? $this->key;
    }

    public function adminFields(): array
    {
        return ['value' => 'Значение'];
    }

    private function adminShow($v): string
    {
        if ($this->key === 'work_hours') {
            $a = json_decode((string) $v, true);
            return is_array($a) ? ($a['from'] ?? '—') . '–' . ($a['to'] ?? '—') : '—';
        }
        if ($this->key === 'time_slots') {
            $a = json_decode((string) $v, true);
            if (!is_array($a)) return '—';
            return collect($a)->map(fn ($s) => ($s['label'] ?? '') . ' ' . ($s['from'] ?? '') . '–' . ($s['to'] ?? ''))->implode(', ') ?: '—';
        }
        return $v === null || $v === '' ? '—' : (string) $v;
    }

    public function adminFieldText(string $field, $old, $new): ?string
    {
        if ($field !== 'value' || !isset(self::ADMIN_KEYS[$this->key])) return null;
        return 'Изменил «' . $this->adminLabel() . '»: ' . $this->adminShow($old) . ' → ' . $this->adminShow($new);
    }

    public function adminCreatedText(): string
    {
        return 'Задал «' . $this->adminLabel() . '»: ' . $this->adminShow($this->value);
    }
}
