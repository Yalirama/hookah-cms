<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Guest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $access = Setting::where('key', 'analytics_access')->value('value') ?? 'admin';
        if ($access === 'admin' && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $period = $request->get('period', 'today');
        $data = $this->buildAnalyticsData($request, $period);

        return view('analytics.index', array_merge(['period' => $period], $data));
    }

    public function contentPartial(Request $request)
    {
        $access = Setting::where('key', 'analytics_access')->value('value') ?? 'admin';
        if ($access === 'admin' && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $period = $request->get('period', 'today');
        $data = $this->buildAnalyticsData($request, $period);

        return view('analytics.partials.content', array_merge(['period' => $period], $data));
    }

    private function buildAnalyticsData(Request $request, string $period): array
    {
        [$from, $to] = $this->getPeriodDates($period);

        // --- ГОСТИ (карточка под текущий фильтр периода, используется в остальной части страницы) ---
        $guestsCount = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['closed', 'cancelled', 'redo'])
            ->count();

        $prevDiff = $to->diffInSeconds($from);
        $prevFrom = $from->copy()->subSeconds($prevDiff);
        $prevTo   = $from->copy();

        $guestsPrev = Order::whereBetween('created_at', [$prevFrom, $prevTo])
            ->whereIn('status', ['closed', 'cancelled', 'redo'])
            ->count();

        $guestsDiff = $guestsCount - $guestsPrev;
        $guestsDiffPct = $guestsPrev > 0 ? round(($guestsDiff / $guestsPrev) * 100) : null;

        $guestsOverview = $this->buildGuestsOverview();

        // --- НОВЫЕ vs ПОВТОРНЫЕ ---
        $closedOrders = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['closed', 'cancelled', 'redo'])
            ->whereNotNull('guest_id')
            ->with('guest')
            ->get();

        $newGuests      = 0;
        $returningGuests = 0;
        foreach ($closedOrders as $order) {
            $prevVisits = Order::where('guest_id', $order->guest_id)
                ->where('id', '<', $order->id)
                ->whereIn('status', ['closed', 'cancelled', 'redo'])
                ->count();
            if ($prevVisits === 0) $newGuests++;
            else $returningGuests++;
        }

        // --- КОГДА ПРИХОДЯТ (слоты) ---
        $timeSlots = Setting::where('key', 'time_slots')->value('value');
        $timeSlots = $timeSlots ? json_decode($timeSlots, true) : [];
        $slotCounts = [];
        $allOrders = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['closed', 'cancelled', 'redo'])
            ->get(['created_at']);

        foreach ($timeSlots as $key => $slot) {
            $count = 0;
            foreach ($allOrders as $order) {
                $hour = (int) $order->created_at->format('H');
                $fromHour = (int) explode(':', $slot['from'])[0];
                $toHour   = (int) explode(':', $slot['to'])[0];
                if ($fromHour < $toHour) {
                    if ($hour >= $fromHour && $hour < $toHour) $count++;
                } else {
                    if ($hour >= $fromHour || $hour < $toHour) $count++;
                }
            }
            $slotCounts[$key] = ['label' => $slot['label'], 'count' => $count];
        }

        // --- HEATMAP ---
        $heatmap = [];
        $days = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
        $heatmapSlots = [];
        foreach ($timeSlots as $slot) {
            $heatmapSlots[] = $slot;
        }
        foreach ($days as $i => $day) {
            foreach ($heatmapSlots as $slot) {
                $heatmap[$day][$slot['label']] = 0;
            }
        }
        foreach ($allOrders as $order) {
            $dow = $order->created_at->dayOfWeekIso;
            $dayLabel = $days[$dow - 1];
            $hour = (int) $order->created_at->format('H');
            foreach ($heatmapSlots as $slot) {
                $fromHour = (int) explode(':', $slot['from'])[0];
                $toHour   = (int) explode(':', $slot['to'])[0];
                $inSlot = $fromHour < $toHour
                    ? ($hour >= $fromHour && $hour < $toHour)
                    : ($hour >= $fromHour || $hour < $toHour);
                if ($inSlot) {
                    $heatmap[$dayLabel][$slot['label']]++;
                    break;
                }
            }
        }

        $topMixes = OrderItem::whereHasMorph('itemable', [\App\Models\Mix::class])
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to])
                ->whereIn('status', ['closed', 'cancelled', 'redo']))
            ->select('itemable_id', 'itemable_type')
            ->selectRaw('count(*) as cnt')
            ->groupBy('itemable_id', 'itemable_type')
            ->orderByDesc('cnt')
            ->limit(10)
            ->with('itemable')
            ->get();

        $topTobaccos = OrderItem::whereHasMorph('itemable', [\App\Models\Tobacco::class])
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to])
                ->whereIn('status', ['closed', 'cancelled', 'redo']))
            ->select('itemable_id', 'itemable_type')
            ->selectRaw('count(*) as cnt')
            ->groupBy('itemable_id', 'itemable_type')
            ->orderByDesc('cnt')
            ->limit(10)
            ->with('itemable.brand')
            ->get();

        $topBrands = OrderItem::whereHasMorph('itemable', [\App\Models\Tobacco::class])
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to])
                ->whereIn('status', ['closed', 'cancelled', 'redo']))
            ->with('itemable.brand')
            ->get()
            ->groupBy(fn($i) => $i->itemable?->brand?->name ?? '—')
            ->map(fn($g) => $g->count())
            ->sortDesc()
            ->take(3);

        $topMasters = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['closed', 'cancelled', 'redo'])
            ->selectRaw('created_by, count(*) as cnt')
            ->groupBy('created_by')
            ->orderByDesc('cnt')
            ->limit(10)
            ->with('creator')
            ->get();

        $topTables = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['closed', 'cancelled', 'redo'])
            ->selectRaw('table_id, count(*) as cnt')
            ->groupBy('table_id')
            ->orderByDesc('cnt')
            ->limit(10)
            ->with('table')
            ->get();

        $topStrengths = OrderItem::whereHasMorph('itemable', [\App\Models\Tobacco::class])
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to])
                ->whereIn('status', ['closed', 'cancelled', 'redo']))
            ->with('itemable')
            ->get()
            ->groupBy(fn($i) => $i->itemable?->strength ?? 'unknown')
            ->map(fn($g) => $g->count())
            ->sortDesc();

        $topFlavors = OrderItem::whereHasMorph('itemable', [\App\Models\Tobacco::class])
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to])
                ->whereIn('status', ['closed', 'cancelled', 'redo']))
            ->with('itemable.flavorTags')
            ->get()
            ->flatMap(fn($i) => $i->itemable?->flavorTags ?? collect())
            ->groupBy('id')
            ->map(fn($g) => ['name' => $g->first()->name, 'count' => $g->count()])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        $flavorsByDay = [];
        $dayNames7 = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];

        $ordersWithItems = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['closed', 'cancelled', 'redo'])
            ->with(['items' => fn($q) => $q->withTrashed()->with('itemable')])
            ->get();

        $allTobaccos = $ordersWithItems->flatMap(fn($o) => $o->items)
            ->map(fn($i) => $i->itemable)
            ->filter(fn($i) => $i instanceof \App\Models\Tobacco)
            ->unique('id')
            ->values();
        \Illuminate\Database\Eloquent\Collection::make($allTobaccos)->load('flavorTags');

        foreach ($dayNames7 as $day) {
            $flavorsByDay[$day] = [];
        }

        foreach ($ordersWithItems as $order) {
            $dow = $order->created_at->dayOfWeekIso;
            $day = $dayNames7[$dow - 1];
            foreach ($order->items as $item) {
                if ($item->itemable instanceof \App\Models\Tobacco) {
                    foreach ($item->itemable->flavorTags as $tag) {
                        $flavorsByDay[$day][] = $tag->name;
                    }
                }
            }
        }

        $inactiveDays = (int) (Setting::where('key', 'guest_inactive_days')->value('value') ?? 30);
        $inactiveStatus = $request->get('inactive_status', 'all');
        $inactiveGuests = $this->getInactiveGuests($inactiveDays, $inactiveStatus);

        return compact(
            'from', 'to',
            'guestsCount', 'guestsDiff', 'guestsDiffPct', 'guestsOverview',
            'newGuests', 'returningGuests',
            'slotCounts', 'heatmap', 'heatmapSlots', 'days',
            'topMixes', 'topTobaccos', 'topBrands',
            'topMasters', 'topTables',
            'inactiveGuests', 'inactiveDays', 'inactiveStatus', 'topStrengths', 'topFlavors', 'flavorsByDay', 'dayNames7'
        );
    }

    public function inactiveGuestsPartial(Request $request)
    {
        $access = Setting::where('key', 'analytics_access')->value('value') ?? 'admin';
        if ($access === 'admin' && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $inactiveDays = (int) (Setting::where('key', 'guest_inactive_days')->value('value') ?? 30);
        $inactiveStatus = $request->get('inactive_status', 'all');
        $period = $request->get('period', 'today');
        $inactiveGuests = $this->getInactiveGuests($inactiveDays, $inactiveStatus);

        return view('analytics.partials.inactive-guests', compact('inactiveGuests', 'inactiveDays', 'inactiveStatus', 'period'));
    }

    /**
     * Считает количество "закрытых" заказов в диапазоне дат — та же логика,
     * что используется во всех остальных местах контроллера для "гостей".
     */
    private function countClosedOrders(Carbon $from, Carbon $to): int
    {
        return Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['closed', 'cancelled', 'redo'])
            ->count();
    }

    /**
     * Собирает данные для карточки "ГОСТИ" сразу по 4 фиксированным периодам
     * (сегодня / неделя / месяц / год) — независимо от фильтра периода на странице.
     */
    private function buildGuestsOverview(): array
    {
        $now = now();

        // --- Сегодня vs вчера ---
        $todayFrom = $now->copy()->startOfDay();
        $todayTo   = $now->copy()->endOfDay();
        $todayPrevFrom = $todayFrom->copy()->subDay();
        $todayPrevTo   = $todayTo->copy()->subDay();

        // --- Эта неделя vs прошлая ---
        $weekFrom = $now->copy()->startOfWeek();
        $weekTo   = $now->copy()->endOfWeek();
        $weekPrevFrom = $weekFrom->copy()->subWeek();
        $weekPrevTo   = $weekTo->copy()->subWeek();

        // --- Этот месяц vs прошлый ---
        $monthFrom = $now->copy()->startOfMonth();
        $monthTo   = $now->copy()->endOfMonth();
        $monthPrevFrom = $monthFrom->copy()->subMonthNoOverflow()->startOfMonth();
        $monthPrevTo   = $monthPrevFrom->copy()->endOfMonth();

        // --- Этот год vs прошлый ---
        $yearFrom = $now->copy()->startOfYear();
        $yearTo   = $now->copy()->endOfYear();
        $yearPrevFrom = $yearFrom->copy()->subYear();
        $yearPrevTo   = $yearTo->copy()->subYear();

        $build = function (Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo) {
            $count = $this->countClosedOrders($from, $to);
            $prev  = $this->countClosedOrders($prevFrom, $prevTo);
            $diff  = $count - $prev;
            $pct   = $prev > 0 ? round(($diff / $prev) * 100) : null;

            return [
                'count'   => $count,
                'diff'    => $diff,
                'diffPct' => $pct,
            ];
        };

        $overview = [
            'today' => $build($todayFrom, $todayTo, $todayPrevFrom, $todayPrevTo),
            'week'  => $build($weekFrom, $weekTo, $weekPrevFrom, $weekPrevTo),
            'month' => $build($monthFrom, $monthTo, $monthPrevFrom, $monthPrevTo),
            'year'  => $build($yearFrom, $yearTo, $yearPrevFrom, $yearPrevTo),
        ];

        // Рекорд года: считаем гостей по всем прошлым годам (с года первого заказа)
        // и сравниваем с текущим — если текущий год уже лучший, покажем "Рекорд"
        $firstOrderAt = Order::min('created_at');
        $firstYear = $firstOrderAt ? Carbon::parse($firstOrderAt)->year : $now->year;

        $isRecordYear = true;
        for ($y = $firstYear; $y < $now->year; $y++) {
            $pastCount = $this->countClosedOrders(
                Carbon::create($y, 1, 1)->startOfYear(),
                Carbon::create($y, 1, 1)->endOfYear()
            );
            if ($pastCount >= $overview['year']['count']) {
                $isRecordYear = false;
                break;
            }
        }
        $overview['year']['isRecord'] = $isRecordYear;

        // Мини-бары: реальные данные за последние N периодов, текущий период — последний и активный
        $overview['today']['history'] = $this->buildHistory(7, fn($i) => [
            $now->copy()->subDays(6 - $i)->startOfDay(),
            $now->copy()->subDays(6 - $i)->endOfDay(),
        ]);

        $overview['week']['history'] = $this->buildHistory(4, fn($i) => [
            $now->copy()->subWeeks(3 - $i)->startOfWeek(),
            $now->copy()->subWeeks(3 - $i)->endOfWeek(),
        ]);

        $overview['month']['history'] = $this->buildHistory(12, fn($i) => [
            $now->copy()->subMonthsNoOverflow(11 - $i)->startOfMonth(),
            $now->copy()->subMonthsNoOverflow(11 - $i)->endOfMonth(),
        ]);

        return $overview;
    }

    /**
     * Строит массив из $total бакетов ['count' => int, 'height' => int (%), 'active' => bool],
     * где последний бакет — текущий период. Высота — относительно максимума в наборе,
     * чтобы визуально было видно "больше гостей — выше бар".
     */
    private function buildHistory(int $total, \Closure $rangeFor): array
    {
        $counts = [];
        for ($i = 0; $i < $total; $i++) {
            [$from, $to] = $rangeFor($i);
            $counts[] = $this->countClosedOrders($from, $to);
        }

        $max = max($counts) ?: 1;

        $history = [];
        foreach ($counts as $i => $count) {
            $history[] = [
                'count'  => $count,
                'height' => $count > 0 ? max(15, (int) round($count / $max * 100)) : 6,
                'active' => $i === $total - 1,
            ];
        }

        return $history;
    }

    private function getPeriodDates(string $period): array
    {
        return match($period) {
            'today'   => [now()->startOfDay(), now()->endOfDay()],
            'week'    => [now()->startOfWeek(), now()->endOfWeek()],
            'month'   => [now()->startOfMonth(), now()->endOfMonth()],
            'half'    => [now()->subMonths(6)->startOfDay(), now()->endOfDay()],
            'year'    => [now()->startOfYear(), now()->endOfYear()],
            'all'     => [Carbon::create(2000, 1, 1), now()->endOfDay()],
            default   => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    private function getInactiveGuests(string $inactiveDays, string $inactiveStatus)
    {
        return Guest::whereHas('orders', fn($q) => $q->whereIn('orders.status', ['closed', 'cancelled', 'redo']))
            ->with(['orders' => fn($q) => $q->whereIn('status', ['closed', 'cancelled', 'redo'])->latest('created_at')->limit(1)])
            ->get()
            ->map(function($guest) {
                $guest->last_visit = $guest->orders->first()?->created_at;
                return $guest;
            })
            ->filter(function($guest) use ($inactiveDays, $inactiveStatus) {
                if (!$guest->last_visit || !$guest->last_visit->lt(now()->subDays($inactiveDays))) return false;
                if ($inactiveStatus !== 'all' && $guest->status !== $inactiveStatus) return false;
                return true;
            })
            ->sortBy('last_visit')
            ->take(20)
            ->values();
    }
}