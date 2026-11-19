<?php

namespace App\Http\Controllers;

use App\Services\Stats\MasterStats;
use App\Services\Stats\MyActions;
use App\Support\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MyStatsController extends Controller
{
    public function index(Request $r, MasterStats $masters, MyActions $myActions)
    {
        if (!$r->has('p')) {
            $r->merge(['p' => 'all']);
        }
        $p = Period::fromRequest($r);
        $uid = auth()->id();

        $d = $masters->build($p);
        $rows = collect($d['rows']);
        $me = $rows->firstWhere('id', $uid);

        $cats = [
            ['prepared', 'Приготовил', 'шт', true, null],
            ['created', 'Создал заказов', 'шт', true, null],
            ['closed', 'Закрыл', 'шт', true, null],
            ['serviced', 'Обслужил', 'шт', true, null],
            ['avgNew', 'Скорость до «Готов» (меньше — лучше)', 'мин', false, fn ($x) => $x['prepared'] >= 3],
            ['redoPct', 'Доля переделок (меньше — лучше)', '%', false, fn ($x) => $x['prepared'] >= 3],
        ];
        $ranks = [];
        if ($me) {
            foreach ($cats as [$key, $label, $unit, $desc, $filter]) {
                $set = $rows->filter(fn ($x) => $x[$key] !== null && (!$filter || $filter($x)));
                if (!$set->contains('id', $uid)) {
                    continue;
                }
                $mine = $me[$key];
                $better = $set->filter(fn ($x) => $desc ? $x[$key] > $mine : $x[$key] < $mine)->count();
                $ranks[] = ['label' => $label, 'place' => $better + 1, 'total' => $set->count(), 'value' => $mine, 'unit' => $unit];
            }
        }

        $topMixes = [];
        $topTobaccos = [];
        $orderIds = DB::table('order_logs')
            ->where('user_id', $uid)->where('action', 'status_changed')
            ->whereBetween('created_at', [$p->from, $p->to])
            ->where('old_value', 'like', '%in_progress%')->where('new_value', 'like', '%ready%')
            ->pluck('order_id')->unique()->values();
        if ($orderIds->isNotEmpty()) {
            $counts = [];
            foreach ($orderIds->chunk(1000) as $chunk) {
                $q = DB::table('order_items')->whereNull('deleted_at')->whereIn('order_id', $chunk->all())
                    ->selectRaw('itemable_type, itemable_id, COUNT(*) as c')->groupBy('itemable_type', 'itemable_id')->get();
                foreach ($q as $row) {
                    $k = (str_contains($row->itemable_type, 'Mix') ? 'mix' : 'tobacco') . ':' . $row->itemable_id;
                    $counts[$k] = ($counts[$k] ?? 0) + (int) $row->c;
                }
            }
            arsort($counts);
            $mixNames = DB::table('mixes')->pluck('name', 'id');
            $brands = DB::table('tobacco_brands')->pluck('name', 'id');
            $tobNames = DB::table('tobaccos')->get(['id', 'name', 'brand_id'])->mapWithKeys(fn ($t) => [$t->id => trim(($brands[$t->brand_id] ?? '') . ' ' . $t->name)]);
            foreach ($counts as $k => $c) {
                [$type, $id] = explode(':', $k);
                if ($type === 'mix' && count($topMixes) < 5) {
                    $topMixes[] = ['name' => $mixNames[$id] ?? ('Микс #' . $id), 'n' => $c];
                } elseif ($type === 'tobacco' && count($topTobaccos) < 5) {
                    $topTobaccos[] = ['name' => $tobNames[$id] ?? ('Табак #' . $id), 'n' => $c];
                }
            }
        }

        return view('my-stats.index', compact('p', 'me', 'ranks', 'topMixes', 'topTobaccos') + ['actions' => $myActions->build($uid, $p)] + ['period' => $p]);
    }
}
