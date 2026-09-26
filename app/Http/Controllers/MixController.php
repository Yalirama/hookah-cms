<?php

namespace App\Http\Controllers;

use App\Models\MixLog;
use App\Models\Mix;
use App\Models\MixIngredient;
use App\Models\MixIngredientAlt;
use App\Models\MixNote;
use App\Models\MixRevision;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\Tobacco;
use Illuminate\Http\Request;

class MixController extends Controller
{
    public function index(Request $request)
    {
        $mixes = Mix::with([
            'creator',
            'ingredients' => fn($q) => $q->orderBy('sort_order')->with('tobacco.brand', 'tobacco.flavorTags.category'),
        ])
        ->latest()
        ->get();

        $total         = Mix::where('is_active', true)->count();
        $totalInactive = Mix::where('is_active', false)->count();

        return view('mixes.index', compact('mixes', 'total', 'totalInactive'));
    }

    public function activate(Request $request, Mix $mix)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $mix->update([
            'is_active'            => true,
            'deactivated_at'       => null,
            'deactivated_by'       => null,
            'deactivation_comment' => null,
        ]);

        MixLog::create([
            'mix_id'  => $mix->id,
            'user_id' => auth()->id(),
            'action'  => 'activated',
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'Микс активирован');
    }

    public function show(Mix $mix)
    {
        $mix->load([
            'creator', 'updater',
            'ingredients' => fn($q) => $q->withTrashed()->with([
                'tobacco.brand',
                'alternatives.tobacco.brand',
            ]),
            'notes.user',
            'revisions.user',
        ]);

        $orderItemsQuery = OrderItem::withTrashed()
            ->where('itemable_type', Mix::class)
            ->where('itemable_id', $mix->id);

        $totalOrders = (clone $orderItemsQuery)->count();
        $redoOrders  = (clone $orderItemsQuery)
            ->whereHas('order', fn($q) => $q->where('status', 'redo'))
            ->count();
        $last30 = (clone $orderItemsQuery)
            ->whereHas('order', fn($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->count();

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
                    $q->whereRaw("TIME(created_at) >= ? AND TIME(created_at) < ?", [
                        $slot['from'], $slot['to'],
                    ]);
                })->count();
        }

        $dayNames  = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        $dayCounts = [];
        for ($i = 0; $i <= 6; $i++) {
            $dayCounts[$dayNames[$i]] = (clone $orderItemsQuery)
                ->whereHas('order', fn($q) => $q->whereRaw("DAYOFWEEK(created_at) = ?", [$i + 1]))
                ->count();
        }

