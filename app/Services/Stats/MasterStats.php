<?php

namespace App\Services\Stats;

use App\Support\Period;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MasterStats
{
    private function status($json): ?string
    {
        return $json ? (json_decode($json, true)['status'] ?? null) : null;
    }

    private function blank(): array
    {
        return ['created' => 0, 'took' => 0, 'prepared' => 0, 'delivered' => 0, 'closed' => 0, 'cancelled' => 0, 'redo' => 0, 'serviced' => 0, 'rollbacks' => 0,
            'madeChanges' => 0, 'lost' => 0, 'gained' => 0, 'preparedOrders' => [], 'events' => []];
    }

    public function build(Period $p): array
    {
        $users = DB::table('users')->selectRaw("id, TRIM(CONCAT(name,' ',COALESCE(surname,''))) as n")->pluck('n', 'id');
        $tables = DB::table('tables')->pluck('name', 'id');
        $logs = DB::table('order_logs')->whereBetween('created_at', [$p->from, $p->to])->whereIn('action', ['created', 'status_changed', 'serviced', 'rolled_back', 'master_changed'])->orderBy('id')->get();
        $oids = $logs->pluck('order_id')->unique()->all();
        $orders = DB::table('orders')->whereIn('id', $oids)->get()->keyBy('id');
        $hist = DB::table('order_status_histories')->whereIn('order_id', $oids)->get()->groupBy('order_id');
        $items = DB::table('order_items')->whereNull('deleted_at')->whereIn('order_id', $oids)->get()->groupBy('order_id');
        $labels = ['pending' => 'Новый', 'in_progress' => 'В работе', 'ready' => 'Готов', 'served' => 'Подан', 'redo' => 'Переделка', 'closed' => 'Закрыт', 'cancelled' => 'Отменён'];

        $m = [];
        $ev = function ($uid, $type, $log, $note = '') use (&$m, $orders, $tables) {
            $o = $orders[$log->order_id] ?? null;
            $m[$uid]['events'][] = ['type' => $type, 'order' => $log->order_id, 'when' => Carbon::parse($log->created_at)->format('d.m H:i'),
                'table' => $o && $o->table_id ? ($tables[$o->table_id] ?? '') : '', 'note' => $note];
        };
        foreach ($logs as $l) {
            $u = $l->user_id;
            $m[$u] ??= $this->blank();
            switch ($l->action) {
                case 'created': $m[$u]['created']++; break;
                case 'serviced': $m[$u]['serviced']++; break;
                case 'rolled_back':
                    $m[$u]['rollbacks']++;
                    $ev($u, 'Откат', $l, trim(($labels[$this->status($l->old_value)] ?? '') . ' → ' . ($labels[$this->status($l->new_value)] ?? '') . ($l->comment ? ' · ' . $l->comment : '')));
                    break;
                case 'master_changed':
                    $old = json_decode($l->old_value, true)['user_id'] ?? null;
                    $new = json_decode($l->new_value, true)['user_id'] ?? null;
                    $m[$u]['madeChanges']++;
                    $ev($u, 'Сменил мастера', $l, ($users[$old] ?? '?') . ' → ' . ($users[$new] ?? '?'));
                    if ($old) { $m[$old] ??= $this->blank(); $m[$old]['lost']++; $ev($old, 'Заказ забрали', $l, '→ ' . ($users[$new] ?? '?')); }
                    if ($new) { $m[$new] ??= $this->blank(); $m[$new]['gained']++; $ev($new, 'Заказ передали', $l, 'от ' . ($users[$old] ?? '?')); }
                    break;
                case 'status_changed':
                    $o = $this->status($l->old_value); $n = $this->status($l->new_value);
                    if ($o === 'pending' && $n === 'in_progress') $m[$u]['took']++;
                    if ($o === 'in_progress' && $n === 'ready') { $m[$u]['prepared']++; $m[$u]['preparedOrders'][$l->order_id] = $l; }
                    if ($o === 'ready' && $n === 'served') $m[$u]['delivered']++;
                    if ($n === 'closed') $m[$u]['closed']++;
                    if ($n === 'cancelled') $m[$u]['cancelled']++;
                    if ($n === 'redo') $m[$u]['redo']++;
                    break;
            }
        }

        $rows = [];
        foreach ($m as $uid => $a) {
            $out = ['closed' => 0, 'cancelled' => 0, 'redo' => 0, 'other' => 0];
            $times = []; $cook = []; $alt = 0; $dp = [];
            foreach ($a['preparedOrders'] as $oid => $log) {
                $o = $orders[$oid] ?? null;
                if (!$o) continue;
                if (isset($out[$o->status])) $out[$o->status]++; else $out['other']++;
                $h = $hist[$oid] ?? collect();
                $ready = $h->where('status', 'ready')->last();
                $prog = $h->where('status', 'in_progress')->first();
                if ($ready) {
                    $t = abs(Carbon::parse($ready->started_at)->diffInMinutes(Carbon::parse($o->created_at)));
                    if ($t < 600) $times[] = $t;
                    if ($prog) { $c = abs(Carbon::parse($ready->started_at)->diffInMinutes(Carbon::parse($prog->started_at))); if ($c < 600) $cook[] = $c; }
                }
                foreach ($items[$oid] ?? [] as $it) if (!empty(json_decode($it->alt_choices ?? 'null', true))) $alt++;
                $d = Grouper::daypart(Carbon::parse($log->created_at)->hour);
                $dp[$d] = ($dp[$d] ?? 0) + 1;
            }
            $secs = 0;
            $pt = collect($a['preparedOrders'])->map(fn ($l) => strtotime($l->created_at))->sort()->values()->all();
            $shifts = DB::table('shift_logs')->where('user_id', $uid)->where('started_at', '<=', $p->to)->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $p->from))->get();
            $shiftRows = [];
            foreach ($shifts as $s) {
                $st = Carbon::parse($s->started_at)->max($p->from);
                $en = ($s->ended_at ? Carbon::parse($s->ended_at) : now())->min($p->to);
                if ($en->gt($st)) {
                    $secs += abs($en->diffInSeconds($st));
                    $a0 = $st->timestamp; $a1 = $en->timestamp;
                    $n = 0;
                    foreach ($pt as $ts) { if ($ts > $a1) break; if ($ts >= $a0) $n++; }
                    $shiftRows[] = ['from' => $st->format('d.m H:i'), 'to' => $en->format('H:i'), 'hours' => round(abs($en->diffInSeconds($st)) / 3600, 1), 'prepared' => $n];
                }
            }
            unset($a['preparedOrders']);
            $rows[] = ['id' => $uid, 'name' => $users[$uid] ?? 'Пользователь #' . $uid] + $a + [
                'outcome' => $out, 'avgNew' => $times ? round(array_sum($times) / count($times), 1) : null, 'avgCook' => $cook ? round(array_sum($cook) / count($cook), 1) : null,
                'redoPct' => $a['prepared'] ? round($out['redo'] / $a['prepared'] * 100) : null, 'alt' => $alt, 'dayparts' => $dp, 'shiftHours' => round($secs / 3600, 1), 'shifts' => array_slice(array_reverse($shiftRows), 0, 60),
                'perHour' => $secs > 0 ? round($a['prepared'] / ($secs / 3600), 1) : null,
            ];
        }
        usort($rows, fn ($a, $b) => $b['prepared'] <=> $a['prepared']);

        $top = function ($key, $label, $unit, $desc = true, $filter = null) use ($rows) {
            $r = collect($rows)->filter(fn ($x) => $x[$key] !== null && ($filter ? $filter($x) : $x[$key] > 0));
            $r = $desc ? $r->sortByDesc($key) : $r->sortBy($key);

            return ['label' => $label, 'unit' => $unit, 'rows' => $r->take(3)->map(fn ($x) => ['name' => $x['name'], 'v' => $x[$key]])->values()->all()];
        };
        $tops = [
            $top('prepared', 'Больше всех приготовил', 'шт'),
            $top('created', 'Больше всех создал заказов', 'шт'),
            $top('closed', 'Больше всех закрыл', 'шт'),
            $top('avgNew', 'Быстрее всех: от «Нового» до «Готов»', 'мин', false, fn ($x) => $x['prepared'] >= 3),
            $top('redoPct', 'Меньше всего переделок', '%', false, fn ($x) => $x['prepared'] >= 3),
            $top('redoPct', 'Больше всего переделок', '%', true, fn ($x) => $x['prepared'] >= 3 && $x['redoPct'] > 0),
            $top('rollbacks', 'Больше всего откатов', 'шт'),
            $top('lost', 'У кого чаще забирали заказы', 'шт'),
            $top('alt', 'Чаще заменял табак на альтернативу', 'шт'),
        ];

        return ['rows' => $rows, 'tops' => array_values(array_filter($tops, fn ($t) => $t['rows']))];
    }
}
