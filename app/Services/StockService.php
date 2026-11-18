<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Tobacco;
use App\Models\TobaccoReceipt;
use App\Models\StockAudit;
use App\Models\TobaccoStock;
use App\Models\VenueTobacco;
use App\Models\VenueTobaccoLog;
use Illuminate\Support\Facades\DB;

class StockService
{
    public const STEP = 0.5;
    public const AUTO_NOTE = 'Нулевой остаток (авто)';

    public function defaultBowl(): float
    {
        return (float) (Setting::get('bowl_size') ?: 25);
    }

    public function bowlFor(Order $order): float
    {
        return (float) ($order->bowl_size ?: $this->defaultBowl());
    }

    private function roundStep(float $v): float
    {
        return round($v / self::STEP) * self::STEP;
    }

    public function calcBowl(Order $order): array
    {
        $bowl = $this->bowlFor($order);
        $items = $order->items()->whereNull('deleted_at')->with('itemable')->get();

        $rows = [];
        foreach ($items as $item) {
            $fixed = null;
            if ($item->grams !== null && (float) $item->grams > 0) $fixed = (float) $item->grams;
            elseif ($item->fixed_percent !== null && (float) $item->fixed_percent > 0) $fixed = round($bowl * (float) $item->fixed_percent / 100, 2);
            $rows[] = ['item' => $item, 'fixed' => $fixed, 'grams' => $fixed ?? 0.0];
        }

        $fixedSum = collect($rows)->whereNotNull('fixed')->sum('fixed');
        $autoKeys = array_keys(array_filter($rows, fn ($r) => $r['fixed'] === null));
        $error = null;
        $shortfall = null;

        if ($fixedSum > $bowl + 0.001) {
            $error = 'Сумма зафиксированных граммов больше чаши (' . $this->fmt($fixedSum) . ' из ' . $this->fmt($bowl) . ' г)';
        }

        if ($autoKeys && !$error) {
            $each = ($bowl - $fixedSum) / count($autoKeys);
            foreach ($autoKeys as $k) $rows[$k]['grams'] = $this->roundStep($each);
            $total = collect($rows)->sum('grams');
            $diff = round($bowl - $total, 1);
            if (abs($diff) > 0.001) {
                $max = null;
                foreach ($autoKeys as $k) if ($max === null || $rows[$k]['grams'] >= $rows[$max]['grams']) $max = $k;
                $rows[$max]['grams'] = round($rows[$max]['grams'] + $diff, 1);
            }
        } elseif (!$autoKeys && $rows && !$error) {
            $short = round($bowl - $fixedSum, 1);
            if (abs($short) > 0.001) $shortfall = $short;
        }

        $result = [];
        $agg = [];
        foreach ($rows as $r) {
            $lines = $this->splitItem($r['item'], (float) $r['grams']);
            foreach ($lines as $l) $agg[$l['tobacco_id']] = ($agg[$l['tobacco_id']] ?? 0) + $l['grams'];
            $result[$r['item']->id] = ['grams' => (float) $r['grams'], 'fixed' => $r['fixed'] !== null, 'lines' => $lines];
        }

        $totals = array_map(fn ($g) => round($g, 1), $agg);
        $mine = $this->outstanding($order);
        $short = [];
        foreach ($totals as $tid => $g) {
            $st = TobaccoStock::where('tobacco_id', $tid)->first();
            if ($st && $st->available + ($mine[$tid] ?? 0) + 0.001 < $g) {
                $t = Tobacco::withTrashed()->with('brand')->find($tid);
                $short[] = trim(($t?->brand->name ?? '') . ' ' . $t?->name) . ': нужно ' . $this->fmt($g) . ' г, доступно ' . $this->fmt(max($st->available + ($mine[$tid] ?? 0), 0)) . ' г';
            }
        }

        return ['bowl' => $bowl, 'items' => $result, 'totals' => $totals, 'error' => $error, 'shortfall' => $shortfall, 'shortages' => $short, 'cost' => $this->bowlCost($totals)];
    }