        return view('mixes.show', compact(
            'mix', 'totalOrders', 'redoOrders', 'last30',
            'timeSlots', 'slotCounts', 'dayCounts'
        ));
    }

    public function create()
    {
        $tobaccos = Tobacco::whereHas('venueTobacco', fn($q) => $q->where('is_active', true))
            ->with('brand', 'flavorTags')
            ->orderBy('name')
            ->get();

        return view('mixes.create', compact('tobaccos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'description_short' => 'nullable|string|max:255',
            'description_long'  => 'nullable|string',
            'strength'          => 'nullable|in:light,medium,strong',
            'note'              => 'nullable|string|max:1000',
            'ingredients'       => 'required|array|min:1',
            'ingredients.*.tobacco_id' => 'required|exists:tobaccos,id',
            'ingredients.*.grams' => 'nullable|numeric|min:0',
        ]);

        $mix = Mix::create([
            'name'              => $request->name,
            'description_short' => $request->description_short,
            'description_long'  => $request->description_long,
            'strength'          => $request->strength,
            'created_by'        => auth()->id(),
            'is_active'         => true,
        ]);

        foreach ($request->ingredients as $i => $ing) {
            $ingredient = MixIngredient::create([
                'mix_id'     => $mix->id,
                'tobacco_id' => $ing['tobacco_id'],
                'grams'      => $ing['grams'] ?? null,
                'unit'       => $ing['unit'] ?? 'g',
                'sort_order' => $i,
            ]);

            if (!empty($ing['alternatives'])) {
                foreach ($ing['alternatives'] as $alt) {
                    if (!empty($alt['tobacco_id'])) {
                        MixIngredientAlt::create([
                            'ingredient_id' => $ingredient->id,
                            'tobacco_id'    => $alt['tobacco_id'],
                            'grams'         => $alt['grams'] ?? null,
                            'unit'          => $alt['unit'] ?? 'g',
                        ]);
                    }
                }
            }
        }

        if ($request->note) {
            MixNote::create([
                'mix_id'  => $mix->id,
                'user_id' => auth()->id(),
                'note'    => $request->note,
            ]);
        }

        // Начальная ревизия
        MixRevision::create([
            'mix_id'         => $mix->id,
            'user_id'        => auth()->id(),
            'changed_fields' => ['action' => 'created'],
            'snapshot'       => $mix->toArray(),
        ]);

        if ($request->ajax()) {
            return response()->json(['message' => 'Микс создан', 'id' => $mix->id]);
        }

        return redirect()->route('mixes.show', $mix)->with('success', 'Микс создан');
    }

    public function edit(Mix $mix)
    {
        $mix->load([
            'ingredients' => fn($q) => $q->withTrashed()->with([
                'tobacco.brand',
                'alternatives.tobacco.brand',
            ]),
        ]);

        $tobaccos = Tobacco::whereHas('venueTobacco', fn($q) => $q->where('is_active', true))
            ->with('brand', 'flavorTags')
            ->orderBy('name')
            ->get();

        return view('mixes.edit', compact('mix', 'tobaccos'));
    }

    public function update(Request $request, Mix $mix)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'description_short' => 'nullable|string|max:255',
            'description_long'  => 'nullable|string',
            'strength'          => 'nullable|in:light,medium,strong',
            'ingredients'       => 'required|array|min:1',
            'ingredients.*.tobacco_id' => 'required|exists:tobaccos,id',
            'ingredients.*.grams' => 'nullable|numeric|min:0',
        ]);

        $fieldLabels = ['name' => 'Название', 'description_short' => 'Короткое описание', 'description_long' => 'Полное описание', 'strength' => 'Крепость'];
        $strengthLabels = ['light' => 'Лёгкий', 'medium' => 'Средний', 'strong' => 'Крепкий'];

        $changes = [];

        foreach ($fieldLabels as $field => $label) {
            $old = $mix->$field;
            $new = $request->$field;
            if ((string) $old !== (string) $new) {
                $oldDisplay = $field === 'strength' ? ($strengthLabels[$old] ?? '—') : ($old ?: '—');
                $newDisplay = $field === 'strength' ? ($strengthLabels[$new] ?? '—') : ($new ?: '—');
                $changes[] = ['type' => 'edit', 'label' => $label, 'old' => $oldDisplay, 'new' => $newDisplay];
            }
        }

        $oldIngredients = $mix->ingredients()->with('tobacco.brand')->get()
            ->mapWithKeys(fn($i) => [$i->tobacco_id => $i]);

        $newTobaccoIds = collect($request->ingredients)->pluck('tobacco_id')->map(fn($id) => (int) $id);
        $tobaccoNames = Tobacco::whereIn('id', $newTobaccoIds)->with('brand')->get()->keyBy('id');

        foreach ($oldIngredients as $tobaccoId => $ing) {
            if (!$newTobaccoIds->contains($tobaccoId)) {
                $name = trim(($ing->tobacco?->brand?->name ?? '') . ': ' . ($ing->tobacco?->name ?? '—'));
                $changes[] = ['type' => 'removed', 'label' => 'Состав', 'value' => $name];
            }
        }

        foreach ($newTobaccoIds as $tobaccoId) {
            if (!$oldIngredients->has($tobaccoId)) {
                $t = $tobaccoNames[$tobaccoId] ?? null;
                $name = trim(($t?->brand?->name ?? '') . ': ' . ($t?->name ?? '—'));
                $changes[] = ['type' => 'added', 'label' => 'Состав', 'value' => $name];
            }
        }

        $mix->update([
            'name'              => $request->name,
            'description_short' => $request->description_short,
            'description_long'  => $request->description_long,
            'strength'          => $request->strength,
            'updated_by'        => auth()->id(),
        ]);

        $mix->ingredients()->each(fn($i) => $i->alternatives()->delete());
        $mix->ingredients()->delete();

        foreach ($request->ingredients as $i => $ing) {
            $ingredient = MixIngredient::create([
                'mix_id'     => $mix->id,
                'tobacco_id' => $ing['tobacco_id'],
                'grams'      => $ing['grams'] ?? null,
                'unit'       => $ing['unit'] ?? 'g',
                'sort_order' => $i,
            ]);

            if (!empty($ing['alternatives'])) {
                foreach ($ing['alternatives'] as $alt) {
                    if (!empty($alt['tobacco_id'])) {
                        MixIngredientAlt::create([
                            'ingredient_id' => $ingredient->id,
                            'tobacco_id'    => $alt['tobacco_id'],
                            'grams'         => $alt['grams'] ?? null,
                            'unit'          => $alt['unit'] ?? 'g',
                        ]);
                    }
                }
            }
        }

        if (!empty($changes)) {
            MixRevision::create([
                'mix_id'         => $mix->id,
                'user_id'        => auth()->id(),
                'changed_fields' => $changes,
                'snapshot'       => $mix->fresh()->toArray(),
                'comment'        => $request->comment,
            ]);
        }

        if ($request->ajax()) {
            return response()->json(['message' => 'Микс обновлён']);
        }

        return redirect()->route('mixes.show', $mix)->with('success', 'Микс обновлён');
    }

    public function deactivate(Request $request, Mix $mix)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $mix->update([
            'is_active'             => false,
            'deactivated_at'        => now(),
            'deactivated_by'        => auth()->id(),
            'deactivation_comment'  => $request->comment,
        ]);

        MixLog::create([
            'mix_id'  => $mix->id,
            'user_id' => auth()->id(),
            'action'  => 'deactivated',
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'Микс деактивирован');
    }

    public function storeNote(Request $request, Mix $mix)
    {
        $request->validate(['note' => 'required|string|max:1000']);

        MixNote::create([
            'mix_id'  => $mix->id,
            'user_id' => auth()->id(),
            'note'    => $request->note,
        ]);

        return back()->with('success', 'Заметка добавлена');
    }

    public function destroyNote(Mix $mix, MixNote $note)
    {
        if ($note->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $note->delete();
        return back()->with('success', 'Заметка удалена');
    }

    public function deactivationLogSheet()
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $logs = MixLog::with(['user', 'mix'])
            ->orderByDesc('id')
            ->limit(31)
            ->get();

        $hasMore = $logs->count() > 30;
        $logs = $logs->take(30);

        return view('mixes.partials.deactivation-log-list', compact('logs', 'hasMore'));
    }

    public function deactivationLogMore(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $lastId = (int) $request->get('last_id');

        $logs = MixLog::with(['user', 'mix'])
            ->where('id', '<', $lastId)
            ->orderByDesc('id')
            ->limit(31)
            ->get();

        $hasMore = $logs->count() > 30;
        $logs = $logs->take(30);

        return view('mixes.partials.deactivation-log-rows', compact('logs', 'hasMore'));
    }

    public function historySheet(Mix $mix)
    {
        $revisions = $mix->revisions()->with('user')->get();

        return view('mixes.partials.history-list', compact('revisions'));
    }

    public function statsPartial()
    {
        $total         = Mix::where('is_active', true)->count();
        $totalInactive = Mix::where('is_active', false)->count();

        return view('mixes.partials.header-stats', compact('total', 'totalInactive'));
    }

    public function sheet(Mix $mix)
    {
        $mix->load([
            'creator', 'updater',
            'ingredients' => fn($q) => $q->orderBy('sort_order')->with(['tobacco.brand', 'tobacco.flavorTags.category', 'alternatives.tobacco.brand']),
            'notes.user',
        ]);

        $data = $this->buildMixStats($mix);

        return view('mixes.partials.mix-sheet', array_merge(['mix' => $mix], $data));
    }

    private function buildMixStats(Mix $mix): array
    {
        $orderItemsQuery = OrderItem::withTrashed()
            ->where('itemable_type', Mix::class)
            ->where('itemable_id', $mix->id);

        $totalOrders = (clone $orderItemsQuery)->count();
        $redoOrders  = (clone $orderItemsQuery)->whereHas('order', fn($q) => $q->where('status', 'redo'))->count();

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

    public function tobaccosListJson()
    {
        $tobaccos = Tobacco::whereHas('venueTobacco', fn($q) => $q->where('is_active', true))
            ->with('brand', 'flavorTags')
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'brand' => $t->brand?->name ?? '',
                'name' => $t->name,
                'search' => mb_strtolower(implode(' ', array_filter([
                    $t->brand?->name,
                    $t->name,
                    $t->flavorTags->pluck('name')->implode(' '),
                ])), 'UTF-8'),
            ]);

        return response()->json($tobaccos);
    }

    public function createForm()
    {
        $tobaccos = Tobacco::whereHas('venueTobacco', fn($q) => $q->where('is_active', true))->with('brand')->orderBy('name')->get();
        $mix = null;
        return view('mixes.partials.mix-form', compact('mix', 'tobaccos'));
    }

    public function editForm(Mix $mix)
    {
        $mix->load('ingredients.tobacco.brand', 'ingredients.alternatives.tobacco.brand');
        $tobaccos = Tobacco::whereHas('venueTobacco', fn($q) => $q->where('is_active', true))->with('brand')->orderBy('name')->get();
        return view('mixes.partials.mix-form', compact('mix', 'tobaccos'));
    }

    public function listPartial()
    {
        $mixes = Mix::with([
            'creator',
            'ingredients' => fn($q) => $q->orderBy('sort_order')->with('tobacco.brand', 'tobacco.flavorTags.category'),
        ])->latest()->get();

        return view('mixes.partials.list-items', compact('mixes'));
    }
}