<?php

namespace App\Services;

use App\Models\OrderLog;
use App\Models\OrderTableHistory;
use App\Models\Table;

class TableMoves
{
    public static function build($from = null, $to = null, ?int $guestId = null)
    {
        $logs = OrderLog::where('action', 'table_changed')
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->when($guestId, fn($q) => $q->whereHas('order', fn($o) => $o->where('guest_id', $guestId)))
            ->with(['order:id,name,status,created_at', 'user:id,name'])
            ->orderByDesc('created_at')
            ->get();

        $names = Table::pluck('name', 'id');
        $used = OrderTableHistory::when($from && $to, fn($q) => $q->whereBetween('assigned_at', [$from, $to]))
            ->when($guestId, fn($q) => $q->whereHas('order', fn($o) => $o->where('guest_id', $guestId)))
            ->selectRaw('table_id, count(distinct order_id) as c')->groupBy('table_id')->pluck('c', 'table_id');

        return $logs->filter(fn($l) => $l->order && !empty($l->old_value['table_id']))
            ->groupBy(fn($l) => $l->old_value['table_id'])
            ->map(function ($g, $tid) use ($names, $used) {
                $total = max((int) ($used[$tid] ?? 0), $g->count());
                return [
                    'name'  => $names[$tid] ?? '—',
                    'count' => $g->count(),
                    'total' => $total,
                    'pct'   => (int) round($g->count() / $total * 100),
                    'moves' => $g->map(fn($l) => [
                        'order_id' => $l->order_id,
                        'when'     => $l->created_at->locale('ru')->translatedFormat('d M, H:i'),
                        'to'       => $names[$l->new_value['table_id'] ?? 0] ?? '—',
                        'who'      => $l->user?->name ?? '—',
                        'reason'   => trim((string) $l->comment),
                    ])->values()->all(),
                ];
            })
            ->sortByDesc('count')->values();
    }
}