    private function splitItem($item, float $grams): array
    {
        $it = $item->itemable;
        if ($it instanceof Tobacco) {
            return [['tobacco_id' => $it->id, 'name' => $it->name, 'grams' => round($grams, 1)]];
        }
        if (!$it) return [];

        $ings = $it->ingredients()->with('tobacco')->get()->filter(fn ($i) => $i->tobacco);
        if ($ings->isEmpty()) return [];

        $weights = $ings->map(fn ($i) => max((float) $i->grams, 0));
        if ($weights->sum() <= 0) $weights = $ings->map(fn () => 1.0);
        $total = $weights->sum();

        $lines = [];
        $choices = (array) ($item->alt_choices ?? []);
        foreach ($ings->values() as $idx => $ing) {
            $tid = $ing->tobacco_id;
            $name = $ing->tobacco->name;
            if (!empty($choices[$ing->id]) && (int) $choices[$ing->id] !== $tid) {
                $alt = $ing->alternatives()->with('tobacco')->where('tobacco_id', $choices[$ing->id])->first();
                if ($alt?->tobacco) { $tid = $alt->tobacco_id; $name = $alt->tobacco->name; }
            }
            $lines[] = ['ingredient_id' => $ing->id, 'tobacco_id' => $tid, 'name' => $name, 'grams' => $this->roundStep($grams * $weights->values()[$idx] / $total)];
        }
        $diff = round($grams - array_sum(array_column($lines, 'grams')), 1);
        if (abs($diff) > 0.001) {
            $max = 0;
            foreach ($lines as $k => $l) if ($l['grams'] >= $lines[$max]['grams']) $max = $k;
            $lines[$max]['grams'] = round($lines[$max]['grams'] + $diff, 1);
        }

        return $lines;
    }

