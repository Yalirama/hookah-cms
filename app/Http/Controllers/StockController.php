<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\Setting;
use App\Models\Mix;
use App\Models\StockAudit;
use App\Models\StockMovement;
use App\Models\Tobacco;
use App\Models\TobaccoStock;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function panel(StockService $stock)
    {
        $order = ['none' => 0, 'low' => 1, 'ok' => 2];
        $rows = TobaccoStock::with('tobacco.brand')->get()
            ->each(fn ($r) => $r->lvl = $stock->level($r->tobacco_id)['key'] ?? 'ok')
            ->sortBy(fn ($r) => $order[$r->lvl] . '|' . mb_strtolower(($r->tobacco->brand->name ?? '') . ' ' . $r->tobacco->name))->values();
        $untracked = Tobacco::with('brand')->whereHas('venueTobacco', fn ($q) => $q->where('is_active', true))
            ->whereNotIn('id', TobaccoStock::pluck('tobacco_id'))->get()
            ->sortBy(fn ($t) => mb_strtolower(($t->brand->name ?? '') . ' ' . $t->name))->values();
        $buy = $rows->filter(fn ($r) => $r->available <= $stock->minFor($r))->values();
        $minDefault = $stock->defaultMin();
        $bowl = $stock->defaultBowl();
        $lead = (int) (Setting::get('stock_lead_days') ?: 3);
        $buffer = (int) (Setting::get('stock_buffer_days') ?: 4);
        $alert = (float) (Setting::get('stock_price_alert') ?: 10);

        return view('settings.partials._stock-panel', compact('rows', 'untracked', 'buy', 'minDefault', 'bowl', 'lead', 'buffer', 'alert'));
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $items = Tobacco::with('brand')
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$q}%"));
            }))
            ->orderBy('name')->limit(15)->get()
            ->map(fn ($t) => ['id' => $t->id, 'name' => trim(($t->brand->name ?? '') . ' - ' . $t->name)]);

        return response()->json($items);
    }

    public function receive(Request $request, StockService $stock)
    {
        $data = $request->validate([
            'tobacco_id' => 'required|exists:tobaccos,id',
            'grams'      => 'nullable|numeric|min:0.1|max:100000',
            'packs'      => 'nullable|integer|min:1|max:10000',
            'price'      => 'nullable|numeric|min:0|max:100000',
            'supplier'   => 'nullable|string|max:255',
            'received_at' => 'nullable|date',
        ]);

        $tobacco = Tobacco::with('brand')->findOrFail($data['tobacco_id']);
        $row = $stock->stock($tobacco->id);

        $grams = !empty($data['packs']) ? $data['packs'] * $row->pack_size : ($data['grams'] ?? 0);
        if ($grams <= 0) return response()->json(['message' => 'Укажите граммы или количество пачек'], 422);

        $price = !empty($data['price']) ? (float) $data['price'] : null;
        $res = $stock->receive($tobacco, (float) $grams, $data['supplier'] ?? null, $data['received_at'] ?? null, $price);
        AdminLog::record('created', 'Склад', $this->label($tobacco), 'Приход: ' . $stock->fmt((float) $grams) . ' г' . ($price ? ' по ' . $stock->fmt($price) . ' за г' : '') . (!empty($data['supplier']) ? ', поставщик ' . $data['supplier'] : '') . ($res['warning'] ? '. ' . $res['warning'] : ''));

        return response()->json(['ok' => true, 'warning' => $res['warning']]);
    }

    public function writeOff(Request $request, StockService $stock)
    {
        $data = $request->validate([
            'tobacco_id' => 'required|exists:tobaccos,id',
            'grams'      => 'required|numeric|min:0.1|max:100000',
            'reason'     => 'required|string|max:255',
        ]);

        $tobacco = Tobacco::with('brand')->findOrFail($data['tobacco_id']);
        if ((float) $data['grams'] > (float) $stock->stock($tobacco->id)->on_hand + 0.001) {
            return response()->json(['message' => 'Нельзя списать больше остатка на складе'], 422);
        }

        $stock->writeOff($tobacco, (float) $data['grams'], $data['reason']);
        AdminLog::record('updated', 'Склад', $this->label($tobacco), 'Списание: ' . $stock->fmt((float) $data['grams']) . ' г, причина: ' . $data['reason']);

        return response()->json(['ok' => true]);
    }

    public function params(Request $request, StockService $stock)
    {
        $data = $request->validate([
            'tobacco_id' => 'required|exists:tobaccos,id',
            'min_stock'  => 'nullable|numeric|min:0|max:100000',
            'pack_size'  => 'required|numeric|min:1|max:10000',
            'supplier'   => 'nullable|string|max:255',
        ]);

        $tobacco = Tobacco::with('brand')->findOrFail($data['tobacco_id']);
        $row = $stock->stock($tobacco->id);
        $old = "порог {$row->min_stock}, пачка {$row->pack_size}, поставщик " . ($row->supplier ?: '—');
        $row->update(['min_stock' => $data['min_stock'] ?? 0, 'pack_size' => $data['pack_size'], 'supplier' => $data['supplier'] ?? null]);
        AdminLog::record('updated', 'Склад', $this->label($tobacco), "Параметры: {$old} → порог {$row->min_stock}, пачка {$row->pack_size}, поставщик " . ($row->supplier ?: '—'));

        return response()->json(['ok' => true]);
    }

    public function audit(Request $request, StockService $stock)
    {
        $data = $request->validate(['tobacco_id' => 'required|exists:tobaccos,id', 'actual' => 'required|numeric|min:0|max:100000']);
        $tobacco = Tobacco::with('brand')->findOrFail($data['tobacco_id']);
        $a = $stock->audit($tobacco, (float) $data['actual']);
        AdminLog::record('updated', 'Склад', $this->label($tobacco), 'Ревизия: учёт ' . $stock->fmt($a->expected) . ' г, факт ' . $stock->fmt($a->actual) . ' г, разница ' . $stock->fmt($a->diff) . ' г');

        return response()->json(['ok' => true, 'diff' => $a->diff]);
    }

    public function settings(Request $request)
    {
        $data = $request->validate([
            'stock_lead_days' => 'required|integer|min:0|max:90',
            'stock_buffer_days' => 'required|integer|min:0|max:90',
            'stock_price_alert' => 'required|numeric|min:0|max:1000',
            'stock_min_default' => 'required|numeric|min:0|max:100000',
        ]);
        foreach ($data as $k => $v) Setting::set($k, $v);

        return response()->json(['ok' => true]);
    }

    public function mixPrice(Request $request, Mix $mix)
    {
        $data = $request->validate(['price' => 'nullable|numeric|min:0|max:10000000']);
        $mix->update(['price' => $data['price'] ?? null]);

        return response()->json(['ok' => true]);
    }

    public function reports(StockService $stock)
    {
        $lead = (int) (Setting::get('stock_lead_days') ?: 3);
        $buffer = (int) (Setting::get('stock_buffer_days') ?: 4);
        $c1 = $stock->consumption(1);
        $c7 = $stock->consumption(7);
        $c30 = $stock->consumption(30);
        $c28 = $stock->consumption(28);

        $rows = TobaccoStock::with('tobacco.brand')->get()->map(function ($r) use ($c28, $c1, $c7, $c30, $lead, $buffer, $stock) {
            $avg = ($c28[$r->tobacco_id]['g'] ?? 0) / 28;
            $days = $avg > 0 ? $r->available / $avg : null;
            $mn = $stock->minFor($r);
            $need = max($avg * ($lead + $buffer) - $r->available, $r->available <= $mn ? $mn - $r->available + $avg * ($lead + $buffer) : 0);
            $buy = $need > 0 ? ($r->pack_size > 0 ? ceil($need / $r->pack_size) * $r->pack_size : ceil($need)) : 0;
            $lowDays = $days !== null && $days < 7;
            return (object) [
                'name' => trim(($r->tobacco->brand->name ?? '') . ' ' . $r->tobacco->name), 'stock' => $r,
                'avg' => $avg, 'days' => $days, 'buy' => $buy, 'need' => ($r->available <= $mn || $lowDays) && $buy > 0,
                'd1' => $c1[$r->tobacco_id]['g'] ?? 0, 'd7' => $c7[$r->tobacco_id]['g'] ?? 0, 'd30' => $c30[$r->tobacco_id]['g'] ?? 0,
                'supplier' => $r->supplier ?: 'Без поставщика',
            ];
        })->sortBy('name')->values();

        $purchase = $rows->filter->need->groupBy('supplier');
        $top = $rows->sortByDesc('d30')->filter(fn ($r) => $r->d30 > 0)->take(5)->values();
        $stale = $rows->filter(fn ($r) => $r->d30 <= 0 && $r->stock->on_hand > 0 && $r->stock->created_at->lte(now()->subDays(30)))->values();

        $mixes = Mix::where('is_active', true)->with('ingredients.tobacco')->orderBy('name')->get()->map(function ($m) use ($stock) {
            $bowl = $stock->defaultBowl();
            $ings = $m->ingredients->filter(fn ($i) => $i->tobacco);
            $w = $ings->map(fn ($i) => max((float) $i->grams, 0));
            if ($w->sum() <= 0) $w = $ings->map(fn () => 1.0);
            $totals = [];
            foreach ($ings->values() as $idx => $i) $totals[$i->tobacco_id] = ($totals[$i->tobacco_id] ?? 0) + $bowl * $w->values()[$idx] / max($w->sum(), 0.0001);
            $cost = $stock->bowlCost($totals);
            $m->cost = $cost > 0 ? $cost : null;
            $m->margin = ($m->price !== null && $cost > 0) ? (float) $m->price - $cost : null;
            return $m;
        });

        $audits = StockAudit::with(['tobacco.brand', 'user'])->orderByDesc('id')->limit(20)->get();
        $auditByUser = StockAudit::with('user')->get()->groupBy('user_id')->map(fn ($g) => (object) [
            'name' => $g->first()->user?->name ?? '—', 'count' => $g->count(),
            'diff' => $g->sum('diff'), 'abs' => $g->sum(fn ($a) => abs($a->diff)), 'cost' => $g->sum('diff_cost'),
        ])->values();
        $byMaster = StockMovement::with('user')->where('type', 'consume')->where('created_at', '>=', now()->subDays(30))->get()
            ->groupBy('user_id')->map(fn ($g) => (object) ['name' => $g->first()->user?->name ?? '—', 'g' => -$g->sum('grams'), 'orders' => $g->pluck('order_id')->unique()->count()])->values();

        return view('settings.partials._stock-reports', compact('purchase', 'rows', 'top', 'stale', 'mixes', 'audits', 'auditByUser', 'byMaster', 'lead', 'buffer'));
    }

    public function bowl(Request $request)
    {
        $data = $request->validate(['bowl_size' => 'required|numeric|min:1|max:200']);
        Setting::set('bowl_size', $data['bowl_size']);

        return response()->json(['ok' => true]);
    }

    public function journal(Request $request)
    {
        $logs = StockMovement::with(['tobacco.brand', 'user'])
            ->when($request->query('tobacco_id'), fn ($q, $id) => $q->where('tobacco_id', $id))
            ->when($request->query('before'), fn ($q, $b) => $q->where('id', '<', $b))
            ->orderByDesc('id')->limit(30)->get();

        return view('settings.partials._stock-journal-rows', compact('logs'));
    }

    private function label(Tobacco $t): string
    {
        return trim(($t->brand->name ?? '') . ' ' . $t->name);
    }
}
