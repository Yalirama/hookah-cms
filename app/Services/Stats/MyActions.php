<?php

namespace App\Services\Stats;

use App\Support\OrderFrequency;
use App\Support\Period;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MyActions
{
    private const DAYS = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

    private const LABELS = ['prepared' => 'Приготовил', 'created' => 'Принял', 'took' => 'В работу', 'serviced' => 'Обслужил', 'delivered' => 'Отнёс гостю'];

    private function status($json): ?string
    {
        return $json ? (json_decode($json, true)['status'] ?? null) : null;
    }

    public function build(int $uid, Period $p): array
    {
        $logs = DB::table('order_logs as l')
            ->leftJoin('orders as o', 'o.id', '=', 'l.order_id')
            ->where('l.user_id', $uid)
            ->whereBetween('l.created_at', [$p->from, $p->to])
            ->whereIn('l.action', ['created', 'serviced', 'status_changed'])
            ->get(['l.action', 'l.old_value', 'l.new_value', 'l.created_at', 'o.guest_id']);

        $acc = [];
        foreach (self::LABELS as $k => $label) {
            $acc[$k] = ['key' => $k, 'label' => $label, 'total' => 0, 'days' => array_fill(0, 7, 0), 'hours' => array_fill(0, 8, 0), 'ym' => [], 'guests' => []];
        }

        foreach ($logs as $l) {
            $keys = [];
            if ($l->action === 'created') {
                $keys[] = 'created';
            } elseif ($l->action === 'serviced') {
                $keys[] = 'serviced';
            } else {
                $o = $this->status($l->old_value);
                $n = $this->status($l->new_value);
                if ($o === 'pending' && $n === 'in_progress') $keys[] = 'took';
                if ($o === 'in_progress' && $n === 'ready') $keys[] = 'prepared';
                if ($o === 'ready' && $n === 'served') $keys[] = 'delivered';
            }
            if (!$keys) continue;
            $t = Carbon::parse($l->created_at);
            foreach ($keys as $k) {
                $a = &$acc[$k];
                $a['total']++;
                $a['days'][$t->dayOfWeekIso - 1]++;
                $a['hours'][intdiv($t->hour, 3)]++;
                $ym = $t->format('Y-m');
                $a['ym'][$ym] = ($a['ym'][$ym] ?? 0) + 1;
                if ($l->guest_id) $a['guests'][$l->guest_id] = ($a['guests'][$l->guest_id] ?? 0) + 1;
                unset($a);
            }
        }

        $names = DB::table('guests')->pluck('name', 'id');
        $out = [];
        foreach ($acc as $a) {
            $rows = collect($a['ym'])->map(fn ($c, $ym) => (object) ['ym' => $ym, 'c' => $c])->values();
            $f = OrderFrequency::build($rows);
            arsort($a['guests']);
            $guests = [];
            foreach (array_slice($a['guests'], 0, 5, true) as $gid => $c) {
                $guests[] = ['name' => $names[$gid] ?? ('Гость #' . $gid), 'n' => $c];
            }
            $out[] = [
                'key' => $a['key'], 'label' => $a['label'], 'total' => $a['total'],
                'days' => array_map(fn ($i) => ['label' => self::DAYS[$i], 'count' => $a['days'][$i]], range(0, 6)),
                'hours' => array_map(fn ($i) => ['label' => ($i * 3) . '–' . ($i * 3 + 3), 'count' => $a['hours'][$i]], range(0, 7)),
                'months' => $f['months'], 'seasons' => $f['seasons'], 'years' => $f['years'], 'guests' => $guests,
            ];
        }

        return $out;
    }
}