    public function fmt(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',');
    }

    private function move(int $tobaccoId, string $type, float $grams, ?int $orderId = null, ?int $receiptId = null, ?string $note = null): void
    {
        $this->levelCache = null;
        $avg = TobaccoStock::where('tobacco_id', $tobaccoId)->value('avg_cost');
        $cost = in_array($type, ['consume', 'writeoff', 'correction']) && $avg !== null ? round(abs($grams) * (float) $avg, 2) : null;
        StockMovement::create([
            'tobacco_id' => $tobaccoId, 'type' => $type, 'grams' => $grams, 'cost' => $cost,
            'order_id' => $orderId, 'receipt_id' => $receiptId, 'user_id' => auth()->id(), 'note' => $note,
            'created_at' => now(),
        ]);
        $this->recalc($tobaccoId);
        if (in_array($type, ['consume', 'writeoff', 'correction'])) $this->autoDeactivate($tobaccoId);
    }

    private function autoDeactivate(int $tobaccoId): void
    {
        $stock = TobaccoStock::where('tobacco_id', $tobaccoId)->first();
        if (!$stock || $stock->on_hand > 0.05) return;
        $vt = VenueTobacco::where('tobacco_id', $tobaccoId)->where('is_active', true)->first();
        if (!$vt || !auth()->id()) return;
        $vt->update(['is_active' => false, 'deactivated_at' => now(), 'deactivated_by' => auth()->id(), 'deactivation_comment' => self::AUTO_NOTE]);
        VenueTobaccoLog::create(['venue_tobacco_id' => $vt->id, 'user_id' => auth()->id(), 'action' => 'deactivated', 'comment' => self::AUTO_NOTE]);
    }

    private function autoReactivate(int $tobaccoId): void
    {
        $vt = VenueTobacco::where('tobacco_id', $tobaccoId)->where('is_active', false)->where('deactivation_comment', self::AUTO_NOTE)->first();
        if (!$vt || !auth()->id()) return;
        $vt->update(['is_active' => true, 'deactivated_at' => null, 'deactivated_by' => null, 'deactivation_comment' => null]);
        VenueTobaccoLog::create(['venue_tobacco_id' => $vt->id, 'user_id' => auth()->id(), 'action' => 'activated', 'comment' => 'Приход на склад (авто)']);
    }

    public function recalc(int $tobaccoId): void
    {
        $this->levelCache = null;
        $sums = StockMovement::where('tobacco_id', $tobaccoId)
            ->selectRaw("SUM(CASE WHEN type IN ('receipt','consume','writeoff','correction') THEN grams ELSE 0 END) as on_hand, SUM(CASE WHEN type IN ('reserve','unreserve') THEN grams ELSE 0 END) as reserved")
            ->first();
        TobaccoStock::where('tobacco_id', $tobaccoId)->update([
            'on_hand' => round((float) $sums->on_hand, 1),
            'reserved' => round((float) $sums->reserved, 1),
        ]);
    }

    public function stock(int $tobaccoId): TobaccoStock
    {
        return TobaccoStock::firstOrCreate(['tobacco_id' => $tobaccoId]);
    }

    public function receive(Tobacco $tobacco, float $grams, ?string $supplier, ?string $date = null, ?float $price = null): array
    {
        return DB::transaction(function () use ($tobacco, $grams, $supplier, $date, $price) {
            $stock = $this->stock($tobacco->id);
            if ($supplier) $stock->update(['supplier' => $supplier]);

            $warning = null;
            if ($price) {
                $prev = TobaccoReceipt::where('tobacco_id', $tobacco->id)->whereNotNull('price_per_gram')->latest('id')->value('price_per_gram');
                $limit = (float) (Setting::get('stock_price_alert') ?: 10);
                if ($prev && $price > $prev * (1 + $limit / 100)) {
                    $warning = 'Цена выросла на ' . round(($price / $prev - 1) * 100) . '% (было ' . $this->fmt((float) $prev) . ', стало ' . $this->fmt($price) . ' за г)';
                }
                $held = max((float) $stock->on_hand, 0);
                $stock->update(['avg_cost' => $stock->avg_cost === null ? round($price, 2) : round((($held * (float) $stock->avg_cost) + $grams * $price) / ($held + $grams), 2)]);
            }

            $receipt = TobaccoReceipt::create([
                'tobacco_id' => $tobacco->id, 'grams' => $grams, 'price_per_gram' => $price, 'supplier' => $supplier ?: $stock->supplier,
                'received_at' => $date ?: now()->toDateString(), 'user_id' => auth()->id(),
            ]);
            $this->move($tobacco->id, 'receipt', $grams, null, $receipt->id);
            $this->autoReactivate($tobacco->id);

            return ['receipt' => $receipt, 'warning' => $warning];
        });
    }

    public function audit(Tobacco $tobacco, float $actual): StockAudit
    {
        return DB::transaction(function () use ($tobacco, $actual) {
            $stock = $this->stock($tobacco->id);
            $expected = (float) $stock->on_hand;
            $diff = round($actual - $expected, 1);
            $avg = $stock->avg_cost !== null ? (float) $stock->avg_cost : null;
            $audit = StockAudit::create([
                'tobacco_id' => $tobacco->id, 'expected' => $expected, 'actual' => $actual, 'diff' => $diff,
                'diff_cost' => $avg !== null ? round($diff * $avg, 2) : null, 'user_id' => auth()->id(),
            ]);
            if (abs($diff) > 0.001) $this->move($tobacco->id, 'correction', $diff, null, null, 'Ревизия');
            if ($actual > 0.05) $this->autoReactivate($tobacco->id);

            return $audit;
        });
    }

    private ?array $levelCache = null;

    public function defaultMin(): float
    {
        return (float) (Setting::get('stock_min_default') ?? 100);
    }

    public function minFor(TobaccoStock $st): float
    {
        return $st->min_stock > 0 ? (float) $st->min_stock : $this->defaultMin();
    }

    public function level(int $tobaccoId): ?array
    {
        $st = $this->stocks()[$tobaccoId] ?? null;
        if (!$st) return null;
        $av = (float) $st->available;
        if ($av <= 0.05) return ['key' => 'none', 'text' => 'Нет в наличии'];
        $low = $av <= max($this->minFor($st), $this->defaultBowl());
        return $low ? ['key' => 'low', 'text' => 'Мало'] : ['key' => 'ok', 'text' => 'Есть'];
    }

    private function stocks(): array
    {
        return $this->levelCache ??= TobaccoStock::all()->keyBy('tobacco_id')->all();
    }

    public function servingsLeft(int $tobaccoId, ?float $bowl = null): ?int
    {
        $stock = $this->stocks()[$tobaccoId] ?? null;
        if (!$stock) return null;
        $bowl = $bowl ?: $this->defaultBowl();
        return max(0, (int) floor($stock->available / $bowl));
    }

    public function servingsForMix($mix, ?float $bowl = null): ?int
    {
        $bowl = $bowl ?: $this->defaultBowl();
        $ings = $mix->ingredients->filter(fn ($i) => $i->tobacco);
        if ($ings->isEmpty()) return null;
        $w = $ings->map(fn ($i) => max((float) $i->grams, 0));
        if ($w->sum() <= 0) $w = $ings->map(fn () => 1.0);
        $min = null;
        foreach ($ings->values() as $idx => $ing) {
            $stock = $this->stocks()[$ing->tobacco_id] ?? null;
            if (!$stock) continue;
            $need = $bowl * $w->values()[$idx] / $w->sum();
            $n = $need > 0 ? (int) floor($stock->available / $need) : 0;
            $min = $min === null ? $n : min($min, $n);
        }

        return $min === null ? null : max(0, $min);
    }

    public function servingsText(?int $n): ?string
    {
        if ($n === null) return null;
        if ($n === 0) return 'нет на складе';
        $w = ($n % 10 === 1 && $n % 100 !== 11) ? 'чашу' : (in_array($n % 10, [2, 3, 4]) && !in_array($n % 100, [12, 13, 14]) ? 'чаши' : 'чаш');
        return 'хватит на ' . $n . ' ' . $w;
    }

    public function consumption(int $days): array
    {
        return StockMovement::where('type', 'consume')->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('tobacco_id, -SUM(grams) as g, -SUM(COALESCE(cost,0)) as c')->groupBy('tobacco_id')->get()
            ->mapWithKeys(fn ($r) => [$r->tobacco_id => ['g' => (float) $r->g, 'c' => (float) $r->c]])->all();
    }

    public function bowlCost(array $totals): float
    {
        $cost = 0.0;
        foreach ($totals as $tid => $g) {
            $avg = TobaccoStock::where('tobacco_id', $tid)->value('avg_cost');
            $cost += $g * (float) $avg;
        }

        return round($cost, 2);
    }

    public function writeOff(Tobacco $tobacco, float $grams, string $reason): void
    {
        DB::transaction(function () use ($tobacco, $grams, $reason) {
            $this->stock($tobacco->id);
            $this->move($tobacco->id, 'writeoff', -abs($grams), null, null, $reason);
        });
    }

    public function outstanding(Order $order): array
    {
        return StockMovement::where('order_id', $order->id)->whereIn('type', ['reserve', 'unreserve'])
            ->selectRaw('tobacco_id, SUM(grams) as g')->groupBy('tobacco_id')->havingRaw('SUM(grams) > 0.01')
            ->pluck('g', 'tobacco_id')->map(fn ($g) => (float) $g)->all();
    }

    public function release(Order $order): void
    {
        foreach ($this->outstanding($order) as $tid => $g) {
            $this->move($tid, 'unreserve', -$g, $order->id);
        }
    }

    public function reserve(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->release($order);
            $calc = $this->calcBowl($order);
            if ($calc['error']) throw new StockShortage($calc['error']);

            $short = [];
            foreach ($calc['totals'] as $tid => $g) {
                $stock = TobaccoStock::where('tobacco_id', $tid)->first();
                if ($stock && $stock->available + 0.001 < $g) {
                    $short[] = (fn ($t) => trim(($t?->brand->name ?? '') . ' ' . $t?->name))(Tobacco::withTrashed()->with('brand')->find($tid)) . ': нужно ' . $this->fmt($g) . ' г, доступно ' . $this->fmt($stock->available) . ' г';
                }
            }
            if ($short) throw new StockShortage('Не хватает табака. ' . implode('; ', $short));

            foreach ($calc['totals'] as $tid => $g) {
                if (TobaccoStock::where('tobacco_id', $tid)->exists() && $g > 0) $this->move($tid, 'reserve', $g, $order->id);
            }
        });
    }

    public function consume(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->release($order);
            $calc = $this->calcBowl($order);
            foreach ($calc['totals'] as $tid => $g) {
                if (TobaccoStock::where('tobacco_id', $tid)->exists() && $g > 0) $this->move($tid, 'consume', -$g, $order->id);
            }
        });
    }

    public function revertConsumption(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $net = StockMovement::where('order_id', $order->id)
                ->where(fn ($q) => $q->where('type', 'consume')->orWhere(fn ($q) => $q->where('type', 'correction')->where('note', 'Откат статуса')))
                ->selectRaw('tobacco_id, SUM(grams) as g')->groupBy('tobacco_id')->get();
            foreach ($net as $row) {
                if ((float) $row->g < -0.01) $this->move($row->tobacco_id, 'correction', -(float) $row->g, $order->id, null, 'Откат статуса');
            }
        });
    }

    public function onStatusChange(Order $order, string $old, string $new): void
    {
        if ($old === $new) return;

        if ($old === 'ready' && in_array($new, ['in_progress', 'pending'])) $this->revertConsumption($order);

        match (true) {
            $new === 'in_progress' => $this->reserve($order),
            $new === 'ready' => $this->consume($order),
            in_array($new, ['cancelled', 'pending', 'redo', 'closed']) => $this->release($order),
            default => null,
        };
    }
}
