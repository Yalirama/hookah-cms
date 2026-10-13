<?php

namespace App\Http\Controllers;

use App\Models\Mix;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\OrderTableHistory;
use App\Models\Table;
use App\Models\Tobacco;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with([
            'creator', 'table',
            'guest' => fn($q) => $q->withCount([
                'orders as closed_orders_count' => fn($q) => $q->where('status', 'closed'),
                'orders as visit_orders_count'  => fn($q) => $q->whereIn('status', ['closed', 'redo', 'cancelled']),
            ]),
            'items' => fn($q) => $q->withTrashed(),
            'items.itemable' => fn($q) => $q->morphWith([
                Tobacco::class => ['brand'],
                Mix::class => ['ingredients.tobacco.brand'],
            ]),
        ]);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                ->orWhere('guest_comment', 'like', "%$search%")
                ->orWhereHas('creator', fn($q) => $q->where('name', 'like', "%$search%"))
                ->orWhereHas('table', fn($q) => $q->where('name', 'like', "%$search%"))
                ->orWhereHas('items', function ($q) use ($search) {
                    $q->withTrashed()->whereHasMorph('itemable', [Mix::class, Tobacco::class], fn($q) => $q->where('name', 'like', "%$search%"));
                })
                ->orWhereHas('guest', fn($q) => $q->where('name', 'like', "%$search%")
                    ->orWhere('username', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($request->get('with_guest') === '1') {
            $query->whereNotNull('guest_id');
        } elseif ($request->get('with_guest') === '0') {
            $query->whereNull('guest_id');
        }
        
        $orders = $query->latest()->limit(150)->get();

        $stats = [
            'today'  => Order::whereDate('created_at', today())->count(),
            'week'   => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'month'  => Order::whereMonth('created_at', now()->month)->count(),
            'total'  => Order::count(),
        ];

        \Carbon\Carbon::setLocale('ru');
        $ordersByDay = $orders->groupBy(fn($o) => $o->created_at->toDateString())
            ->mapWithKeys(function ($group, $date) {
                $carbon = \Carbon\Carbon::parse($date);
                $label = $carbon->isToday()
                    ? 'Сегодня - ' . $carbon->translatedFormat('d M Y')
                    : ($carbon->isYesterday()
                        ? 'Вчера - ' . $carbon->translatedFormat('d M Y')
                        : $carbon->translatedFormat('d M Y'));
                return [$label => $group];
            });

        return view('orders.index', compact('ordersByDay', 'stats'));
    }

    public function create()
    {
        $tables  = Table::where('is_active', true)->with('type')->get();
        $mixes   = Mix::where('is_active', true)->with('ingredients.tobacco.brand')->get();
        $tobaccos = Tobacco::whereHas('venueTobacco', fn($q) => $q->where('is_active', true))
            ->with('brand', 'flavorTags')
            ->get();

        return view('orders.create', compact('tables', 'mixes', 'tobaccos'));
    }

    public function store(Request $request)
    {
        
        $request->validate([
            'table_id'         => 'required|exists:tables,id',
            'guest_comment'    => 'nullable|string|max:500',
            'started_at'       => 'nullable|date',
            'new_guest_name'   => 'nullable|string|max:255',
            'new_guest_username' => 'nullable|string|unique:guests,username|max:255',
            'new_guest_phone'  => 'nullable|string|max:20',
            'new_guest_email'  => 'nullable|email|max:255',
        ]);

        // Создаём нового гостя если указано имя
        $guestId = $request->guest_id ?: null;
        $newGuest = null;

        if ($request->filled('new_guest_name')) {
            $newGuest = \App\Models\Guest::create([
                'name'       => $request->new_guest_name,
                'username'   => $request->new_guest_username ?: null,
                'phone'      => $request->new_guest_phone,
                'email'      => $request->new_guest_email,
                'created_by' => auth()->id(),
            ]);

            $this->safeBroadcast(fn() => broadcast(new \App\Events\GuestCreated($newGuest))->toOthers());
            $guestId = $newGuest->id;
        }

        $order = Order::create([
            'name'          => $request->name ?: null,
            'table_id'      => $request->table_id,
            'created_by'    => auth()->id(),
            'status'        => 'pending',
            'guest_id'      => $guestId,
            'guest_comment' => $request->guest_comment,
            'started_at'    => $request->started_at ?: now(),
        ]);

        // История стола
        OrderTableHistory::create([
            'order_id'    => $order->id,
            'table_id'    => $request->table_id,
            'user_id'     => auth()->id(),
            'assigned_at' => now(),
        ]);

        OrderLog::create([
            'order_id'   => $order->id,
            'user_id'    => auth()->id(),
            'action'     => 'created',
            'new_value'  => ['status' => 'pending', 'table_id' => $request->table_id],
            'created_at' => now(),
        ]);

        // Лог гостя — после создания заказа чтобы указать номер
        if ($newGuest) {
            \App\Models\GuestLog::create([
                'guest_id'   => $newGuest->id,
                'user_id'    => auth()->id(),
                'action'     => 'created',
                'new_value'  => [
                    'name'     => $newGuest->name,
                    'username' => $newGuest->username,
                    'phone'    => $newGuest->phone,
                    'email'    => $newGuest->email,
                    'context'  => 'order',
                    'order_id' => $order->id,
                ],
                'created_at' => now(),
            ]);
        }

        if ($guestId) {
            OrderLog::create([
                'order_id'   => $order->id,
                'user_id'    => auth()->id(),
                'action'     => 'guest_changed',
                'old_value'  => null,
                'new_value'  => ['guest_id' => $guestId, 'guest_name' => $order->guest?->name ?? $request->new_guest_name],
                'created_at' => now(),
            ]);
        }

        $this->broadcastOrder($order);

        return redirect()->route('orders.index')->with('success', 'Заказ #' . $order->id . ' создан');
    }

    public function addItem(Request $request, Order $order)
    {
        $request->validate([
            'items'           => 'required|array|min:1',
            'items.*.type'    => 'required|in:mix,tobacco',
            'items.*.id'      => 'required|integer',
            'items.*.grams'   => 'nullable|string|max:50',
            'comment'         => 'nullable|string|max:500',
        ]);

        if (!auth()->user()->hasRole('admin') && !$order->isActive()) {
            abort(403, 'Заказ закрыт');
        }

        foreach ($request->items as $itemData) {
            $itemableType = $itemData['type'] === 'mix' ? Mix::class : Tobacco::class;
            $itemable     = $itemableType::findOrFail($itemData['id']);

            OrderItem::create([
                'order_id'      => $order->id,
                'added_by'      => auth()->id(),
                'itemable_type' => $itemableType,
                'itemable_id'   => $itemData['id'],
                'grams'         => $itemData['grams'] ?? null,
            ]);

            OrderLog::create([
                'order_id'   => $order->id,
                'user_id'    => auth()->id(),
                'action'     => 'item_added',
                'new_value'  => [
                    'item_type' => $itemData['type'],
                    'item_name' => $itemable->name,
                    'grams'     => $itemData['grams'] ?? null,
                ],
                'comment'    => $request->comment,
                'created_at' => now(),
            ]);
        }

        $this->broadcastOrder($order);

        return back()->with('success', 'Позиции добавлены');
    }

    public function removeItem(Request $request, Order $order, OrderItem $item)
    {
        $request->validate([
            'comment' => 'nullable|string|max:500',
        ]);

        // Проверка прав
        if (!auth()->user()->hasRole('admin') && !$order->isActive()) {
            abort(403, 'Заказ закрыт');
        }

        $itemName = $item->itemable?->name ?? '—';

        $item->delete(); // soft delete

        OrderLog::create([
            'order_id'   => $order->id,
            'user_id'    => auth()->id(),
            'action'     => 'item_removed',
            'old_value'  => ['item_name' => $itemName],
            'comment'    => $request->comment,
            'created_at' => now(),
        ]);

        $this->broadcastOrder($order);

        return back()->with('success', 'Позиция удалена');
    }

    public function logService(Request $request, Order $order)
    {
        $request->validate([
            'comment' => 'nullable|string|max:500',
        ]);

        OrderLog::create([
            'order_id'   => $order->id,
            'user_id'    => auth()->id(),
            'action'     => 'serviced',  // отдельный action
            'old_value'  => null,
            'new_value'  => null,
            'comment'    => $request->comment,
            'created_at' => now(),
        ]);

        $this->safeBroadcast(fn() => broadcast(new \App\Events\OrderUpdated($order))->toOthers());
        $this->safeBroadcast(fn() => broadcast(new \App\Events\ServiceReminderCheck()));

        return back()->with('success', 'Обслуживание зафиксировано');
    }

    public function liveData(Order $order)
    {
        $order->load([
            'logs' => fn($q) => $q->with('user')->latest('created_at')->limit(30),
            'tableHistory' => fn($q) => $q->with(['table', 'user'])->orderBy('assigned_at'),
        ]);

        $statusLabels = [
            'pending'     => 'Новый',
            'in_progress' => 'В работе',
            'ready'       => 'Готов',
            'served'      => 'У гостя',
            'redo'        => 'Переделка',
            'closed'      => 'Закрыт',
            'cancelled'   => 'Отменён',
        ];

        $logs = $order->logs->map(fn($log) => [
            'action'       => $log->action,
            'action_label' => [
                'created'        => 'Создан',
                'status_changed' => 'Статус изменён',
                'serviced'       => 'Обслуживание гостя',
                'table_changed'  => 'Стол изменён',
                'item_added'     => 'Позиция добавлена',
                'item_removed'   => 'Позиция удалена',
                'rolled_back'    => 'Статус откатен',
            ][$log->action] ?? $log->action,
            'icon'         => ['created'=>'📋','status_changed'=>'🔄','serviced'=>'🫧','table_changed'=>'🪑','item_added'=>'➕','item_removed'=>'➖','rolled_back'=>'↩️'][$log->action] ?? '•',
            'user'         => $log->user?->name ?? '—',
            'comment'      => $log->comment,
            'old_value'    => $log->old_value,
            'new_value'    => $log->new_value,
            'created_at'   => $log->created_at->format('d.m.Y H:i:s'),
        ])->toArray();

        $tableHistory = $order->tableHistory->map(fn($h) => [
            'table'       => $h->table?->name ?? '—',
            'user'        => $h->user?->name ?? '—',
            'assigned_at' => $h->assigned_at->format('H:i:s'),
            'removed_at'  => $h->removed_at?->format('H:i:s'),
        ])->toArray();

        return response()->json([
            'logs'          => $logs,
            'table_history' => $tableHistory,
            'status_labels' => $statusLabels,
        ]);
    }

    public function show(Order $order)
    {
        [$order, $tables, $mixes, $tobaccos, $masters] = $this->loadOrderForView($order);
        return view('orders.show', compact('order', 'tables', 'mixes', 'tobaccos', 'masters'));
    }

    public function changeStatus(Request $request, Order $order)
    {
        $request->validate([
            'status'  => 'required|in:in_progress,ready,served,redo,closed,cancelled',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($request->status !== 'cancelled' && $order->items()->whereNull('deleted_at')->doesntExist()) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Нельзя изменить статус — состав заказа пуст.'], 422);
            }
            return back()->withErrors(['status' => 'Нельзя изменить статус — состав заказа пуст.']);
        }

        $oldStatus = $order->status;
        $newStatus = $request->status;

        $order->update([
            'status'    => $newStatus,
            'closed_at' => in_array($newStatus, ['closed', 'cancelled', 'redo']) ? now() : $order->closed_at,
        ]);

        $this->broadcastOrder($order);
        $this->safeBroadcast(fn() => broadcast(new \App\Events\ServiceReminderCheck()));

        OrderLog::create([
            'order_id'   => $order->id,
            'user_id'    => auth()->id(),
            'action'     => 'status_changed',
            'old_value'  => ['status' => $oldStatus],
            'new_value'  => ['status' => $newStatus],
            'comment'    => $request->comment,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Статус обновлён');
    }

    public function rollbackStatus(Request $request, Order $order)
    {
        $request->validate([
            'comment' => 'required|string|max:500',
        ]);

        $lastStatusLog = $order->logs()
            ->where('action', 'status_changed')
            ->reorder()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $previousStatus = $lastStatusLog?->old_value['status'] ?? 'pending';

        $oldStatus = $order->status;

        $order->update([
            'status'    => $previousStatus,
            'closed_at' => in_array($previousStatus, ['closed', 'cancelled', 'redo']) ? $order->closed_at : null,
        ]);

        OrderLog::create([
            'order_id'   => $order->id,
            'user_id'    => auth()->id(),
            'action'     => 'rolled_back',
            'old_value'  => ['status' => $oldStatus],
            'new_value'  => ['status' => $previousStatus],
            'comment'    => $request->comment,
            'created_at' => now(),
        ]);

        $this->broadcastOrder($order);

        return back()->with('success', 'Статус откатен');
    }

    public function changeTable(Request $request, Order $order)
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
            'comment'  => 'nullable|string|max:500',
        ]);

        $oldTableId = $order->table_id;

        // Закрываем старую запись в истории
        OrderTableHistory::where('order_id', $order->id)
            ->whereNull('removed_at')
            ->update(['removed_at' => now()]);

        $order->update(['table_id' => $request->table_id]);

        // Новая запись в истории
        OrderTableHistory::create([
            'order_id'    => $order->id,
            'table_id'    => $request->table_id,
            'user_id'     => auth()->id(),
            'assigned_at' => now(),
        ]);

        OrderLog::create([
            'order_id'   => $order->id,
            'user_id'    => auth()->id(),
            'action'     => 'table_changed',
            'old_value'  => ['table_id' => $oldTableId],
            'new_value'  => ['table_id' => $request->table_id],
            'comment'    => $request->comment,
            'created_at' => now(),
        ]);

        $this->broadcastOrder($order);

        return back()->with('success', 'Стол изменён');
    }

    public function searchGuests(Request $request)
    {
        $q = mb_strtolower(trim($request->get('q', '')), 'UTF-8');

        $guests = \App\Models\Guest::whereRaw('LOWER(name) LIKE ?', ["%$q%"])
            ->orWhereRaw('LOWER(username) LIKE ?', ["%$q%"])
            ->orWhereRaw('LOWER(phone) LIKE ?', ["%$q%"])
            ->orWhereRaw('LOWER(email) LIKE ?', ["%$q%"])
            ->withCount(['orders as visits_count' => fn($q) => $q->whereIn('status', ['closed', 'redo', 'cancelled'])])
            ->limit(10)
            ->get()
            ->map(fn($g) => [
                'id'           => $g->id,
                'name'         => $g->name,
                'username'     => '@' . $g->username,
                'status'       => $g->status,
                'status_label' => $g->status_label,
                'visits_count' => $g->visits_count,
                'phone'        => $g->phone,
            ]);

        return response()->json($guests);
    }

    public function attachGuest(Request $request, Order $order)
    {
        $request->validate([
            'guest_id' => 'required|exists:guests,id',
        ]);

        $oldGuestId = $order->guest_id;
        $newGuest   = \App\Models\Guest::findOrFail($request->guest_id);
        $oldGuest   = $oldGuestId ? \App\Models\Guest::find($oldGuestId) : null;

        $order->update(['guest_id' => $request->guest_id]);

        OrderLog::create([
            'order_id'   => $order->id,
            'user_id'    => auth()->id(),
            'action'     => 'guest_changed',
            'old_value'  => $oldGuest ? ['guest_id' => $oldGuest->id, 'guest_name' => $oldGuest->name] : null,
            'new_value'  => ['guest_id' => $newGuest->id, 'guest_name' => $newGuest->name],
            'created_at' => now(),
        ]);

        return back()->with('success', 'Гость привязан');
    }

    public function detachGuest(Request $request, Order $order)
    {
        $oldGuest = $order->guest;

        $order->update(['guest_id' => null]);

        OrderLog::create([
            'order_id'   => $order->id,
            'user_id'    => auth()->id(),
            'action'     => 'guest_changed',
            'old_value'  => $oldGuest ? ['guest_id' => $oldGuest->id, 'guest_name' => $oldGuest->name] : null,
            'new_value'  => null,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Гость откреплён');
    }

    public function updateLogMaster(Request $request, Order $order, OrderLog $log)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $oldUser = \App\Models\User::find($log->user_id);
        $newUser = \App\Models\User::find($request->user_id);

        $log->update(['user_id' => $request->user_id]);

        if ($log->action === 'created') {
            $order->update(['created_by' => $request->user_id]);
        }

        OrderLog::create([
            'order_id'   => $order->id,
            'user_id'    => auth()->id(),
            'action'     => 'master_changed',
            'old_value'  => ['user_id' => $oldUser?->id, 'user_name' => $oldUser?->name],
            'new_value'  => ['user_id' => $newUser?->id, 'user_name' => $newUser?->name, 'log_id' => $log->id],
            'created_at' => now(),
        ]);

        $this->broadcastOrder($order);

        return back()->with('success', 'Мастер действия обновлён');
    }

    public function serviceAlerts()
    {
        $enabled = \App\Models\Setting::where('key', 'service_reminder_enabled')->value('value');
        if ($enabled !== '1') {
            return response()->json([]);
        }

        $interval = (int) (\App\Models\Setting::where('key', 'service_reminder_interval')->value('value') ?? 20);

        $orders = \App\Models\Order::where('status', 'served')
            ->with(['logs' => fn($q) => $q->whereIn('action', ['status_changed', 'serviced'])->latest('created_at')])
            ->get();

        $alerts = [];
        foreach ($orders as $order) {
            $lastEvent = $order->logs->sortByDesc('created_at')->first(function($log) {
                if ($log->action === 'serviced') return true;
                if ($log->action === 'status_changed' && ($log->new_value['status'] ?? '') === 'served') return true;
                return false;
            });

            if (!$lastEvent) continue;

            $minutesSince = $lastEvent->created_at->diffInMinutes(now());

            if ($minutesSince >= $interval) {
                $alerts[] = [
                    'order_id'      => $order->id,
                    'order_name'    => $order->name,
                    'minutes'       => (int) $minutesSince,
                    'url'           => route('orders.show', $order),
                    'last_event_ts' => $lastEvent->created_at->timestamp,
                ];
            }
        }

        return response()->json($alerts);
    }

    public function sheet(Order $order)
    {
        [$order, $tables, $mixes, $tobaccos, $masters] = $this->loadOrderForView($order);
        return view('orders.partials.view-order-inner', compact('order', 'tables', 'mixes', 'tobaccos', 'masters'));
    }

    public function cardPartial(Order $order)
    {
        $order->load(['creator', 'table', 'guest', 'items.itemable']);

        $order->items->each(function ($item) {
            if ($item->itemable instanceof Tobacco) {
                $item->itemable->load('brand');
            } elseif ($item->itemable instanceof Mix) {
                $item->itemable->load('ingredients.tobacco.brand');
            }
        });

        return view('orders.partials.order-card', compact('order'));
    }

    private function broadcastOrder(Order $order): void
    {
        try {
            broadcast(new \App\Events\OrderStatusChanged($order))->toOthers();
            broadcast(new \App\Events\OrderUpdated($order))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function safeBroadcast(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function loadOrderForView(Order $order): array
    {
        $order->load([
            'creator', 'table.type',
            'guest' => fn($q) => $q->withCount([
                'orders as visits_count' => fn($q) => $q->whereIn('status', ['closed', 'redo', 'cancelled']),
            ]),
            'items' => fn($q) => $q->withTrashed()->with(['itemable', 'addedBy']),
            'logs' => fn($q) => $q->with('user'),
            'tableHistory.table',
            'tableHistory.user',
            'statusHistories',
        ]);

        $order->items->each(function ($item) {
            if ($item->itemable instanceof \App\Models\Tobacco) $item->itemable->load('brand', 'flavorTags.category');
            if ($item->itemable instanceof \App\Models\Mix) $item->itemable->load('ingredients.tobacco.flavorTags.category');
        });

        $masters  = \App\Models\User::where('is_active', true)->get();
        $tables   = Table::where('is_active', true)
            ->with('type')
            ->get()
            ->sortBy([
                fn($a, $b) => ($a->type?->id ?? 0) <=> ($b->type?->id ?? 0),
                fn($a, $b) => $a->name <=> $b->name,
            ])
            ->values();
        $mixes    = Mix::where('is_active', true)->get();
        $tobaccos = Tobacco::whereHas('venueTobacco', fn($q) => $q->where('is_active', true))->with('brand')->orderBy('name')->get();

        return [$order, $tables, $mixes, $tobaccos, $masters];
    }

    public function updateGuestComment(Request $request, Order $order)
    {
        $request->validate([
            'guest_comment' => 'nullable|string|max:500',
        ]);

        $oldComment = $order->guest_comment;
        $newComment = $request->guest_comment ?: null;

        if ($oldComment === $newComment) {
            return back();
        }

        $order->update(['guest_comment' => $newComment]);

        OrderLog::create([
            'order_id'   => $order->id,
            'user_id'    => auth()->id(),
            'action'     => 'guest_comment_changed',
            'old_value'  => ['comment' => $oldComment],
            'new_value'  => ['comment' => $newComment],
            'created_at' => now(),
        ]);

        $this->broadcastOrder($order);

        return back()->with('success', 'Комментарий гостя обновлён');
    }
}