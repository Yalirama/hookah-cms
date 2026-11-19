<?php

namespace App\Services\Stats;

use App\Models\Setting;
use App\Services\StockService;
use App\Support\Period;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatsService
{
    public const MONTHS = ['', 'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
    public const DOW = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 0 => 'Вс'];

    private ?array $names = null;
    private ?array $prices = null;
    private ?array $ingCache = null;

    public function __construct(private StockService $stock) {}

    private function c($v): Carbon
    {
        return $v instanceof Carbon ? $v : Carbon::parse($v);
    }

    private function status($json): ?string
    {
        return $json ? (json_decode($json, true)['status'] ?? null) : null;
    }

    private function pct($a, $b): ?float
    {
        return $b > 0 ? round(($a - $b) / $b * 100, 1) : null;
    }

    private function orders(Carbon $a, Carbon $b, bool $withCancelled = false): Collection
    {
        $q = DB::table('orders')->whereBetween('created_at', [$a, $b]);
        if (!$withCancelled) $q->where('status', '!=', 'cancelled');

        return $q->get();
    }

    private function itemNames(): array
    {
        if ($this->names) return $this->names;
        $n = [];
        foreach (DB::table('mixes')->get(['id', 'name']) as $m) $n['mix:' . $m->id] = $m->name;
        $brands = DB::table('tobacco_brands')->pluck('name', 'id');
        foreach (DB::table('tobaccos')->get(['id', 'name', 'brand_id']) as $t) $n['tobacco:' . $t->id] = trim(($brands[$t->brand_id] ?? '') . ' ' . $t->name);

        return $this->names = $n;
    }

    private function tname($id): string
    {
        return $this->itemNames()['tobacco:' . $id] ?? ('Табак #' . $id);
    }

    private function priceMap(): array
    {
        if ($this->prices) return $this->prices;
        $p = [];
        foreach (DB::table('mixes')->whereNotNull('price')->get(['id', 'price']) as $m) $p['mix:' . $m->id] = (float) $m->price;
        foreach (DB::table('venue_tobaccos')->whereNotNull('price')->get(['tobacco_id', 'price']) as $t) $p['tobacco:' . $t->tobacco_id] = (float) $t->price;

        return $this->prices = $p;
    }

    private function key($type, $id): string
    {
        return (str_contains($type, 'Mix') ? 'mix' : 'tobacco') . ':' . $id;
    }

    public function items(Collection $orderIds): Collection
    {
        if ($orderIds->isEmpty()) return collect();
        $default = (float) (Setting::get('default_item_price') ?: 0);
        $map = $this->priceMap();

        return DB::table('order_items')->whereNull('deleted_at')->whereIn('order_id', $orderIds->all())->get()->map(function ($i) use ($map, $default) {
            $i->key = $this->key($i->itemable_type, $i->itemable_id);
            $i->eff = $i->price !== null ? (float) $i->price : ($map[$i->key] ?? ($default ?: 0));

            return $i;
        });
    }

    private function revenueByOrder(Collection $orders): array
    {
        $closed = $orders->where('status', 'closed')->pluck('id');

        return $this->items($closed)->groupBy('order_id')->map(fn ($g) => $g->sum('eff'))->all();
    }

    private function ingredients(): array
    {
        if ($this->ingCache) return $this->ingCache;
        $r = [];
        foreach (DB::table('mix_ingredients')->whereNull('deleted_at')->orderBy('sort_order')->get() as $i) $r[$i->mix_id][] = $i;

        return $this->ingCache = $r;
    }

    private function tobaccosOf($item): array
    {
        if (str_starts_with($item->key, 'tobacco:')) return [(int) $item->itemable_id];

        return array_values(array_unique(array_map(fn ($i) => (int) $i->tobacco_id, $this->ingredients()[$item->itemable_id] ?? [])));
    }

    /* ---------------- ВРЕМЯ ---------------- */

    public function time(Period $p, bool $money): array
    {
        $now = now();
        $from24 = $now->copy()->startOfMonth()->subMonths(23);
        $all = $this->orders($from24, $now->copy()->endOfMonth());
        $rev = $money ? $this->revenueByOrder($all) : [];
        $agg = function ($set) use ($rev, $money) {
            return ['orders' => $set->count(), 'guests' => $set->whereNotNull('guest_id')->pluck('guest_id')->unique()->count(),
                'revenue' => $money ? round($set->sum(fn ($o) => $rev[$o->id] ?? 0)) : null];
        };

        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = $now->copy()->startOfMonth()->subMonths($i);
            $y = $m->copy()->subYear();
            $cur = $agg($all->filter(fn ($o) => $this->c($o->created_at)->isSameMonth($m)));
            $prev = $agg($all->filter(fn ($o) => $this->c($o->created_at)->isSameMonth($y)));
            $months[] = ['label' => self::MONTHS[$m->month] . ' ' . $m->format('y'), 'cur' => $cur, 'prev' => $prev,
                'dOrders' => $this->pct($cur['orders'], $prev['orders']), 'dGuests' => $this->pct($cur['guests'], $prev['guests']),
                'dRevenue' => $money ? $this->pct($cur['revenue'], $prev['revenue']) : null];
        }

        $everything = $this->orders(Carbon::create(2000), $now->copy()->endOfDay());
        $revAll = $money ? $this->revenueByOrder($everything) : [];
        $seasons = [];
        foreach (['Зима', 'Весна', 'Лето', 'Осень'] as $s) {
            $set = $everything->filter(fn ($o) => Grouper::season($this->c($o->created_at)->month) === $s);
            $mc = $set->map(fn ($o) => $this->c($o->created_at)->format('Y-m'))->unique()->count();
            $seasons[] = ['label' => $s, 'orders' => $set->count(), 'perMonth' => $mc ? round($set->count() / $mc, 1) : 0, 'months' => $mc,
                'guests' => $set->whereNotNull('guest_id')->pluck('guest_id')->unique()->count(),
                'revenue' => $money ? round($set->sum(fn ($o) => $revAll[$o->id] ?? 0)) : null];
        }

        $per = $this->orders($p->from, $p->to);
        $revP = $money ? $this->revenueByOrder($per) : [];
        $days = array_fill(1, 31, ['orders' => 0, 'revenue' => 0]);
        $hours = array_fill(0, 24, ['orders' => 0, 'revenue' => 0]);
        $dowAgg = [];
        $heatRaw = [];
        foreach ($per as $o) {
            $d = $this->c($o->created_at);
            $r = $revP[$o->id] ?? 0;
            $days[$d->day]['orders']++; $days[$d->day]['revenue'] += $r;
            $hours[$d->hour]['orders']++; $hours[$d->hour]['revenue'] += $r;
            $dow = $d->dayOfWeek;
            $heatRaw[$dow][$d->hour] = ($heatRaw[$dow][$d->hour] ?? 0) + 1;
            $dowAgg[$dow]['orders'] = ($dowAgg[$dow]['orders'] ?? 0) + 1;
            $dowAgg[$dow]['revenue'] = ($dowAgg[$dow]['revenue'] ?? 0) + $r;
            $dowAgg[$dow]['dates'][$d->toDateString()] = 1;
        }
        $dowRows = [];
        foreach (self::DOW as $n => $l) {
            $a = $dowAgg[$n] ?? ['orders' => 0, 'revenue' => 0, 'dates' => []];
            $dc = count($a['dates'] ?? []);
            $dowRows[] = ['label' => $l, 'weekend' => in_array($n, [0, 6]), 'orders' => $a['orders'], 'days' => $dc, 'avg' => $dc ? round($a['orders'] / $dc, 1) : 0,
                'avgRevenue' => $money && $dc ? round($a['revenue'] / $dc) : null, 'revenue' => $a['revenue']];
        }
        $wkRows = [];
        foreach ([[false, 'Будни (Пн–Пт)'], [true, 'Выходные (Сб–Вс)']] as [$w, $label]) {
            $rows = collect($dowRows)->where('weekend', $w);
            $dd = $rows->sum('days');
            $wkRows[] = ['label' => $label, 'orders' => $rows->sum('orders'), 'days' => $dd, 'avg' => $dd ? round($rows->sum('orders') / $dd, 1) : 0,
                'avgRevenue' => $money && $dd ? round($rows->sum('revenue') / $dd) : null];
        }

        $heatRows = [];
        $heatMax = 0;
        foreach ([1, 2, 3, 4, 5, 6, 0] as $n) {
            $cells = [];
            for ($h = 0; $h < 24; $h++) {
                $v = $heatRaw[$n][$h] ?? 0;
                $heatMax = max($heatMax, $v);
                $cells[] = $v;
            }
            $heatRows[] = ['label' => self::DOW[$n], 'cells' => $cells];
        }
        $heat = ['rows' => $heatRows, 'max' => $heatMax];

        return compact('months', 'seasons', 'days', 'hours', 'dowRows', 'wkRows', 'heat');
    }

    /* ---------------- ГОСТИ ---------------- */

    public function guests(Period $p, bool $money): array
    {
        $now = now();
        $names = DB::table('guests')->pluck('name', 'id');
        $orders = DB::table('orders')->whereNotNull('guest_id')->where('status', '!=', 'cancelled')->orderBy('created_at')->get();
        $by = $orders->groupBy('guest_id')->map(fn ($g) => $g->map(fn ($o) => $this->c($o->created_at)->startOfDay())->unique(fn ($d) => $d->toDateString())->values());

        $cohort = $by->filter(fn ($d) => $d->first()->betweenIncluded($p->from->copy()->startOfDay(), $p->to));
        $ret = [];
        foreach ([30, 90] as $w) {
            $elig = $cohort->filter(fn ($d) => $d->first()->lte($now->copy()->subDays($w)));
            $back = $elig->filter(fn ($d) => $d->count() > 1 && abs($d[1]->diffInDays($d[0])) <= $w);
            $ret[$w] = ['eligible' => $elig->count(), 'back' => $back->count(), 'pct' => $elig->count() ? round($back->count() / $elig->count() * 100) : null];
        }
        $gaps = $cohort->filter(fn ($d) => $d->count() > 1)->map(fn ($d) => abs($d[1]->diffInDays($d[0])));
        $avgBack = $gaps->count() ? round($gaps->avg(), 1) : null;

        $weeks = [];
        for ($i = 11; $i >= 0; $i--) {
            $s = $now->copy()->startOfWeek()->subWeeks($i);
            $weeks[] = ['label' => $s->format('d.m'), 'n' => DB::table('guests')->whereBetween('created_at', [$s, $s->copy()->endOfWeek()])->count()];
        }
        $monthsG = [];
        for ($i = 11; $i >= 0; $i--) {
            $s = $now->copy()->startOfMonth()->subMonths($i);
            $monthsG[] = ['label' => self::MONTHS[$s->month], 'n' => DB::table('guests')->whereBetween('created_at', [$s, $s->copy()->endOfMonth()])->count()];
        }
        $totalGuests = DB::table('guests')->count();

        $revAll = $money ? $this->revenueByOrder($orders) : [];
        $perOrders = $orders->filter(fn ($o) => $this->c($o->created_at)->betweenIncluded($p->from, $p->to));
        $top = $perOrders->groupBy('guest_id')->map(fn ($g, $id) => [
            'id' => $id, 'name' => $names[$id] ?? 'Гость #' . $id, 'orders' => $g->count(), 'last' => $this->c($g->max('created_at'))->format('d.m'),
            'revenue' => $money ? round($g->sum(fn ($o) => $revAll[$o->id] ?? 0)) : null,
        ])->sortByDesc('orders')->take(10)->values();

        $labelsSt = ['vip' => 'VIP', 'permanent' => 'Постоянный', 'regular' => 'Регулярный'];
        $valuable = \App\Models\Guest::withCount(['orders as closed_orders_count' => fn ($q) => $q->where('status', 'closed')])->get()
            ->filter(fn ($g) => in_array($g->status, ['vip', 'permanent']))->sortByDesc('closed_orders_count')->take(15)
            ->map(function ($g) use ($by, $now, $labelsSt, $money, $orders, $revAll) {
                $d = $by[$g->id] ?? collect();
                $last = $d->last();

                return ['id' => $g->id, 'name' => $g->name, 'status' => $labelsSt[$g->status] ?? $g->status, 'visits' => $g->closed_orders_count, 'last' => $last ? $last->format('d.m.Y') : '—',
                    'ago' => $last ? (int) abs($last->diffInDays($now)) : null, 'revenue' => $money ? round($orders->where('guest_id', $g->id)->sum(fn ($o) => $revAll[$o->id] ?? 0)) : null];
            })->values();
        $thresholds = [(int) Setting::get('guest_vip_threshold', 400), (int) Setting::get('guest_permanent_threshold', 50)];

        $risk = $by->map(function ($d, $id) use ($names, $now) {
            if ($d->count() < 3) return null;
            $avg = abs($d->first()->diffInDays($d->last())) / ($d->count() - 1);
            $ago = (int) abs($d->last()->diffInDays($now));
            if ($ago <= max(21, $avg * 2.5)) return null;

            return ['id' => $id, 'name' => $names[$id] ?? 'Гость #' . $id, 'visits' => $d->count(), 'avg' => round($avg, 1), 'ago' => $ago, 'last' => $d->last()->format('d.m.Y')];
        })->filter()->sortByDesc('visits')->take(30)->values();

        return compact('ret', 'avgBack', 'weeks', 'monthsG', 'totalGuests', 'top', 'valuable', 'risk', 'thresholds') + ['cohort' => $cohort->count()];
    }

    /* ---------------- СТОЛЫ ---------------- */

    private function union(array $iv): int
    {
        usort($iv, fn ($a, $b) => $a[0] <=> $b[0]);
        $tot = 0; $cs = null; $ce = null;
        foreach ($iv as [$s, $e]) {
            if ($e <= $s) continue;
            if ($cs === null) { $cs = $s; $ce = $e; continue; }
            if ($s <= $ce) { $ce = max($ce, $e); } else { $tot += $ce - $cs; $cs = $s; $ce = $e; }
        }
        if ($cs !== null) $tot += $ce - $cs;

        return $tot;
    }

    public function tables(Period $p): array
    {
        $tables = DB::table('tables')->get()->keyBy('id');
        $types = DB::table('table_types')->pluck('name', 'id');
        $tname = fn ($id) => $tables[$id]->name ?? ('Стол #' . $id);
        $orders = $this->orders($p->from, $p->to)->keyBy('id');
        $hist = DB::table('order_table_history')->whereIn('order_id', $orders->keys()->all())->get()->groupBy('order_id');
        $nowTs = now()->timestamp;

        $tbl = []; $typeAgg = [];
        foreach ($orders as $o) {
            $startTs = $this->c($o->started_at ?? $o->created_at)->timestamp;
            $end = $o->closed_at ? $this->c($o->closed_at)->timestamp : min($nowTs, $startTs + 7200);
            $rows = $hist[$o->id] ?? collect();
            $segs = [];
            if ($rows->isEmpty() && $o->table_id) $segs[] = [$o->table_id, $startTs, min($end, $startTs + 43200)];
            foreach ($rows as $h) {
                $s = $this->c($h->assigned_at)->timestamp;
                $e = $h->removed_at ? $this->c($h->removed_at)->timestamp : $end;
                $segs[] = [$h->table_id, $s, min(max($e, $s), $s + 43200)];
            }
            foreach ($segs as [$tid, $s, $e]) {
                $tbl[$tid]['iv'][] = [$s, $e];
                $tbl[$tid]['orders'][$o->id] = 1;
            }
            if ($o->status === 'closed' && $o->closed_at && $o->table_id) {
                $len = ($end - $startTs) / 60;
                if ($len > 0 && $len < 600) $typeAgg[$tables[$o->table_id]->type_id ?? 0][] = $len;
            }
        }
        $load = [];
        foreach ($tbl as $tid => $a) {
            $secs = $this->union($a['iv']);
            $load[] = ['name' => $tname($tid), 'type' => $types[$tables[$tid]->type_id ?? 0] ?? '—', 'orders' => count($a['orders']), 'hours' => round($secs / 3600, 1),
                'avgMin' => count($a['orders']) ? round($secs / 60 / count($a['orders'])) : 0];
        }
        usort($load, fn ($a, $b) => $b['hours'] <=> $a['hours']);
        $typeRows = [];
        foreach ($typeAgg as $tid => $arr) $typeRows[] = ['name' => $types[$tid] ?? '—', 'orders' => count($arr), 'avgMin' => round(array_sum($arr) / count($arr))];
        usort($typeRows, fn ($a, $b) => $b['avgMin'] <=> $a['avgMin']);

        $moves = DB::table('order_logs')->where('action', 'table_changed')->whereBetween('created_at', [$p->from, $p->to])->get();
        $touched = [];
        foreach ($tbl as $tid => $a) $touched[$tid] = count($a['orders']);
        $rating = []; $flow = []; $reasonTotal = [];
        foreach ($moves as $m) {
            $from = (int) (json_decode($m->old_value, true)['table_id'] ?? 0);
            $to = (int) (json_decode($m->new_value, true)['table_id'] ?? 0);
            $reasons = Grouper::reasons($m->comment);
            $rating[$from]['orders'][$m->order_id] = 1;
            $fk = $from . '>' . $to;
            $flow[$fk]['n'] = ($flow[$fk]['n'] ?? 0) + 1;
            foreach ($reasons as $r) {
                $rating[$from]['reasons'][$r] = ($rating[$from]['reasons'][$r] ?? 0) + 1;
                $flow[$fk]['reasons'][$r] = ($flow[$fk]['reasons'][$r] ?? 0) + 1;
                $reasonTotal[$r] = ($reasonTotal[$r] ?? 0) + 1;
            }
        }
        $ratingRows = [];
        foreach ($rating as $tid => $a) {
            $base = max($touched[$tid] ?? 0, count($a['orders']));
            arsort($a['reasons']);
            $ratingRows[] = ['name' => $tname($tid), 'moved' => count($a['orders']), 'total' => $base, 'pct' => $base ? round(count($a['orders']) / $base * 100) : 0, 'reasons' => array_slice($a['reasons'], 0, 3, true)];
        }
        usort($ratingRows, fn ($a, $b) => [$b['pct'], $b['moved']] <=> [$a['pct'], $a['moved']]);
        $flowRows = [];
        foreach ($flow as $k => $a) {
            [$f, $t] = explode('>', $k);
            arsort($a['reasons']);
            $flowRows[] = ['from' => $tname($f), 'to' => $tname($t), 'n' => $a['n'], 'reason' => array_key_first($a['reasons'])];
        }
        usort($flowRows, fn ($a, $b) => $b['n'] <=> $a['n']);
        arsort($reasonTotal);

        return ['load' => $load, 'types' => $typeRows, 'rating' => $ratingRows, 'flow' => array_slice($flowRows, 0, 10), 'reasons' => $reasonTotal, 'movesTotal' => $moves->count(), 'ordersTotal' => $orders->count()];
    }

    /* ---------------- ТОВАРЫ ---------------- */

    private function counts(Carbon $a, Carbon $b): array
    {
        $o = $this->orders($a, $b)->pluck('id');
        $out = [];
        foreach ($this->items($o) as $i) $out[$i->key] = ($out[$i->key] ?? 0) + 1;

        return $out;
    }

    public function itemsStats(Period $p): array
    {
        $now = now();
        $names = $this->itemNames();
        $cur = $this->counts($p->from, $p->to);
        $prev = $p->prevFrom ? $this->counts($p->prevFrom, $p->prevTo) : [];
        $yoy = $this->counts($p->yoyFrom, $p->yoyTo);
        $rows = [];
        foreach (array_unique(array_merge(array_keys($cur), array_keys($prev))) as $k) {
            $c = $cur[$k] ?? 0; $pr = $prev[$k] ?? 0;
            if ($c + $pr < 2) continue;
            $rows[] = ['name' => $names[$k] ?? $k, 'kind' => str_starts_with($k, 'mix') ? 'Микс' : 'Табак', 'cur' => $c, 'prev' => $pr, 'yoy' => $yoy[$k] ?? 0, 'delta' => $c - $pr];
        }
        usort($rows, fn ($a, $b) => $b['delta'] <=> $a['delta']);
        $grow = array_values(array_filter(array_slice($rows, 0, 6), fn ($r) => $r['delta'] > 0));
        $fall = array_values(array_filter(array_slice(array_reverse($rows), 0, 6), fn ($r) => $r['delta'] < 0));

        $orders = $this->orders($p->from, $p->to);
        $redoSet = DB::table('order_logs')->where('action', 'status_changed')->where('new_value', 'like', '%redo%')->whereIn('order_id', $orders->pluck('id')->all())->pluck('order_id')->unique()->flip();
        $items = $this->items($orders->pluck('id'));
        $tob = []; $pair = [];
        foreach ($items->groupBy('order_id') as $oid => $its) {
            $redo = isset($redoSet[$oid]);
            foreach ($its as $it) {
                $ids = $this->tobaccosOf($it);
                foreach ($ids as $a) {
                    $tob[$a]['n'] = ($tob[$a]['n'] ?? 0) + 1;
                    $tob[$a]['redo'] = ($tob[$a]['redo'] ?? 0) + ($redo ? 1 : 0);
                    foreach ($ids as $b) {
                        if ($a === $b) continue;
                        $pair[$a][$b]['n'] = ($pair[$a][$b]['n'] ?? 0) + 1;
                        $pair[$a][$b]['redo'] = ($pair[$a][$b]['redo'] ?? 0) + ($redo ? 1 : 0);
                    }
                }
            }
        }
        $mp = [];
        foreach ($items->groupBy('order_id') as $oid => $its) {
            $ms = $its->filter(fn ($x) => str_starts_with($x->key, 'mix:'))->pluck('itemable_id')->unique()->values()->all();
            foreach ($ms as $a) foreach ($ms as $b) {
                if ($a >= $b) continue;
                $mp[$a . '-' . $b]['n'] = ($mp[$a . '-' . $b]['n'] ?? 0) + 1;
                $mp[$a . '-' . $b]['redo'] = ($mp[$a . '-' . $b]['redo'] ?? 0) + (isset($redoSet[$oid]) ? 1 : 0);
            }
        }
        $mixPairs = collect($mp)->map(function ($v, $k) use ($names) {
            [$a, $b] = explode('-', $k);

            return ['a' => $names['mix:' . $a] ?? $a, 'b' => $names['mix:' . $b] ?? $b, 'n' => $v['n'], 'redoPct' => round($v['redo'] / $v['n'] * 100)];
        })->sortByDesc('n')->take(6)->values()->all();
        uasort($tob, fn ($a, $b) => $b['n'] <=> $a['n']);
        $pairs = [];
        foreach (array_slice($tob, 0, 8, true) as $id => $t) {
            $partners = collect($pair[$id] ?? [])->map(fn ($v, $b) => ['name' => $this->tname($b), 'n' => $v['n'], 'redoPct' => $v['n'] ? round($v['redo'] / $v['n'] * 100) : 0]);
            $pairs[] = ['name' => $this->tname($id), 'n' => $t['n'], 'redoPct' => $t['n'] ? round($t['redo'] / $t['n'] * 100) : 0,
                'top' => $partners->sortByDesc('n')->take(3)->values()->all(),
                'worst' => $partners->filter(fn ($x) => $x['n'] >= 2 && $x['redoPct'] > 0)->sortByDesc('redoPct')->take(2)->values()->all()];
        }

        $last = [];
        $rowsAll = DB::table('order_items')->whereNull('order_items.deleted_at')->join('orders', 'orders.id', '=', 'order_items.order_id')->where('orders.status', '!=', 'cancelled')
            ->get(['order_items.itemable_type', 'order_items.itemable_id', 'orders.created_at']);
        foreach ($rowsAll as $r) {
            $it = (object) ['key' => $this->key($r->itemable_type, $r->itemable_id), 'itemable_id' => $r->itemable_id];
            $ts = $this->c($r->created_at);
            $ids = str_starts_with($it->key, 'mix') ? [$it->key] : [];
            foreach ($this->tobaccosOf($it) as $t) $ids[] = 'tobacco:' . $t;
            foreach (array_unique($ids) as $k) if (!isset($last[$k]) || $ts->gt($last[$k])) $last[$k] = $ts;
        }
        $dead = ['30' => [], '60' => [], '90' => []];
        $active = [];
        foreach (DB::table('mixes')->where('is_active', true)->whereNull('deleted_at')->pluck('id') as $id) $active[] = 'mix:' . $id;
        foreach (DB::table('venue_tobaccos')->where('is_active', true)->pluck('tobacco_id') as $id) $active[] = 'tobacco:' . $id;
        foreach ($active as $k) {
            $l = $last[$k] ?? null;
            $days = $l ? (int) abs($l->diffInDays($now)) : null;
            foreach ([90, 60, 30] as $w) {
                if ($days === null || $days >= $w) { $dead[(string) $w][] = ['name' => $names[$k] ?? $k, 'kind' => str_starts_with($k, 'mix') ? 'Микс' : 'Табак', 'days' => $days]; break; }
            }
        }
        foreach ($dead as $w => $arr) { usort($arr, fn ($a, $b) => ($b['days'] ?? 9999) <=> ($a['days'] ?? 9999)); $dead[$w] = $arr; }

        $flav = DB::table('tobacco_flavors')->join('flavor_tags', 'flavor_tags.id', '=', 'tobacco_flavors.flavor_tag_id')->get(['tobacco_flavors.tobacco_id', 'flavor_tags.name'])->groupBy('tobacco_id')->map(fn ($g) => $g->pluck('name')->all());
        $everything = $this->orders(Carbon::create(2000), $now->copy()->endOfDay());
        $allItems = $this->items($everything->pluck('id'))->groupBy('order_id');
        $season = ['Зима' => [], 'Весна' => [], 'Лето' => [], 'Осень' => []];
        foreach ($everything as $o) {
            $s = Grouper::season($this->c($o->created_at)->month);
            foreach ($allItems[$o->id] ?? [] as $it) foreach ($this->tobaccosOf($it) as $t) foreach ($flav[$t] ?? [] as $f) $season[$s][$f] = ($season[$s][$f] ?? 0) + 1;
        }
        foreach ($season as $s => $a) { arsort($a); $season[$s] = array_slice($a, 0, 6, true); }

        $cm = DB::table('orders')->whereNotNull('guest_comment')->where('guest_comment', '!=', '')->get();
        $bySeason = []; $inPeriod = [];
        foreach ($cm as $o) {
            $g = Grouper::match($o->guest_comment, Grouper::FLAVORS) ?: ['Другое'];
            $s = Grouper::season($this->c($o->created_at)->month);
            $in = $this->c($o->created_at)->betweenIncluded($p->from, $p->to);
            foreach ($g as $x) {
                $bySeason[$x][$s] = ($bySeason[$x][$s] ?? 0) + 1;
                if ($in) $inPeriod[$x] = ($inPeriod[$x] ?? 0) + 1;
            }
        }
        $commentRows = [];
        foreach ($bySeason as $g => $a) $commentRows[] = ['name' => $g, 'period' => $inPeriod[$g] ?? 0, 'Зима' => $a['Зима'] ?? 0, 'Весна' => $a['Весна'] ?? 0, 'Лето' => $a['Лето'] ?? 0, 'Осень' => $a['Осень'] ?? 0, 'total' => array_sum($a)];
        usort($commentRows, fn ($a, $b) => $b['total'] <=> $a['total']);
        $weekRows = [];
        for ($i = 7; $i >= 0; $i--) {
            $ws = $now->copy()->startOfWeek()->subWeeks($i); $we = $ws->copy()->endOfWeek();
            $cnt = [];
            foreach ($cm as $o) {
                if (!$this->c($o->created_at)->betweenIncluded($ws, $we)) continue;
                foreach (Grouper::match($o->guest_comment, Grouper::FLAVORS) ?: ['Другое'] as $x) $cnt[$x] = ($cnt[$x] ?? 0) + 1;
            }
            arsort($cnt);
            $weekRows[] = ['label' => $ws->format('d.m') . '–' . $we->format('d.m'), 'top' => array_slice($cnt, 0, 3, true), 'n' => array_sum($cnt)];
        }
        $recent = $cm->filter(fn ($o) => $this->c($o->created_at)->betweenIncluded($p->from, $p->to))->sortByDesc('created_at')->take(8)
            ->map(fn ($o) => ['text' => $o->guest_comment, 'when' => $this->c($o->created_at)->format('d.m H:i'), 'tags' => Grouper::match($o->guest_comment, Grouper::FLAVORS)])->values()->all();

        return compact('grow', 'fall', 'pairs', 'mixPairs', 'dead', 'season', 'commentRows', 'weekRows', 'recent') + ['hasPrev' => (bool) $p->prevFrom];
    }

    /* ---------------- ПРОЦЕСС ---------------- */

    public function process(Period $p): array
    {
        $orders = $this->orders($p->from, $p->to, true)->keyBy('id');
        $hist = DB::table('order_status_histories')->whereIn('order_id', $orders->keys()->all())->orderBy('started_at')->get()->groupBy('order_id');
        $labels = ['pending' => 'Новый', 'in_progress' => 'В работе', 'ready' => 'Готов', 'served' => 'Подан', 'redo' => 'Переделка', 'closed' => 'Закрыт', 'cancelled' => 'Отменён'];
        $dur = []; $long = []; $speed = [];
        foreach ($orders as $o) {
            $h = ($hist[$o->id] ?? collect())->values();
            if ($h->isEmpty()) continue;
            $d = abs($this->c($h->first()->started_at)->diffInSeconds($this->c($o->created_at)));
            $dur['pending'][] = $d; $long[] = ['order' => $o->id, 'status' => 'Новый', 'min' => round($d / 60)];
            foreach ($h as $i => $row) {
                if (in_array($row->status, ['closed', 'cancelled'])) continue;
                $next = $h[$i + 1] ?? null;
                $end = $next ? $this->c($next->started_at) : ($o->closed_at ? $this->c($o->closed_at) : null);
                if (!$end) continue;
                $sec = abs($end->diffInSeconds($this->c($row->started_at)));
                if ($sec > 86400) continue;
                $dur[$row->status][] = $sec;
                $long[] = ['order' => $o->id, 'status' => $labels[$row->status] ?? $row->status, 'min' => round($sec / 60)];
            }
            $ready = $h->firstWhere('status', 'ready');
            if ($ready) {
                $mm = abs($this->c($ready->started_at)->diffInMinutes($this->c($o->created_at)));
                if ($mm < 600) $speed[Grouper::daypart($this->c($o->created_at)->hour)][] = $mm;
            }
        }
        $statusRows = [];
        foreach (['pending', 'in_progress', 'ready', 'served', 'redo'] as $s) {
            if (empty($dur[$s])) continue;
            $statusRows[] = ['label' => $labels[$s], 'avgMin' => round(array_sum($dur[$s]) / count($dur[$s]) / 60, 1), 'maxMin' => round(max($dur[$s]) / 60), 'n' => count($dur[$s])];
        }
        usort($long, fn ($a, $b) => $b['min'] <=> $a['min']);
        $speedRows = [];
        foreach (['Утро', 'День', 'Вечер', 'Ночь'] as $d) if (!empty($speed[$d])) $speedRows[] = ['label' => $d, 'avgMin' => round(array_sum($speed[$d]) / count($speed[$d]), 1), 'n' => count($speed[$d])];

        $cancelled = $orders->where('status', 'cancelled');
        $logs = DB::table('order_logs')->where('action', 'status_changed')->where('new_value', 'like', '%cancelled%')->whereIn('order_id', $cancelled->keys()->all())->get();
        $users = DB::table('users')->selectRaw("id, TRIM(CONCAT(name,' ',COALESCE(surname,''))) as n")->pluck('n', 'id');
        $from = []; $who = []; $reasons = [];
        foreach ($logs as $l) {
            if ($this->status($l->old_value) === 'cancelled') continue;
            $s = $labels[$this->status($l->old_value)] ?? '—';
            $from[$s] = ($from[$s] ?? 0) + 1;
            $un = $users[$l->user_id] ?? '—';
            $who[$un] = ($who[$un] ?? 0) + 1;
            foreach (Grouper::reasons($l->comment) as $r) $reasons[$r] = ($reasons[$r] ?? 0) + 1;
        }
        arsort($from); arsort($who); arsort($reasons);

        return ['statusRows' => $statusRows, 'long' => array_slice($long, 0, 6), 'speed' => $speedRows,
            'cancel' => ['n' => $cancelled->count(), 'total' => $orders->count(), 'pct' => $orders->count() ? round($cancelled->count() / $orders->count() * 100, 1) : 0, 'from' => $from, 'who' => $who, 'reasons' => $reasons]];
    }

    /* ---------------- ДЕНЬГИ ---------------- */

    public function money(Period $p, array $can): array
    {
        $out = [];
        $names = $this->itemNames();
        if ($can['revenue'] ?? false) {
            $cur = $this->orders($p->from, $p->to);
            $rev = $this->revenueByOrder($cur);
            $total = array_sum($rev);
            $closed = $cur->where('status', 'closed')->count();
            $prevTotal = null;
            if ($p->prevFrom) $prevTotal = array_sum($this->revenueByOrder($this->orders($p->prevFrom, $p->prevTo)));
            $closedItems = $this->items($cur->where('status', 'closed')->pluck('id'));
            $byItem = [];
            foreach ($closedItems as $i) { $byItem[$i->key]['sum'] = ($byItem[$i->key]['sum'] ?? 0) + $i->eff; $byItem[$i->key]['n'] = ($byItem[$i->key]['n'] ?? 0) + 1; }
            uasort($byItem, fn ($a, $b) => $b['sum'] <=> $a['sum']);
            $out['revenue'] = ['total' => round($total), 'closed' => $closed, 'avg' => $closed ? round($total / $closed) : 0, 'delta' => $prevTotal !== null ? $this->pct($total, $prevTotal) : null,
                'top' => collect($byItem)->take(6)->map(fn ($v, $k) => ['name' => $names[$k] ?? $k, 'sum' => round($v['sum']), 'n' => $v['n']])->values()->all(), 'noPrice' => $closedItems->where('eff', 0)->count()];
        }
        if ($can['margin'] ?? false) {
            $def = $this->stock->defaultBowl();
            $avg = DB::table('tobacco_stocks')->pluck('avg_cost', 'tobacco_id');
            $ords = DB::table('orders')->where('status', '!=', 'cancelled')->pluck('bowl_size', 'id');
            $bowls = [];
            foreach (DB::table('order_items')->whereNull('deleted_at')->whereIn('itemable_type', ['App\\Models\\Mix', 'App\\Models\\Tobacco'])->get(['order_id', 'itemable_type', 'itemable_id']) as $it) {
                if (!isset($ords[$it->order_id])) continue;
                $bowls[$this->key($it->itemable_type, $it->itemable_id)][] = (float) ($ords[$it->order_id] ?: $def);
            }
            $bowlOf = fn ($k) => !empty($bowls[$k]) ? round(array_sum($bowls[$k]) / count($bowls[$k]), 1) : $def;
            $rows = []; $noPrice = [];
            foreach (DB::table('mixes')->where('is_active', true)->whereNull('deleted_at')->get() as $m) {
                $ings = $this->ingredients()[$m->id] ?? [];
                if (!$ings) continue;
                $w = array_map(fn ($i) => max((float) $i->grams, 0), $ings);
                $sum = array_sum($w);
                if ($sum <= 0) { $w = array_fill(0, count($ings), 1.0); $sum = count($ings); }
                $bowl = $bowlOf('mix:' . $m->id);
                $cost = 0;
                foreach ($ings as $k => $i) $cost += $bowl * ($w[$k] / $sum) * (float) ($avg[$i->tobacco_id] ?? 0);
                if ($m->price === null) { $noPrice[] = $m->name; continue; }
                $price = (float) $m->price;
                $rows[] = ['name' => $m->name, 'kind' => 'Микс', 'bowl' => $bowl, 'price' => round($price), 'cost' => round($cost), 'margin' => round($price - $cost), 'pct' => $price > 0 ? round(($price - $cost) / $price * 100) : 0];
            }
            foreach (DB::table('venue_tobaccos')->where('is_active', true)->whereNotNull('price')->get() as $vt) {
                $bowl = $bowlOf('tobacco:' . $vt->tobacco_id);
                $cost = $bowl * (float) ($avg[$vt->tobacco_id] ?? 0);
                $price = (float) $vt->price;
                $rows[] = ['name' => $this->tname($vt->tobacco_id), 'kind' => 'Табак', 'bowl' => $bowl, 'price' => round($price), 'cost' => round($cost), 'margin' => round($price - $cost), 'pct' => $price > 0 ? round(($price - $cost) / $price * 100) : 0];
            }
            usort($rows, fn ($a, $b) => $b['margin'] <=> $a['margin']);
            $out['margin'] = ['best' => array_slice($rows, 0, 5), 'worst' => array_slice(array_reverse($rows), 0, 5), 'count' => count($rows), 'noPrice' => $noPrice, 'bowl' => $def];
        }
        if ($can['prices'] ?? false) {
            $alert = (float) (Setting::get('stock_price_alert') ?: 10);
            $rec = DB::table('tobacco_receipts')->whereNotNull('price_per_gram')->orderBy('received_at')->orderBy('id')->get()->groupBy('tobacco_id');
            $rows = [];
            foreach ($rec as $tid => $g) {
                if ($g->count() < 2) continue;
                $last = $g->last(); $prev = $g[$g->count() - 2];
                if (!$this->c($last->received_at)->betweenIncluded($p->from, $p->to)) continue;
                $d = $this->pct((float) $last->price_per_gram, (float) $prev->price_per_gram);
                $rows[] = ['name' => $this->tname($tid), 'from' => round((float) $prev->price_per_gram, 2), 'to' => round((float) $last->price_per_gram, 2), 'delta' => $d, 'jump' => $d !== null && abs($d) >= $alert, 'when' => $this->c($last->received_at)->format('d.m.Y'),
                    'history' => $g->map(fn ($x) => ['d' => $this->c($x->received_at)->format('d.m.Y'), 'p' => round((float) $x->price_per_gram, 2)])->all()];
            }
            usort($rows, fn ($a, $b) => abs($b['delta'] ?? 0) <=> abs($a['delta'] ?? 0));
            $out['prices'] = ['rows' => array_slice($rows, 0, 12), 'alert' => $alert];
        }
        if ($can['losses'] ?? false) {
            $aud = DB::table('stock_audits')->whereBetween('created_at', [$p->from, $p->to])->where('diff', '<>', 0)->get();
            $wo = DB::table('stock_movements')->where('type', 'writeoff')->whereBetween('created_at', [$p->from, $p->to])->get();
            $by = [];
            foreach ($aud as $a) { $by[$a->tobacco_id]['audit'] = ($by[$a->tobacco_id]['audit'] ?? 0) + (float) $a->diff; $by[$a->tobacco_id]['cost'] = ($by[$a->tobacco_id]['cost'] ?? 0) + (float) $a->diff_cost; }
            foreach ($wo as $w) { $by[$w->tobacco_id]['wo'] = ($by[$w->tobacco_id]['wo'] ?? 0) + abs((float) $w->grams); $by[$w->tobacco_id]['cost'] = ($by[$w->tobacco_id]['cost'] ?? 0) - (float) $w->cost; }
            $rows = collect($by)->map(fn ($v, $t) => ['name' => $this->tname($t), 'audit' => round($v['audit'] ?? 0, 1), 'wo' => round($v['wo'] ?? 0, 1), 'cost' => round($v['cost'] ?? 0)])->sortBy('cost')->take(10)->values()->all();
            $out['losses'] = ['auditN' => $aud->count(), 'auditCost' => round($aud->sum('diff_cost')), 'auditMinus' => round($aud->where('diff', '<', 0)->sum('diff_cost')), 'woN' => $wo->count(),
                'woGrams' => round($wo->sum(fn ($w) => abs((float) $w->grams)), 1), 'woCost' => round($wo->sum('cost')), 'rows' => $rows];
        }

        return $out;
    }
}
