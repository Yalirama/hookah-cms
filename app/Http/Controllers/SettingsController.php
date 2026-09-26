<?php

namespace App\Http\Controllers;

use App\Models\OrderLog;
use App\Models\Setting;
use App\Models\Table;
use App\Models\TableType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        $settings   = Setting::all()->keyBy('key');
        $masters = User::orderByDesc('is_active')->orderByDesc('created_at')->get();
        $tableTypes = TableType::withTrashed()->withCount('tables')->orderBy('name')->get();
        $tables     = Table::with('type')->orderBy('name')->get();
        $wikiPages = \App\Models\WikiPage::orderBy('sort_order')->orderBy('created_at')->get();
        $workHours = json_decode($settings->get('work_hours')?->value ?? '', true) ?: ['from' => '14:00', 'to' => '02:00'];
        $storedSlots = json_decode($settings->get('time_slots')?->value ?? '', true);

        $defaultSlots = [
            'morning' => ['label' => 'Утро',  'from' => '06:00', 'to' => '12:00'],
            'day'     => ['label' => 'День',  'from' => '12:00', 'to' => '17:00'],
            'evening' => ['label' => 'Вечер', 'from' => '17:00', 'to' => '23:00'],
            'night'   => ['label' => 'Ночь',  'from' => '23:00', 'to' => '06:00'],
        ];

        // Если настройка ещё ни разу не сохранялась — показываем дефолт целиком.
        // Если сохранялась (даже пустым массивом после удаления всех слотов) — уважаем её как есть.
        $timeSlots = is_array($storedSlots) ? $storedSlots : $defaultSlots;

        $journalLogs = OrderLog::where('action', 'master_changed')
            ->with(['user', 'order'])
            ->latest('created_at')
            ->limit(20)
            ->get();

        return view('settings.index', compact(
            'settings', 'masters', 'tableTypes', 'tables', 'workHours', 'timeSlots', 'journalLogs', 'wikiPages'
        ));
    }

    public function updateGeneral(Request $request)
    {
        Setting::set('venue_name', $request->venue_name);
        Setting::set('work_hours', json_encode([
            'from' => $request->work_hours_from,
            'to'   => $request->work_hours_to,
        ]));

        $slots = [];
        foreach (['morning', 'day', 'evening', 'night'] as $key) {
            if ($request->has("slots_{$key}_from")) {
                $slots[$key] = [
                    'label' => $request->input("slots_{$key}_label"),
                    'from'  => $request->input("slots_{$key}_from"),
                    'to'    => $request->input("slots_{$key}_to"),
                ];
            }
        }
        Setting::set('time_slots', json_encode($slots));
        Setting::set('analytics_access', $request->input('analytics_access', 'admin'));

        return response()->json(['ok' => true]);
    }

    public function updateNotifications(Request $request)
    {
        Setting::set('notifications_enabled', $request->boolean('notifications_enabled') ? '1' : '0');
        Setting::set('service_reminder_enabled', $request->boolean('service_reminder_enabled') ? '1' : '0');
        Setting::set('service_reminder_interval', $request->input('service_reminder_interval', 20));
        Setting::set('order_status_notifications', $request->boolean('order_status_notifications') ? '1' : '0');

        return response()->json(['ok' => true]);
    }

    public function updateThresholds(Request $request)
    {
        Setting::set('guest_new_threshold',       $request->input('guest_new_threshold', 0));
        Setting::set('guest_regular_threshold',   $request->input('guest_regular_threshold', 100));
        Setting::set('guest_permanent_threshold', $request->input('guest_permanent_threshold', 50));
        Setting::set('guest_vip_threshold',       $request->input('guest_vip_threshold', 400));

        return response()->json(['ok' => true]);
    }

    public function updateInactive(Request $request)
    {
        Setting::set('guest_inactive_days', $request->input('guest_inactive_days', 30));
        return response()->json(['ok' => true]);
    }

    public function storeMaster(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'login'    => 'required|string|unique:users,login|max:255',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'login'     => $request->login,
            'password'  => Hash::make($request->password),
            'is_active' => true,
        ]);
        $user->assignRole('master');

        return response()->json(['row' => view('settings.partials._master-row', ['master' => $user])->render()]);
    }

    public function updateMaster(Request $request, User $user)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'login' => 'required|string|unique:users,login,' . $user->id . '|max:255',
        ]);

        $data = ['name' => $request->name, 'login' => $request->login];
        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6']);
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);

        return response()->json(['row' => view('settings.partials._master-row', ['master' => $user])->render()]);
    }

    public function toggleMaster(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        return response()->json(['row' => view('settings.partials._master-row', ['master' => $user])->render()]);
    }

    public function storeTableType(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $type = TableType::create($request->only('name', 'description'));

        return response()->json(['row' => view('settings.partials._table-type-row', ['type' => $type])->render()]);
    }

    public function updateTableType(Request $request, TableType $tableType)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $tableType->update($request->only('name', 'description'));

        return response()->json(['row' => view('settings.partials._table-type-row', ['type' => $tableType])->render()]);
    }

    public function toggleTableType(TableType $tableType)
    {
        $tableType->trashed() ? $tableType->restore() : $tableType->delete();
        $tableType->refresh();

        return response()->json(['row' => view('settings.partials._table-type-row', ['type' => $tableType])->render()]);
    }

    public function storeTable(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'type_id' => 'nullable|exists:table_types,id',
        ]);

        $table = Table::create([
            'name'      => $request->name,
            'type_id'   => $request->type_id,
            'is_active' => true,
        ]);

        return response()->json(['row' => view('settings.partials._table-row', ['table' => $table->load('type')])->render()]);
    }

    public function updateTable(Request $request, Table $table)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'type_id' => 'nullable|exists:table_types,id',
        ]);

        $table->update($request->only('name', 'type_id'));

        return response()->json(['row' => view('settings.partials._table-row', ['table' => $table->load('type')])->render()]);
    }

    public function toggleTable(Table $table)
    {
        $table->update(['is_active' => !$table->is_active]);

        return response()->json(['row' => view('settings.partials._table-row', ['table' => $table->load('type')])->render()]);
    }

    public function masterChangesSheet(Request $request)
    {
        $before = $request->query('before');

        $query = OrderLog::where('action', 'master_changed')->with(['user', 'order'])->latest('created_at');
        if ($before) {
            $query->where('created_at', '<', $before);
        }
        $logs = $query->limit(20)->get();

        return view('settings.partials._journal-rows', compact('logs'));
    }
}