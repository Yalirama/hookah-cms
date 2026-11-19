<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
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

        $adminLogs = AdminLog::with('user')->orderByDesc('id')->limit(30)->get();

        return view('settings.index', compact(
            'settings', 'masters', 'tableTypes', 'tables', 'workHours', 'timeSlots', 'journalLogs', 'wikiPages', 'adminLogs'
        ));
    }

    public function updateGeneral(Request $request)
    {
        $request->validate([
            'venue_name' => 'nullable|string|max:255',
            'work_hours_from' => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'], 'work_hours_to' => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
            'analytics_access' => 'nullable|in:all,admin',
        ], [], ['venue_name' => 'название заведения', 'work_hours_from' => 'начало работы', 'work_hours_to' => 'конец работы', 'analytics_access' => 'доступ к аналитике']);

        if ($request->has('venue_name')) Setting::set('venue_name', $request->venue_name);
        if ($request->filled('work_hours_from') && $request->filled('work_hours_to')) {
            Setting::set('work_hours', json_encode(['from' => $request->work_hours_from, 'to' => $request->work_hours_to]));
        }

        $slots = [];
        foreach (['morning', 'day', 'evening', 'night'] as $key) {
            if ($request->filled("slots_{$key}_from") && $request->filled("slots_{$key}_to")) {
                $slots[$key] = [
                    'label' => $request->input("slots_{$key}_label"),
                    'from'  => $request->input("slots_{$key}_from"),
                    'to'    => $request->input("slots_{$key}_to"),
                ];
            }
        }
        if ($slots || $request->hasAny(['slots_morning_from', 'slots_day_from', 'slots_evening_from', 'slots_night_from'])) Setting::set('time_slots', json_encode($slots));
        if ($request->filled('analytics_access')) Setting::set('analytics_access', $request->input('analytics_access'));

        return response()->json(['ok' => true]);
    }

    public function updateNotifications(Request $request)
    {
        $request->validate([
            'service_reminder_interval' => 'nullable|integer|min:1|max:600',
            'order_wait_warn' => 'nullable|integer|min:1|max:120',
            'order_wait_alert' => 'nullable|integer|min:1|max:240|gt:order_wait_warn',
        ], [], ['service_reminder_interval' => 'интервал напоминания', 'order_wait_warn' => 'оранжевый порог', 'order_wait_alert' => 'красный порог']);
        Setting::set('notifications_enabled', $request->boolean('notifications_enabled') ? '1' : '0');
        Setting::set('service_reminder_enabled', $request->boolean('service_reminder_enabled') ? '1' : '0');
        if ($request->filled('service_reminder_interval')) Setting::set('service_reminder_interval', $request->input('service_reminder_interval'));
        if ($request->filled('order_wait_warn')) Setting::set('order_wait_warn', $request->input('order_wait_warn'));
        if ($request->filled('order_wait_alert')) Setting::set('order_wait_alert', $request->input('order_wait_alert'));

        return response()->json(['ok' => true]);
    }

    public function updateThresholds(Request $request)
    {
        $request->validate([
            'guest_new_threshold' => 'nullable|integer|min:0|max:100000', 'guest_regular_threshold' => 'nullable|integer|min:0|max:100000',
            'guest_permanent_threshold' => 'nullable|integer|min:0|max:100000', 'guest_vip_threshold' => 'nullable|integer|min:0|max:100000',
        ], [], ['guest_new_threshold' => 'порог «новый»', 'guest_regular_threshold' => 'порог «постоянный»', 'guest_permanent_threshold' => 'порог «частый»', 'guest_vip_threshold' => 'порог VIP']);

        $reg = (int) $request->input('guest_regular_threshold', Setting::get('guest_regular_threshold', 10));
        $per = (int) $request->input('guest_permanent_threshold', Setting::get('guest_permanent_threshold', 50));
        $vip = (int) $request->input('guest_vip_threshold', Setting::get('guest_vip_threshold', 400));
        if (!($reg <= $per && $per <= $vip)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['guest_regular_threshold' => 'Пороги должны идти по возрастанию: регулярный ≤ постоянный ≤ VIP.']);
        }
        Setting::set('guest_new_threshold',       $request->input('guest_new_threshold', 0));
        Setting::set('guest_regular_threshold',   $reg);
        Setting::set('guest_permanent_threshold', $per);
        Setting::set('guest_vip_threshold',       $vip);

        return response()->json(['ok' => true]);
    }

    public function updateInactive(Request $request)
    {
        $request->validate(['guest_inactive_days' => 'nullable|integer|min:1|max:3650'], [], ['guest_inactive_days' => 'дни неактивности']);
        Setting::set('guest_inactive_days', $request->input('guest_inactive_days', 30));
        return response()->json(['ok' => true]);
    }

    private function cleanName(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    private function assertNameFree(string $name, ?User $ignore): void
    {
        $full = mb_strtolower(trim($name . ' ' . ($ignore?->surname ?? '')));
        $taken = User::when($ignore, fn ($q) => $q->where('id', '!=', $ignore->id))
            ->whereRaw("LOWER(TRIM(CONCAT(name, ' ', COALESCE(surname, '')))) = ?", [$full])
            ->exists();
        if ($taken) {
            throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Имя «' . trim($name . ' ' . ($ignore?->surname ?? '')) . '» уже занято. Добавьте ещё буквы, например «Куплинов А» → «Куплинов Ал».']);
        }
    }

    public function storeMaster(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'login'    => 'required|string|max:255',
            'password' => 'required|string',
            'role'     => 'nullable|in:admin,master',
        ]);
        $name = $this->cleanName($request->name);
        $this->assertNameFree($name, null);
        [$login, $loginError] = \App\Support\AccountRules::login($request->login);
        if ($loginError) throw \Illuminate\Validation\ValidationException::withMessages(['login' => $loginError]);
        if ($passwordError = \App\Support\AccountRules::password($request->password, $login)) throw \Illuminate\Validation\ValidationException::withMessages(['password' => $passwordError]);
        if (User::where('login', $login)->exists()) throw \Illuminate\Validation\ValidationException::withMessages(['login' => 'Такой логин уже используется.']);

        $role = $request->input('role', 'master');

        $user = User::create([
            'name'      => $name,
            'login'     => $login,
            'password'  => Hash::make($request->password),
            'is_active' => true,
        ]);
        $user->assignRole($role);
        AdminLog::record('created', 'Пользователь', $user->adminLabel(), 'Создал учётку (' . ($role === 'admin' ? 'администратор' : 'мастер') . ') «' . $user->adminLabel() . '»');

        return response()->json(['row' => view('settings.partials._master-row', ['master' => $user])->render()]);
    }

    public function updateMaster(Request $request, User $user)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'login' => 'required|string|max:255',
        ]);
        $name = $this->cleanName($request->name);
        $this->assertNameFree($name, $user);
        $login = trim($request->login);
        if ($login !== $user->login) {
            [$login, $loginError] = \App\Support\AccountRules::login($login);
            if ($loginError) throw \Illuminate\Validation\ValidationException::withMessages(['login' => $loginError]);
            if (User::where('login', $login)->where('id', '!=', $user->id)->exists()) throw \Illuminate\Validation\ValidationException::withMessages(['login' => 'Такой логин уже используется.']);
        }

        $data = ['name' => $name, 'login' => $login];
        if ($request->filled('password')) {
            if ($passwordError = \App\Support\AccountRules::password($request->password, $login)) throw \Illuminate\Validation\ValidationException::withMessages(['password' => $passwordError]);
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);

        return response()->json(['row' => view('settings.partials._master-row', ['master' => $user])->render()]);
    }

    public function toggleMaster(User $user)
    {
        abort_if($user->id === auth()->id(), 422, 'Нельзя деактивировать самого себя');
        $user->update(['is_active' => !$user->is_active]);
        return response()->json(['row' => view('settings.partials._master-row', ['master' => $user])->render()]);
    }

    public function storeTableType(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('table_types', 'name')->whereNull('deleted_at')]]);
        $type = TableType::create($request->only('name', 'description'));

        return response()->json(['row' => view('settings.partials._table-type-row', ['type' => $type])->render()]);
    }

    public function updateTableType(Request $request, TableType $tableType)
    {
        $request->validate(['name' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('table_types', 'name')->whereNull('deleted_at')->ignore($tableType->id)]]);
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
            'name'    => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('tables', 'name')->whereNull('deleted_at')],
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
            'name'    => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('tables', 'name')->whereNull('deleted_at')->ignore($table->id)],
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

    public function adminLogSheet(Request $request)
    {
        $query = AdminLog::with('user')->orderByDesc('id');
        if ($before = $request->query('before')) {
            $query->where('id', '<', $before);
        }
        $logs = $query->limit(30)->get();

        return view('settings.partials._admin-log-rows', compact('logs'));
    }

    public function tablesPanel()
    {
        $tableTypes = TableType::withTrashed()->orderBy('name')->get();
        $tables     = Table::with('type')->orderBy('name')->get();

        return response()->json([
            'accordion' => view('settings.partials._tables-accordion', compact('tableTypes', 'tables'))->render(),
            'options'   => view('settings.partials._type-options', compact('tableTypes'))->render(),
        ]);
    }
}