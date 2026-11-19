<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TobaccoPairs
{
    public function for(int $tobaccoId, int $limit = 5): array
    {
        $empty = ['closed' => [], 'redo' => [], 'closedTotal' => 0, 'redoTotal' => 0];

        $ing = DB::table('mix_ingredients')->whereNull('deleted_at')->get(['mix_id', 'tobacco_id'])->groupBy('mix_id');
        $mixIds = $ing->filter(fn ($g) => $g->contains('tobacco_id', $tobaccoId))->keys()->all();
        if (!$mixIds) {
            return $empty;
        }

        $items = DB::table('order_items')->whereNull('deleted_at')
            ->where('itemable_type', \App\Models\Mix::class)
            ->whereIn('itemable_id', $mixIds)
            ->get(['order_id', 'itemable_id']);
        if ($items->isEmpty()) {
            return $empty;
        }

        $orderIds = $items->pluck('order_id')->unique()->values();
        $status = [];
        $redoSet = [];
        foreach ($orderIds->chunk(1000) as $chunk) {
            foreach (DB::table('orders')->whereIn('id', $chunk->all())->pluck('status', 'id') as $id => $st) {
                $status[$id] = $st;
            }
            foreach (DB::table('order_logs')->where('action', 'status_changed')->where('new_value', 'like', '%redo%')->whereIn('order_id', $chunk->all())->pluck('order_id') as $id) {
                $redoSet[$id] = true;
            }
        }

        $closed = [];
        $redo = [];
        $closedTotal = 0;
        $redoTotal = 0;
        foreach ($items as $it) {
            $partners = $ing[$it->itemable_id]->pluck('tobacco_id')->unique()->reject(fn ($t) => (int) $t === $tobaccoId);
            if (($status[$it->order_id] ?? null) === 'closed') {
                $closedTotal++;
                foreach ($partners as $p) {
                    $closed[$p] = ($closed[$p] ?? 0) + 1;
                }
            }
            if (isset($redoSet[$it->order_id])) {
                $redoTotal++;
                foreach ($partners as $p) {
                    $redo[$p] = ($redo[$p] ?? 0) + 1;
                }
            }
        }

        arsort($closed);
        arsort($redo);
        $closed = array_slice($closed, 0, $limit, true);
        $redo = array_slice($redo, 0, $limit, true);

        $ids = array_unique(array_merge(array_keys($closed), array_keys($redo)));
        $brands = DB::table('tobacco_brands')->pluck('name', 'id');
        $names = [];
        foreach (DB::table('tobaccos')->whereIn('id', $ids)->get(['id', 'name', 'brand_id']) as $t) {
            $names[$t->id] = trim(($brands[$t->brand_id] ?? '') . ' ' . $t->name);
        }

        $fmt = fn (array $m, int $total) => collect($m)->map(fn ($n, $id) => [
            'name' => $names[$id] ?? ('Табак #' . $id),
            'n' => $n,
            'pct' => $total ? round($n / $total * 100) : 0,
        ])->values()->all();

        return ['closed' => $fmt($closed, $closedTotal), 'redo' => $fmt($redo, $redoTotal), 'closedTotal' => $closedTotal, 'redoTotal' => $redoTotal];
    }
}
