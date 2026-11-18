<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class Period
{
    public Carbon $from;
    public Carbon $to;
    public ?Carbon $prevFrom = null;
    public ?Carbon $prevTo = null;
    public Carbon $yoyFrom;
    public Carbon $yoyTo;
    public string $key;
    public string $label;
    public bool $isAll = false;

    public static function fromRequest(Request $r): self
    {
        $p = new self();
        $key = $r->get('p', 'month');
        $now = now();
        switch ($key) {
            case 'today':
                $p->from = $now->copy()->startOfDay(); $p->to = $now->copy()->endOfDay(); $p->label = 'Сегодня'; break;
            case 'week':
                $p->from = $now->copy()->startOfWeek(); $p->to = $now->copy()->endOfWeek(); $p->label = 'Эта неделя'; break;
            case 'year':
                $p->from = $now->copy()->startOfYear(); $p->to = $now->copy()->endOfYear(); $p->label = $now->year . ' год'; break;
            case 'all':
                $first = Order::min('created_at');
                $p->from = $first ? Carbon::parse($first)->startOfDay() : $now->copy()->startOfYear();
                $p->to = $now->copy()->endOfDay(); $p->label = 'Всё время'; $p->isAll = true; break;
            case 'custom':
                try {
                    $p->from = Carbon::parse($r->get('from', $now->toDateString()))->startOfDay();
                    $p->to = Carbon::parse($r->get('to', $now->toDateString()))->endOfDay();
                } catch (\Throwable $e) {
                    $p->from = $now->copy()->startOfMonth(); $p->to = $now->copy()->endOfDay();
                }
                if ($p->to->lt($p->from)) [$p->from, $p->to] = [$p->to->copy()->startOfDay(), $p->from->copy()->endOfDay()];
                $p->label = $p->from->format('d.m.Y') . ' — ' . $p->to->format('d.m.Y'); break;
            case 'month_pick':
                try { $m = Carbon::createFromFormat('Y-m-d', ($r->get('m') ?: $now->format('Y-m')) . '-01'); } catch (\Throwable $e) { $m = $now->copy()->startOfMonth(); }
                $p->from = $m->copy()->startOfMonth(); $p->to = $m->copy()->endOfMonth();
                $p->label = $m->locale('ru')->isoFormat('MMMM YYYY'); break;
            default:
                $key = 'month';
                $p->from = $now->copy()->startOfMonth(); $p->to = $now->copy()->endOfMonth(); $p->label = 'Этот месяц';
        }
        $p->key = $key;
        if (!$p->isAll) {
            if (in_array($key, ['month', 'month_pick'], true)) {
                $p->prevFrom = $p->from->copy()->subMonthNoOverflow()->startOfMonth();
                $p->prevTo = $p->from->copy()->subMonthNoOverflow()->endOfMonth();
            } else {
                $secs = max(1, abs($p->to->diffInSeconds($p->from)));
                $p->prevTo = $p->from->copy()->subSecond();
                $p->prevFrom = $p->prevTo->copy()->subSeconds($secs);
            }
        }
        if (!$p->isAll && $p->prevFrom && $p->to->gt(now()) && $p->from->lte(now())) {
            $p->prevTo = $p->prevFrom->copy()->addSeconds(max(0, now()->timestamp - $p->from->timestamp))->min($p->prevTo);
        }
        $p->yoyFrom = $p->from->copy()->subYear();
        $p->yoyTo = $p->to->copy()->subYear();
        return $p;
    }

    public function days(): int
    {
        return max(1, (int) ceil(abs($this->from->diffInDays($this->to->copy()->min(now()->endOfDay()))) + 1));
    }

    public function query(): string
    {
        return http_build_query(array_filter(['p' => $this->key, 'from' => $this->from->toDateString(), 'to' => $this->to->toDateString(), 'm' => $this->from->format('Y-m')]));
    }
}
