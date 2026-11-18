<?php

namespace App\Http\Controllers;

use App\Models\Mix;
use App\Models\Setting;
use App\Models\Tobacco;
use App\Models\User;
use App\Models\VenueTobacco;
use App\Support\StatAccess;
use Illuminate\Http\Request;

class StatsSettingsController extends Controller
{
    public function pricesPanel()
    {
        $tobaccos = Tobacco::with('brand', 'venueTobacco')
            ->whereHas('venueTobacco', fn ($q) => $q->where('is_active', true))
            ->get()->sortBy(fn ($t) => mb_strtolower(($t->brand->name ?? '') . ' ' . $t->name))->values();
        $mixes = Mix::where('is_active', true)->orderBy('name')->get();
        $default = Setting::get('default_item_price');

        return view('settings.partials._prices-panel', compact('tobaccos', 'mixes', 'default'));
    }

    public function savePrices(Request $request)
    {
        $data = $request->validate([
            'default'    => 'nullable|numeric|min:0|max:10000000',
            'tobaccos'   => 'nullable|array',
            'tobaccos.*' => 'nullable|numeric|min:0|max:10000000',
            'mixes'      => 'nullable|array',
            'mixes.*'    => 'nullable|numeric|min:0|max:10000000',
        ]);

        Setting::set('default_item_price', ($data['default'] ?? '') === '' ? '' : $data['default']);

        foreach ((array) ($data['tobaccos'] ?? []) as $id => $price) {
            VenueTobacco::where('tobacco_id', (int) $id)->get()->each(fn ($v) => $v->update(['price' => $price === null || $price === '' ? null : $price]));
        }
        foreach ((array) ($data['mixes'] ?? []) as $id => $price) {
            Mix::whereKey((int) $id)->get()->each(fn ($m) => $m->update(['price' => $price === null || $price === '' ? null : $price]));
        }

        return response()->json(['ok' => true]);
    }

    public function accessPanel()
    {
        $masters = User::role('master')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'surname']);
        $rules = collect(StatAccess::STATS)->map(fn ($v, $k) => StatAccess::rule($k));

        return view('settings.partials._stat-access-panel', compact('masters', 'rules'));
    }

    public function saveAccess(Request $request)
    {
        $request->validate(['rules' => 'required|array']);
        StatAccess::save($request->input('rules'));

        return response()->json(['ok' => true]);
    }
}
