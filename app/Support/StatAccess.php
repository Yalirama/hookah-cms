<?php

namespace App\Support;

use App\Models\Setting;

class StatAccess
{
    public const GROUPS = [
        'time' => 'Время', 'guests' => 'Гости', 'tables' => 'Столы', 'items' => 'Табаки и миксы',
        'process' => 'Процесс', 'money' => 'Деньги', 'masters' => 'Мастера', 'legacy' => 'Главная аналитика',
    ];

    public const STATS = [
        'time_months'        => ['time', 'Динамика по месяцам и сезонам', 0],
        'time_monthdays'     => ['time', 'По дням месяца', 0],
        'time_hours'         => ['time', 'По часам', 0],
        'time_weekend'       => ['time', 'Выходные и будни', 0],
        'guests_return'      => ['guests', 'Возвраты гостей', 0],
        'guests_growth'      => ['guests', 'Рост базы гостей', 0],
        'guests_top'         => ['guests', 'Топ и ценные гости', 0],
        'guests_risk'        => ['guests', 'Гости в зоне риска', 0],
        'tables_load'        => ['tables', 'Загрузка столов', 0],
        'tables_moves'       => ['tables', 'Пересадки: рейтинг столов и причины', 0],
        'tables_flow'        => ['tables', 'Пересадки: откуда и куда', 0],
        'items_trend'        => ['items', 'Рост и падение позиций', 0],
        'items_pairs'        => ['items', 'Сочетания и переделки по табакам', 0],
        'items_dead'         => ['items', 'Мёртвые позиции', 0],
        'items_seasons'      => ['items', 'Вкусы по сезонам', 0],
        'items_comments'     => ['items', 'О чём просят в комментариях', 0],
        'process_time'       => ['process', 'Время в статусах', 0],
        'process_cancel'     => ['process', 'Отмены и причины', 0],
        'money_revenue'      => ['money', 'Выручка (суммы)', 0],
        'money_margin'       => ['money', 'Себестоимость и маржа миксов', 0],
        'money_prices'       => ['money', 'Динамика закупочных цен', 0],
        'money_losses'       => ['money', 'Потери: ревизии и списания', 0],
        'masters_page'       => ['masters', 'Страница мастеров', 0],
        'a_new_repeat'       => ['legacy', 'Новые и повторные гости', 1],
        'a_when_come'        => ['legacy', 'Когда приходят', 1],
        'a_heatmap'          => ['legacy', 'Загруженность по часам', 1],
        'a_top_mixes'        => ['legacy', 'Топ миксов', 1],
        'a_top_tobaccos'     => ['legacy', 'Топ табаков', 1],
        'a_top_brands'       => ['legacy', 'Топ брендов', 1],
        'a_top_masters'      => ['legacy', 'Топ мастеров', 1],
        'a_top_strength'     => ['legacy', 'Топ крепости', 1],
        'a_top_flavors'      => ['legacy', 'Топ вкусов', 1],
        'a_flavor_days'      => ['legacy', 'Вкусы по дням недели', 1],
        'a_top_tables'       => ['legacy', 'Топ места', 1],
        'a_table_moves'      => ['legacy', 'Пересадки со столов', 1],
        'a_inactive'         => ['legacy', 'Давно не приходили', 1],
    ];

    private static ?array $cfg = null;

    public static function config(): array
    {
        return self::$cfg ??= (array) (Setting::get('stat_access', []) ?: []);
    }

    public static function rule(string $key): array
    {
        $c = self::config()[$key] ?? null;
        if ($c && isset($c['mode'])) return ['mode' => $c['mode'], 'users' => array_map('intval', $c['users'] ?? [])];
        $legacy = (self::STATS[$key][2] ?? 0) === 1;
        return ['mode' => $legacy ? 'masters' : 'admin', 'users' => []];
    }

    public static function can(string $key, $user = null): bool
    {
        $user ??= auth()->user();
        if (!$user) return false;
        if ($user->hasRole('admin')) return true;
        $r = self::rule($key);
        return match ($r['mode']) {
            'masters' => true,
            'users'   => in_array($user->id, $r['users'], true),
            default   => false,
        };
    }

    public static function any(array $keys): bool
    {
        foreach ($keys as $k) if (self::can($k)) return true;
        return false;
    }

    public static function anyExtended(): bool
    {
        return self::any(array_keys(array_filter(self::STATS, fn ($v) => $v[2] === 0)));
    }

    public static function allowedIn(string $group): array
    {
        return array_keys(array_filter(self::STATS, fn ($v, $k) => $v[0] === $group && self::can($k), ARRAY_FILTER_USE_BOTH));
    }

    public static function save(array $input): void
    {
        $out = [];
        foreach (self::STATS as $key => $_) {
            $r = $input[$key] ?? null;
            if (!is_array($r) || !in_array($r['mode'] ?? '', ['admin', 'masters', 'users'], true)) continue;
            $out[$key] = ['mode' => $r['mode'], 'users' => $r['mode'] === 'users' ? array_values(array_map('intval', $r['users'] ?? [])) : []];
        }
        Setting::set('stat_access', $out ?: ['_' => ['mode' => 'admin', 'users' => []]]);
        self::$cfg = null;
    }
}
