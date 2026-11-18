<?php

namespace App\Http\Controllers;

use App\Services\Stats\MasterStats;
use App\Services\Stats\StatsService;
use App\Support\Period;
use App\Support\StatAccess;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public const TABS = [
        'time' => ['Время', ['time_months', 'time_monthdays', 'time_hours', 'time_weekend']],
        'guests' => ['Гости', ['guests_return', 'guests_growth', 'guests_top', 'guests_risk']],
        'tables' => ['Столы', ['tables_load', 'tables_moves', 'tables_flow']],
        'items' => ['Позиции', ['items_trend', 'items_pairs', 'items_dead', 'items_seasons', 'items_comments']],
        'process' => ['Процесс', ['process_time', 'process_cancel']],
        'money' => ['Деньги', ['money_revenue', 'money_margin', 'money_prices', 'money_losses']],
        'masters' => ['Мастера', ['masters_page']],
    ];

    private function allowedTabs(): array
    {
        return array_keys(array_filter(self::TABS, fn ($t) => StatAccess::any($t[1])));
    }

    public function index(Request $r)
    {
        $tabs = $this->allowedTabs();
        abort_if(!$tabs, 403);
        $period = Period::fromRequest($r);
        $tab = in_array($r->get('tab'), $tabs, true) ? $r->get('tab') : $tabs[0];

        return view('stats.index', ['tabs' => $tabs, 'tab' => $tab, 'period' => $period, 'tabDefs' => self::TABS]);
    }

    public function tab(Request $r, string $tab, StatsService $svc, MasterStats $masters)
    {
        abort_unless(in_array($tab, $this->allowedTabs(), true), 403);
        $p = Period::fromRequest($r);
        $can = fn ($k) => StatAccess::can($k);
        $money = $can('money_revenue');
        $data = match ($tab) {
            'time' => $svc->time($p, $money),
            'guests' => $svc->guests($p, $money),
            'tables' => $svc->tables($p),
            'items' => $svc->itemsStats($p),
            'process' => $svc->process($p),
            'money' => $svc->money($p, ['revenue' => $can('money_revenue'), 'margin' => $can('money_margin'), 'prices' => $can('money_prices'), 'losses' => $can('money_losses')]),
            'masters' => $masters->build($p),
        };

        return view('stats.partials.' . $tab, ['d' => $data, 'p' => $p, 'money' => $money]);
    }
}
