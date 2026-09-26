<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\LabelLog;
use App\Models\LabelNote;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function index()
    {
        $labels = Label::withCount(['activeGuestLabels as usage_count'])->latest()->get();

        $stats = ['total' => $labels->where('is_active', true)->count()];

        return view('labels.index', compact('labels', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $label = Label::create([
            'name'        => $request->name,
            'description' => $request->description,
            'created_by'  => auth()->id(),
            'is_active'   => true,
        ]);

        LabelLog::create([
            'label_id'   => $label->id,
            'user_id'    => auth()->id(),
            'action'     => 'created',
            'new_value'  => $label->only(['name', 'description']),
            'created_at' => now(),
        ]);

        if ($note = $request->note) {
            LabelNote::create(['label_id' => $label->id, 'user_id' => auth()->id(), 'note' => $note]);
            LabelLog::create([
                'label_id' => $label->id, 'user_id' => auth()->id(),
                'action' => 'note_added', 'new_value' => ['note' => $note], 'created_at' => now(),
            ]);
        }

        return response()->json([
            'row' => view('labels.partials._row', ['label' => $label->loadCount(['activeGuestLabels as usage_count'])])->render(),
            'total' => Label::where('is_active', true)->count(),
        ]);
    }

    public function sheet(Label $label)
    {
        $label->load(['creator', 'notes' => fn($q) => $q->whereNull('deleted_at')->latest(), 'notes.user', 'logs.user']);

        $typeCounts = [
            'positive' => $label->activeGuestLabels()->where('type', 'positive')->count(),
            'neutral'  => $label->activeGuestLabels()->where('type', 'neutral')->count(),
            'negative' => $label->activeGuestLabels()->where('type', 'negative')->count(),
        ];

        $guestsByType = [];
        foreach (['positive', 'neutral', 'negative'] as $type) {
            $guestsByType[$type] = $label->activeGuestLabels()
                ->where('type', $type)
                ->with(['guest' => fn($q) => $q->withCount([
                    'orders as visits_count' => fn($q) => $q->whereIn('status', ['closed', 'redo', 'cancelled']),
                ])])
                ->get();
        }

        $html = view('labels.partials._view-sheet', [
            'label' => $label,
            'usageCount' => array_sum($typeCounts),
            'typeCounts' => $typeCounts,
            'guestsByType' => $guestsByType,
        ])->render();

        return response()->json([
            'id' => $label->id,
            'name' => $label->name,
            'description' => $label->description,
            'is_active' => $label->is_active,
            'html' => $html,
        ]);
    }

    public function update(Request $request, Label $label)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $old = $label->only(['name', 'description']);
        $label->update($request->only(['name', 'description']));

        LabelLog::create([
            'label_id' => $label->id, 'user_id' => auth()->id(), 'action' => 'updated',
            'old_value' => $old, 'new_value' => $label->only(['name', 'description']), 'created_at' => now(),
        ]);

        return response()->json(['name' => $label->name, 'description' => $label->description]);
    }

    public function toggle(Label $label)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $wasActive = $label->is_active;
        $label->update(['is_active' => !$wasActive]);

        LabelLog::create([
            'label_id' => $label->id, 'user_id' => auth()->id(),
            'action' => $wasActive ? 'deactivated' : 'activated', 'created_at' => now(),
        ]);

        return response()->json([
            'is_active' => $label->is_active,
            'total' => Label::where('is_active', true)->count(),
        ]);
    }

    public function storeNote(Request $request, Label $label)
    {
        $request->validate(['note' => 'required|string|max:1000']);

        LabelNote::create(['label_id' => $label->id, 'user_id' => auth()->id(), 'note' => $request->note]);
        LabelLog::create([
            'label_id' => $label->id, 'user_id' => auth()->id(),
            'action' => 'note_added', 'new_value' => ['note' => $request->note], 'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroyNote(Label $label, LabelNote $note)
    {
        if ($note->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) abort(403);

        LabelLog::create([
            'label_id' => $label->id, 'user_id' => auth()->id(),
            'action' => 'note_deleted', 'old_value' => ['note' => $note->note], 'created_at' => now(),
        ]);
        $note->delete();

        return response()->json(['ok' => true]);
    }

    public function historySheet(Label $label)
    {
        $label->load('logs.user');

        return view('labels.partials._history-sheet', compact('label'));
    }
}