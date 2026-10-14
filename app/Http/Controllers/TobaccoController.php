<?php

namespace App\Http\Controllers;

use App\Models\FlavorTag;
use App\Models\Mix;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tobacco;
use App\Models\TobaccoBrand;
use App\Models\TobaccoNote;
use App\Models\VenueTobacco;
use App\Models\VenueTobaccoLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class TobaccoController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'tobaccos');

        $tobaccos = $this->venueTobaccosList();

        $stats = [
            'total'    => Tobacco::whereHas('venueTobacco', fn($q) => $q->where('is_active', true))->count(),
            'brands'   => TobaccoBrand::whereHas('tobaccos.venueTobacco', fn($q) => $q->where('is_active', true))->count(),
            'inactive' => Tobacco::whereHas('venueTobacco', fn($q) => $q->where('is_active', false))->count(),
        ];

        $brands = TobaccoBrand::whereHas('tobaccos.venueTobacco')
            ->withCount([
                'tobaccos as active_count' => fn($q) => $q->whereHas('venueTobacco', fn($q) => $q->where('is_active', true)),
                'tobaccos as inactive_count' => fn($q) => $q->whereHas('venueTobacco', fn($q) => $q->where('is_active', false)),
            ])
            ->orderBy('name')->get();

        return view('tobaccos.index', compact('tobaccos', 'stats', 'brands', 'tab'));
    }

    public function show(Tobacco $tobacco)
    {
        $tobacco->load('brand', 'flavorTags.category', 'notes.user', 'venueTobacco.logs.user');

        $data = $this->buildTobaccoStats($tobacco);

        return view('tobaccos.show', array_merge(['tobacco' => $tobacco], $data));
    }

    public function sheet(Tobacco $tobacco)
    {
        $tobacco->load('brand', 'flavorTags.category', 'notes.user', 'venueTobacco.logs.user');

        $data = $this->buildTobaccoStats($tobacco);

        return view('tobaccos.partials.tobacco-sheet', array_merge(['tobacco' => $tobacco], $data));
    }

    private function buildTobaccoStats(Tobacco $tobacco): array
    {
        $orderItemsQuery = OrderItem::withTrashed()
            ->where('itemable_type', Tobacco::class)
            ->where('itemable_id', $tobacco->id);

        $totalOrders = (clone $orderItemsQuery)->count();

        $redoOrders = (clone $orderItemsQuery)
            ->whereHas('order', fn($q) => $q->where('status', 'redo'))
            ->count();

        $last7   = (clone $orderItemsQuery)->whereHas('order', fn($q) => $q->where('created_at', '>=', now()->subDays(7)))->count();
        $last30  = (clone $orderItemsQuery)->whereHas('order', fn($q) => $q->where('created_at', '>=', now()->subDays(30)))->count();
        $last60  = (clone $orderItemsQuery)->whereHas('order', fn($q) => $q->where('created_at', '>=', now()->subDays(60)))->count();
        $last90  = (clone $orderItemsQuery)->whereHas('order', fn($q) => $q->where('created_at', '>=', now()->subDays(90)))->count();
        $last365 = (clone $orderItemsQuery)->whereHas('order', fn($q) => $q->where('created_at', '>=', now()->subDays(365)))->count();

        $timeSlots = Setting::get('time_slots', [
            'morning' => ['label' => 'Утро',  'from' => '06:00', 'to' => '12:00'],
            'day'     => ['label' => 'Обед',  'from' => '12:00', 'to' => '17:00'],
            'evening' => ['label' => 'Вечер', 'from' => '17:00', 'to' => '23:00'],
            'night'   => ['label' => 'Ночь',  'from' => '23:00', 'to' => '06:00'],
        ]);

        $slotCounts = [];
        foreach ($timeSlots as $key => $slot) {
            $slotCounts[$key] = (clone $orderItemsQuery)
                ->whereHas('order', function ($q) use ($slot) {
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
            $dayCounts[$dayNames[$i]] = (clone $orderItemsQuery)
                ->whereHas('order', fn($q) => $q->whereRaw("DAYOFWEEK(created_at) = ?", [$i + 1]))
                ->count();
        }

        return compact('totalOrders', 'redoOrders', 'last7', 'last30', 'last60', 'last90', 'last365', 'timeSlots', 'slotCounts', 'dayCounts');
    }

    public function edit()
    {
        $brands = TobaccoBrand::with(['tobaccos' => function ($q) {
            $q->with('venueTobacco')->orderBy('name');
        }])->orderBy('name')->get();

        return view('tobaccos.edit', compact('brands'));
    }

    public function updateVenue(Request $request)
    {
        $selected = $request->input('tobacco_ids', []);

        // Деактивируем все
        VenueTobacco::query()->update(['is_active' => false]);

        // Активируем выбранные
        foreach ($selected as $tobaccoId) {
            VenueTobacco::updateOrCreate(
                ['tobacco_id' => $tobaccoId],
                ['is_active' => true]
            );
        }

        return redirect()->route('tobaccos.index')->with('success', 'Список табаков обновлён');
    }

    public function storeNote(Request $request, Tobacco $tobacco)
    {
        $request->validate(['note' => 'required|string|max:1000']);

        TobaccoNote::create([
            'tobacco_id' => $tobacco->id,
            'user_id'    => auth()->id(),
            'note'       => $request->note,
        ]);

        return back()->with('success', 'Заметка добавлена');
    }

    public function destroyNote(Tobacco $tobacco, TobaccoNote $note)
    {
        if ($note->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $note->delete();
        return back()->with('success', 'Заметка удалена');
    }

    public function deactivate(Request $request, Tobacco $tobacco)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $vt = $tobacco->venueTobacco;
        if (!$vt || !$vt->is_active) return back()->with('error', 'Табак уже деактивирован');

        $vt->update([
            'is_active'             => false,
            'deactivated_at'        => now(),
            'deactivated_by'        => auth()->id(),
            'deactivation_comment'  => $request->comment,
        ]);

        VenueTobaccoLog::create([
            'venue_tobacco_id' => $vt->id,
            'user_id'          => auth()->id(),
            'action'           => 'deactivated',
            'comment'          => $request->comment,
        ]);

        return back()->with('success', 'Табак деактивирован');
    }

    public function activate(Request $request, Tobacco $tobacco)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $vt = $tobacco->venueTobacco;
        if (!$vt) return back()->with('error', 'Табак не в списке заведения');

        $vt->update([
            'is_active'            => true,
            'deactivated_at'       => null,
            'deactivated_by'       => null,
            'deactivation_comment' => null,
        ]);

        VenueTobaccoLog::create([
            'venue_tobacco_id' => $vt->id,
            'user_id'          => auth()->id(),
            'action'           => 'activated',
            'comment'          => $request->comment,
        ]);

        return back()->with('success', 'Табак активирован');
    }

    public function deactivationLogSheet()
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $logs = \App\Models\VenueTobaccoLog::with(['user', 'venueTobacco.tobacco.brand'])
            ->orderByDesc('id')
            ->limit(31)
            ->get();

        $hasMore = $logs->count() > 30;
        $logs = $logs->take(30);

        return view('tobaccos.partials.deactivation-log-list', compact('logs', 'hasMore'));
    }

    public function deactivationLogMore(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $lastId = (int) $request->get('last_id');

        $logs = \App\Models\VenueTobaccoLog::with(['user', 'venueTobacco.tobacco.brand'])
            ->where('id', '<', $lastId)
            ->orderByDesc('id')
            ->limit(31)
            ->get();

        $hasMore = $logs->count() > 30;
        $logs = $logs->take(30);

        return view('tobaccos.partials.deactivation-log-rows', compact('logs', 'hasMore'));
    }

    public function showBrand(\App\Models\TobaccoBrand $brand)
    {
        $brand->load('tobaccos.flavorTags', 'tobaccos.venueTobacco');

        $tobaccoIds = $brand->tobaccos->pluck('id');

        $totalInVenue  = $brand->tobaccos->filter(fn($t) => $t->venueTobacco?->is_active)->count();
        $totalInactive = $brand->tobaccos->filter(fn($t) => $t->venueTobacco && !$t->venueTobacco->is_active)->count();

        $orderItemsQuery = \App\Models\OrderItem::withTrashed()
            ->where('itemable_type', \App\Models\Tobacco::class)
            ->whereIn('itemable_id', $tobaccoIds);

        $totalOrders = (clone $orderItemsQuery)->count();
        $last30      = (clone $orderItemsQuery)
            ->whereHas('order', fn($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->count();
        $redoOrders  = (clone $orderItemsQuery)
            ->whereHas('order', fn($q) => $q->where('status', 'redo'))
            ->count();

        // Топ табаков бренда
        $topTobaccos = \App\Models\OrderItem::withTrashed()
            ->where('itemable_type', \App\Models\Tobacco::class)
            ->whereIn('itemable_id', $tobaccoIds)
            ->selectRaw('itemable_id, itemable_type, count(*) as total')
            ->groupBy('itemable_id', 'itemable_type')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Загружаем табаки отдельно
        $tobaccoMap = \App\Models\Tobacco::withTrashed()
            ->whereIn('id', $topTobaccos->pluck('itemable_id'))
            ->with('flavorTags')
            ->get()
            ->keyBy('id');

        $topTobaccos = $topTobaccos->map(function($item) use ($tobaccoMap) {
            $item->tobacco = $tobaccoMap[$item->itemable_id] ?? null;
            return $item;
        });

        $timeSlots = \App\Models\Setting::get('time_slots', [
            'morning' => ['label' => 'Утро',  'from' => '06:00', 'to' => '12:00'],
            'day'     => ['label' => 'Обед',  'from' => '12:00', 'to' => '17:00'],
            'evening' => ['label' => 'Вечер', 'from' => '17:00', 'to' => '23:00'],
            'night'   => ['label' => 'Ночь',  'from' => '23:00', 'to' => '06:00'],
        ]);

        $slotCounts = [];
        foreach ($timeSlots as $key => $slot) {
            $slotCounts[$key] = (clone $orderItemsQuery)
                ->whereHas('order', function ($q) use ($slot) {
                    if ($slot['from'] < $slot['to']) {
                        $q->whereRaw("TIME(created_at) >= ? AND TIME(created_at) < ?", [$slot['from'], $slot['to']]);
                    } else {
                        $q->whereRaw("(TIME(created_at) >= ? OR TIME(created_at) < ?)", [$slot['from'], $slot['to']]);
                    }
                })->count();
        }

        $dayNames  = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        $dayCounts = [];
        for ($i = 0; $i <= 6; $i++) {
            $dayCounts[$dayNames[$i]] = (clone $orderItemsQuery)
                ->whereHas('order', fn($q) => $q->whereRaw("DAYOFWEEK(created_at) = ?", [$i + 1]))
                ->count();
        }

        return view('tobaccos.brand', compact(
            'brand', 'totalInVenue', 'totalInactive',
            'totalOrders', 'last30', 'redoOrders',
            'topTobaccos', 'timeSlots', 'slotCounts', 'dayCounts'
        ));
    }

    public function editSheet()
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $brands = TobaccoBrand::withCount([
            'tobaccos',
            'tobaccos as active_count' => fn($q) => $q->whereHas('venueTobacco', fn($v) => $v->where('is_active', true)),
        ])->orderBy('name')->get();

        return view('tobaccos.partials.edit-list', compact('brands'));
    }

    public function editBrandRows(TobaccoBrand $brand)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $tobaccos = $brand->tobaccos()->with('venueTobacco')->orderBy('name')->get();

        return view('tobaccos.partials.edit-rows', compact('tobaccos'));
    }

    public function editSearch(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $q = trim((string) $request->get('q'));
        $limit = 80;

        $found = Tobacco::with(['brand', 'venueTobacco'])
            ->where(fn($w) => $w->where('name', 'like', "%$q%")
                ->orWhere('alt_name', 'like', "%$q%")
                ->orWhereHas('brand', fn($b) => $b->where('name', 'like', "%$q%")))
            ->orderBy('name')
            ->limit($limit + 1)
            ->get();

        $truncated = $found->count() > $limit;
        $groups = $found->take($limit)->groupBy('brand_id')->sortBy(fn($g) => $g->first()->brand->name);

        return view('tobaccos.partials.edit-search-results', compact('groups', 'truncated', 'limit'));
    }

    public function updateVenueBulk(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $request->validate([
            'changes' => 'required|array',
            'changes.*.tobacco_id' => 'required|exists:tobaccos,id',
            'changes.*.active' => 'required|boolean',
            'changes.*.comment' => 'nullable|string|max:500',
        ]);

        foreach ($request->changes as $change) {
            $tobacco = Tobacco::find($change['tobacco_id']);
            $vt = $tobacco->venueTobacco;
            $wantActive = (bool) $change['active'];
            $comment = $change['comment'] ?? null;

            if ($wantActive) {
                if ($vt && $vt->is_active) continue; // уже активен, нечего менять

                $vt = VenueTobacco::updateOrCreate(
                    ['tobacco_id' => $tobacco->id],
                    ['is_active' => true, 'deactivated_at' => null, 'deactivated_by' => null, 'deactivation_comment' => null]
                );

                VenueTobaccoLog::create([
                    'venue_tobacco_id' => $vt->id,
                    'user_id'          => auth()->id(),
                    'action'           => 'activated',
                    'comment'          => $comment,
                ]);
            } else {
                if (!$vt || !$vt->is_active) continue; // уже неактивен, нечего менять

                $vt->update([
                    'is_active'            => false,
                    'deactivated_at'       => now(),
                    'deactivated_by'       => auth()->id(),
                    'deactivation_comment' => $comment,
                ]);

                VenueTobaccoLog::create([
                    'venue_tobacco_id' => $vt->id,
                    'user_id'          => auth()->id(),
                    'action'           => 'deactivated',
                    'comment'          => $comment,
                ]);
            }
        }

        return response()->json(['message' => 'Список обновлён']);
    }

    private function venueTobaccosList()
    {
        return Tobacco::whereHas('venueTobacco')
            ->with('brand', 'flavorTags.category', 'venueTobacco.deactivatedBy')
            ->orderBy('name')
            ->get();
    }

    public function listPartial()
    {
        return view('tobaccos.partials.list-items', ['tobaccos' => $this->venueTobaccosList()]);
    }

    public function statsPartial()
    {
        $stats = [
            'total'    => Tobacco::whereHas('venueTobacco', fn($q) => $q->where('is_active', true))->count(),
            'brands'   => TobaccoBrand::whereHas('tobaccos.venueTobacco', fn($q) => $q->where('is_active', true))->count(),
            'inactive' => Tobacco::whereHas('venueTobacco', fn($q) => $q->where('is_active', false))->count(),
        ];

        return view('tobaccos.partials.header-stats', compact('stats'));
    }
}