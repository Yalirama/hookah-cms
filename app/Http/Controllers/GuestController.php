<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\GuestLog;
use App\Models\GuestNote;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\Setting;
use App\Models\Mix;
use App\Models\Tobacco;
use App\Models\TobaccoBrand;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request)
    {
        $guests = $this->guestsList();

        $stats = $this->buildGuestStats();

        return view('guests.index', compact('guests', 'stats'));
    }

    private function guestsList()
    {
        return Guest::withCount([
            'orders as visits_count'        => fn($q) => $q->whereIn('status', ['closed', 'redo', 'cancelled']),
            'orders as closed_orders_count' => fn($q) => $q->where('status', 'closed'),
            'orders as visit_orders_count'  => fn($q) => $q->whereIn('status', ['closed', 'redo', 'cancelled']),
        ])->latest()->get();
    }

    private function buildGuestStats(): array
    {
        return [
            'total'     => Guest::count(),
            'vip'       => Guest::where('status_override', 'vip')->count(),
            'permanent' => Guest::where('status_override', 'permanent')->count(),
            'regular'   => Guest::where('status_override', 'regular')->count(),
            'new'       => Guest::whereNull('status_override')->orWhere('status_override', 'new')->count(),
        ];
    }

    public function statsPartial()
    {
        $stats = $this->buildGuestStats();
        return view('guests.partials.header-stats', compact('stats'));
    }

    public function listPartial()
    {
        $guests = $this->guestsList();

        return view('guests.partials.list-items', compact('guests'));
    }

    public function createForm()
    {
        return view('guests.partials.guest-form', ['guest' => null]);
    }

    public function editForm(Guest $guest)
    {
        return view('guests.partials.guest-form', compact('guest'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'nullable|string|unique:guests,username|max:255',
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:255',
            'note'     => 'nullable|string|max:1000',
        ]);

        $guest = Guest::create([
            'name'       => $request->name,
            'username'   => $request->username ?: null,
            'phone'      => $request->phone,
            'email'      => $request->email,
            'created_by' => auth()->id(),
        ]);

        GuestLog::create([
            'guest_id'   => $guest->id,
            'user_id'    => auth()->id(),
            'action'     => 'created',
            'new_value'  => $guest->toArray(),
            'created_at' => now(),
        ]);

        if ($request->note) {
            GuestNote::create([
                'guest_id' => $guest->id,
                'user_id'  => auth()->id(),
                'note'     => $request->note,
            ]);

            GuestLog::create([
                'guest_id'   => $guest->id,
                'user_id'    => auth()->id(),
                'action'     => 'note_added',
                'new_value'  => ['note' => $request->note],
                'created_at' => now(),
            ]);
        }

        broadcast(new \App\Events\GuestCreated($guest))->toOthers();

        if ($request->ajax()) {
            return response()->json(['message' => 'Гость добавлен', 'id' => $guest->id]);
        }

        return redirect()->route('guests.index')->with('success', 'Гость добавлен');
    }

    public function show(Guest $guest)
    {
        $guest->load(['creator', 'notes.user', 'logs.user', 'labels.label']);

        $timeSlots = Setting::get('time_slots', [
            'morning' => ['label' => 'Утро',  'from' => '06:00', 'to' => '12:00'],
            'day'     => ['label' => 'Обед',  'from' => '12:00', 'to' => '17:00'],
            'evening' => ['label' => 'Вечер', 'from' => '17:00', 'to' => '23:00'],
            'night'   => ['label' => 'Ночь',  'from' => '23:00', 'to' => '06:00'],
        ]);

        // Базовый запрос заказов гостя
        $ordersQuery = Order::where('guest_id', $guest->id);

        // Статистика
        $totalOrders  = (clone $ordersQuery)->count();
        $closedOrders = (clone $ordersQuery)->whereIn('status', ['closed'])->get();
        $redoCount    = (clone $ordersQuery)->where('status', 'redo')->count();
        $visitsCount  = (clone $ordersQuery)->whereIn('status', ['closed', 'redo', 'cancelled'])->count();

        // Средняя сессия (только закрытые заказы)
        $avgSession = $closedOrders->avg(fn($o) =>
            $o->started_at && $o->closed_at
                ? $o->started_at->diffInMinutes($o->closed_at)
                : null
        );

        // Обслуживаний за сессию
        $orderIds       = (clone $ordersQuery)->pluck('id');
        $totalServiced  = OrderLog::whereIn('order_id', $orderIds)->where('action', 'serviced')->count();
        $avgServiced    = $visitsCount > 0 ? round($totalServiced / $visitsCount, 1) : 0;

        // Дней с последнего визита
        $lastOrder    = (clone $ordersQuery)->whereIn('status', ['closed'])->latest('closed_at')->first();
        $lastVisitDays = $lastOrder ? now()->diffInDays($lastOrder->closed_at) : null;

        // Когда приходит — слоты
        $slotCounts = [];
        foreach ($timeSlots as $key => $slot) {
            $slotCounts[$key] = (clone $ordersQuery)
                ->whereRaw("TIME(created_at) >= ? AND TIME(created_at) < ?", [$slot['from'], $slot['to']])
                ->count();
        }

        // Дни недели
        $dayNames  = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        $dayCounts = [];
        for ($i = 0; $i <= 6; $i++) {
            $dayCounts[$dayNames[$i]] = (clone $ordersQuery)
                ->whereRaw("DAYOFWEEK(created_at) = ?", [$i + 1])
                ->count();
        }

        // Топ миксы
        $topMixes = OrderItem::withTrashed()
            ->whereIn('order_id', $orderIds)
            ->where('itemable_type', Mix::class)
            ->selectRaw('itemable_id, itemable_type, count(*) as total')
            ->groupBy('itemable_id', 'itemable_type')
            ->orderByDesc('total')
            ->with('itemable')
            ->limit(50)
            ->get();

        // Топ табаки
        $topTobaccos = OrderItem::withTrashed()
            ->whereIn('order_id', $orderIds)
            ->where('itemable_type', Tobacco::class)
            ->selectRaw('itemable_id, itemable_type, count(*) as total')
            ->groupBy('itemable_id', 'itemable_type')
            ->orderByDesc('total')
            ->with('itemable.brand')
            ->limit(50)
            ->get();

        // Топ бренды (через табаки)
        $topBrands = OrderItem::withTrashed()
            ->whereIn('order_id', $orderIds)
            ->where('itemable_type', Tobacco::class)
            ->selectRaw('itemable_id, itemable_type, count(*) as total')
            ->groupBy('itemable_id', 'itemable_type')
            ->get()
            ->groupBy(fn($i) => $i->itemable?->brand_id)
            ->map(fn($group) => [
                'brand' => $group->first()->itemable?->brand,
                'total' => $group->sum('total'),
            ])
            ->sortByDesc('total')
            ->take(50)
            ->values();

        // Топ мастера
        $topMasters = Order::where('guest_id', $guest->id)
            ->selectRaw('created_by, count(*) as total')
            ->groupBy('created_by')
            ->orderByDesc('total')
            ->with('creator')
            ->limit(50)
            ->get();

        // Любимое место
        $favTables = Order::where('guest_id', $guest->id)
            ->selectRaw('table_id, count(*) as total')
            ->groupBy('table_id')
            ->orderByDesc('total')
            ->with('table')
            ->get();
        $totalTableVisits = $favTables->sum('total');

        // История визитов
        $visitHistory = (clone $ordersQuery)
            ->with('table', 'creator')
            ->latest()
            ->paginate(20);

        return view('guests.show', compact(
            'guest', 'timeSlots', 'slotCounts', 'dayCounts',
            'totalOrders', 'visitsCount', 'redoCount', 'lastVisitDays',
            'avgSession', 'avgServiced',
            'topMixes', 'topTobaccos', 'topBrands', 'topMasters',
            'favTables', 'totalTableVisits',
            'visitHistory'
        ));
    }

    public function update(Request $request, Guest $guest)
    {
        if ($guest->created_by !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'nullable|string|unique:guests,username,' . $guest->id . '|max:255',
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:255',
        ]);

        $old = $guest->only(['name', 'username', 'phone', 'email']);

        $guest->update([
            'name'     => $request->name,
            'username' => $request->username ?: $guest->username,
            'phone'    => $request->phone,
            'email'    => $request->email,
        ]);

        GuestLog::create([
            'guest_id'   => $guest->id,
            'user_id'    => auth()->id(),
            'action'     => 'updated',
            'old_value'  => $old,
            'new_value'  => $guest->only(['name', 'username', 'phone', 'email']),
            'created_at' => now(),
        ]);

        if ($request->ajax()) {
            return response()->json(['message' => 'Данные гостя обновлены']);
        }

        return $this->done('Данные гостя обновлены');
    }

    public function setStatus(Request $request, Guest $guest)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $request->validate([
            'status_override' => 'nullable|in:new,regular,permanent,vip',
        ]);

        $old = $guest->status_override;
        $guest->update(['status_override' => $request->status_override]);

        GuestLog::create([
            'guest_id'   => $guest->id,
            'user_id'    => auth()->id(),
            'action'     => 'status_changed',
            'old_value'  => ['status' => $old],
            'new_value'  => ['status' => $request->status_override],
            'created_at' => now(),
        ]);

        return $this->done('Статус гостя обновлён');
    }

    private function done(string $message)
    {
        return request()->ajax()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }

    public function storeNote(Request $request, Guest $guest)
    {
        $request->validate(['note' => 'required|string|max:1000']);

        $note = GuestNote::create([
            'guest_id' => $guest->id,
            'user_id'  => auth()->id(),
            'note'     => $request->note,
        ]);

        GuestLog::create([
            'guest_id'   => $guest->id,
            'user_id'    => auth()->id(),
            'action'     => 'note_added',
            'new_value'  => ['note' => $request->note],
            'created_at' => now(),
        ]);

        return request()->ajax()
        ? response()->json(['message' => 'Заметка добавлена', 'delete_url' => route('guests.notes.destroy', [$guest, $note])])
        : back()->with('success', 'Заметка добавлена');
    }

    public function destroyNote(Guest $guest, GuestNote $note)
    {
        if ($note->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        GuestLog::create([
            'guest_id'   => $guest->id,
            'user_id'    => auth()->id(),
            'action'     => 'note_deleted',
            'old_value'  => ['note' => $note->note],
            'created_at' => now(),
        ]);

        $note->delete();
        return $this->done('Заметка удалена');
    }

    public function addLabel(Request $request, Guest $guest)
    {
        $request->validate([
            'label_id' => 'required|exists:labels,id',
            'type'     => 'required|in:positive,neutral,negative',
        ]);

        $label = \App\Models\Label::findOrFail($request->label_id);

        $existing = \App\Models\GuestLabel::where('guest_id', $guest->id)
            ->where('label_id', $request->label_id)
            ->whereNull('removed_at')
            ->first();

        if ($existing) {
            $oldType = $existing->type;

            if ($oldType === $request->type) {
                return request()->ajax()
                    ? response()->json(['message' => 'Этот ярлык уже добавлен в эту категорию'], 422)
                    : back()->with('error', 'Этот ярлык уже добавлен в эту категорию');
            }

            $existing->update([
                'removed_by' => auth()->id(),
                'removed_at' => now(),
            ]);

            $new = \App\Models\GuestLabel::create([
                'guest_id' => $guest->id,
                'label_id' => $request->label_id,
                'type'     => $request->type,
                'added_by' => auth()->id(),
                'added_at' => now(),
            ]);

            GuestLog::create([
                'guest_id'   => $guest->id,
                'user_id'    => auth()->id(),
                'action'     => 'updated',
                'old_value'  => ['label' => $label->name, 'type' => $oldType],
                'new_value'  => ['label' => $label->name, 'type' => $request->type],
                'created_at' => now(),
            ]);

            return $this->labelAdded($new, 'Категория ярлыка изменена');
        }

        $new = \App\Models\GuestLabel::create([
            'guest_id' => $guest->id,
            'label_id' => $request->label_id,
            'type'     => $request->type,
            'added_by' => auth()->id(),
            'added_at' => now(),
        ]);

        GuestLog::create([
            'guest_id'   => $guest->id,
            'user_id'    => auth()->id(),
            'action'     => 'updated',
            'old_value'  => [],
            'new_value'  => ['label_added' => $label->name, 'type' => $request->type],
            'created_at' => now(),
        ]);

        return $this->labelAdded($new, 'Ярлык добавлен');
    }

    private function labelAdded(\App\Models\GuestLabel $new, string $message)
    {
        return request()->ajax()
            ? response()->json(['message' => $message, 'id' => $new->id])
            : back()->with('success', $message);
    }

    public function removeLabel(Request $request, Guest $guest, \App\Models\GuestLabel $guestLabel)
    {
        $label   = $guestLabel->label;
        $oldType = $guestLabel->type;

        $guestLabel->update([
            'removed_by' => auth()->id(),
            'removed_at' => now(),
        ]);

        GuestLog::create([
            'guest_id'   => $guest->id,
            'user_id'    => auth()->id(),
            'action'     => 'updated',
            'old_value'  => ['label_removed' => $label?->name, 'type' => $oldType],
            'new_value'  => [],
            'created_at' => now(),
        ]);

        return $this->done('Ярлык снят');
    }

    public function sheet(Guest $guest)
    {
        $guest->load(['creator', 'notes.user']);
        $data = $this->buildGuestCardData($guest);

        return view('guests.partials.guest-sheet', array_merge(['guest' => $guest], $data));
    }

    private function buildGuestCardData(Guest $guest): array
    {
        $timeSlots = Setting::get('time_slots', [
            'morning' => ['label' => 'Утро',  'from' => '06:00', 'to' => '12:00'],
            'day'     => ['label' => 'Обед',  'from' => '12:00', 'to' => '17:00'],
            'evening' => ['label' => 'Вечер', 'from' => '17:00', 'to' => '23:00'],
            'night'   => ['label' => 'Ночь',  'from' => '23:00', 'to' => '06:00'],
        ]);

        $ordersQuery = Order::where('guest_id', $guest->id);

        $totalOrders  = (clone $ordersQuery)->count();
        $closedOrders = (clone $ordersQuery)->whereIn('status', ['closed'])->get();
        $redoCount    = (clone $ordersQuery)->where('status', 'redo')->count();
        $visitsCount  = (clone $ordersQuery)->whereIn('status', ['closed', 'redo', 'cancelled'])->count();

        $avgSession = $closedOrders->avg(fn($o) =>
            $o->started_at && $o->closed_at ? $o->started_at->diffInMinutes($o->closed_at) : null
        );

        $orderIds      = (clone $ordersQuery)->pluck('id');
        $totalServiced = OrderLog::whereIn('order_id', $orderIds)->where('action', 'serviced')->count();
        $avgServiced   = $visitsCount > 0 ? round($totalServiced / $visitsCount, 1) : 0;

        $lastOrder     = (clone $ordersQuery)->whereIn('status', ['closed'])->latest('closed_at')->first();
        $lastVisitDays = $lastOrder ? (int) floor(abs(now()->diffInDays($lastOrder->closed_at))) : null;

        $slotCounts = [];
        foreach ($timeSlots as $key => $slot) {
            $slotCounts[$key] = (clone $ordersQuery)
                ->where(function ($q) use ($slot) {
                    if ($slot['from'] < $slot['to']) {
                        $q->whereRaw("TIME(created_at) >= ? AND TIME(created_at) < ?", [$slot['from'], $slot['to']]);
                    } else {
                        $q->whereRaw("(TIME(created_at) >= ? OR TIME(created_at) < ?)", [$slot['from'], $slot['to']]);
                    }
                })->count();
        }

        $dayNames = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        $dayCounts = [];
        for ($i = 0; $i <= 6; $i++) {
            $dayCounts[$dayNames[$i]] = (clone $ordersQuery)
                ->whereRaw("DAYOFWEEK(created_at) = ?", [$i + 1])
                ->count();
        }

        $topMixes = OrderItem::withTrashed()
            ->whereIn('order_id', $orderIds)->where('itemable_type', Mix::class)
            ->selectRaw('itemable_id, itemable_type, count(*) as total')->groupBy('itemable_id', 'itemable_type')
            ->orderByDesc('total')->with('itemable')->limit(50)->get();

        $topTobaccos = OrderItem::withTrashed()
            ->whereIn('order_id', $orderIds)->where('itemable_type', Tobacco::class)
            ->selectRaw('itemable_id, itemable_type, count(*) as total')->groupBy('itemable_id', 'itemable_type')
            ->orderByDesc('total')->with('itemable.brand')->limit(50)->get();

        $topBrands = OrderItem::withTrashed()
            ->whereIn('order_id', $orderIds)->where('itemable_type', Tobacco::class)
            ->selectRaw('itemable_id, itemable_type, count(*) as total')->groupBy('itemable_id', 'itemable_type')
            ->get()
            ->groupBy(fn($i) => $i->itemable?->brand_id)
            ->map(fn($group) => ['brand' => $group->first()->itemable?->brand, 'total' => $group->sum('total')])
            ->sortByDesc('total')->take(50)->values();

        $topMasters = Order::where('guest_id', $guest->id)
            ->selectRaw('created_by, count(*) as total')->groupBy('created_by')
            ->orderByDesc('total')->with('creator')->limit(50)->get();

        $favTables = Order::where('guest_id', $guest->id)
            ->selectRaw('table_id, count(*) as total')->groupBy('table_id')
            ->orderByDesc('total')->with('table')->get();
        $totalTableVisits = $favTables->sum('total');

        $maxSlot = max($slotCounts) ?: 1;
        $maxDay  = max($dayCounts) ?: 1;

        return compact(
            'timeSlots', 'slotCounts', 'dayCounts', 'maxSlot', 'maxDay',
            'totalOrders', 'visitsCount', 'redoCount', 'lastVisitDays',
            'avgSession', 'avgServiced',
            'topMixes', 'topTobaccos', 'topBrands', 'topMasters',
            'favTables', 'totalTableVisits'
        );
    }

    public function visitHistorySheet(Guest $guest)
    {
        $orders = Order::where('guest_id', $guest->id)
            ->with('table', 'creator')
            ->orderByDesc('id')
            ->limit(21)
            ->get();

        $hasMore = $orders->count() > 20;
        $orders = $orders->take(20);

        return view('guests.partials.visit-history-list', compact('guest', 'orders', 'hasMore'));
    }

    public function visitHistoryMore(Request $request, Guest $guest)
    {
        $lastId = (int) $request->get('last_id');

        $orders = Order::where('guest_id', $guest->id)
            ->where('id', '<', $lastId)
            ->with('table', 'creator')
            ->orderByDesc('id')
            ->limit(21)
            ->get();

        $hasMore = $orders->count() > 20;
        $orders = $orders->take(20);

        return view('guests.partials.visit-history-rows', compact('orders', 'hasMore'));
    }

    public function labelsSheet(Guest $guest)
    {
        $labels = \App\Models\Label::where('is_active', true)->orderBy('name')->get();
        $assigned = $guest->allLabels()->whereNull('removed_at')->get()->keyBy('label_id');

        return view('guests.partials.labels-sheet', compact('guest', 'labels', 'assigned'));
    }

    public function historySheet(Guest $guest)
    {
        $logs = GuestLog::where('guest_id', $guest->id)
            ->with('user')
            ->orderByDesc('id')
            ->get();

        return view('guests.partials.history-list', compact('guest', 'logs'));
    }
}