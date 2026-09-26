@extends('layouts.app')

@section('content')

@php
$statusColors = [
    'new'       => ['#e2e3e5','#383d41'],
    'regular'   => ['#d4edda','#155724'],
    'permanent' => ['#cce5ff','#004085'],
    'vip'       => ['#fff3cd','#856404'],
];
$sc = $statusColors[$guest->status] ?? ['#eee','#333'];
$maxDay  = max(array_values($dayCounts)  ?: [1]);
$maxSlot = max(array_values($slotCounts) ?: [1]);
@endphp

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1>{{ $guest->name }}</h1>
        <p>{{ '@' . $guest->username }} · #{{ $guest->id }}</p>
    </div>
    <div style="display:flex; gap:10px; align-items:center;">
        <span class="badge" style="background:{{ $sc[0] }}; color:{{ $sc[1] }}; font-size:14px; padding:6px 14px;">
            {{ $guest->status_label }}
        </span>
        <a href="{{ route('guests.index') }}" class="btn btn-secondary">← Назад</a>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 340px; gap:20px;">

    {{-- Левая колонка --}}
    <div>

        {{-- Общая статистика --}}
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card">
                <div class="label">Визитов</div>
                <div class="value">{{ $visitsCount }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Всего заказов</div>
                <div class="value">{{ $totalOrders }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Переделок</div>
                <div class="value" style="color:#c95a2a;">{{ $redoCount }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Средняя сессия</div>
                <div class="value" style="font-size:20px;">
                    {{ $avgSession ? round($avgSession) . ' мин' : '—' }}
                </div>
            </div>
            <div class="stat-card">
                <div class="label">Обслуживаний/сессия</div>
                <div class="value" style="font-size:20px;">{{ $avgServiced }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Дней с визита</div>
                <div class="value" style="font-size:20px;">{{ $lastVisitDays ?? '—' }}</div>
            </div>
        </div>

        {{-- Когда приходит --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">Когда приходит</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div>
                    <div style="font-size:13px; color:#666; margin-bottom:12px;">По времени суток</div>
                    @foreach($timeSlots as $key => $slot)
                        <div style="margin-bottom:10px;">
                            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                                <span>{{ $slot['label'] }} <span style="color:#999; font-size:11px;">{{ $slot['from'] }}–{{ $slot['to'] }}</span></span>
                                <strong>{{ $slotCounts[$key] ?? 0 }}</strong>
                            </div>
                            <div style="background:#f0f0f0; border-radius:4px; height:8px; overflow:hidden;">
                                <div style="background:#c9a84c; height:100%; width:{{ $maxSlot > 0 ? round(($slotCounts[$key] ?? 0) / $maxSlot * 100) : 0 }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div>
                    <div style="font-size:13px; color:#666; margin-bottom:12px;">По дням недели</div>
                    @foreach($dayCounts as $day => $count)
                        <div style="margin-bottom:10px;">
                            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                                <span>{{ $day }}</span>
                                <strong>{{ $count }}</strong>
                            </div>
                            <div style="background:#f0f0f0; border-radius:4px; height:8px; overflow:hidden;">
                                <div style="background:#4caf74; height:100%; width:{{ $maxDay > 0 ? round($count / $maxDay * 100) : 0 }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Топы --}}
        @foreach([
            ['title'=>'Топ миксы',   'items'=>$topMixes,    'name'=>fn($i)=>$i->itemable?->name ?? '—', 'sub'=>null],
            ['title'=>'Топ табаки',  'items'=>$topTobaccos, 'name'=>fn($i)=>$i->itemable?->name ?? '—', 'sub'=>fn($i)=>$i->itemable?->brand?->name],
            ['title'=>'Топ мастера', 'items'=>$topMasters,  'name'=>fn($i)=>$i->creator?->name ?? '—',  'sub'=>null],
        ] as $top)
        <div class="card" x-data="{ limit: 3 }">
            <h3 style="margin-bottom:16px;">{{ $top['title'] }}</h3>
            @php $items = $top['items']; $getName = $top['name']; $getSub = $top['sub']; @endphp
            @forelse($items->take(3) as $i => $item)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f0f0f0;" class="top-item-{{ $loop->parent->index ?? 0 }}-3">
                    <div>
                        <span style="color:#999; font-size:13px; margin-right:8px;">#{{ $i+1 }}</span>
                        <strong>{{ $getName($item) }}</strong>
                        @if($getSub) <span style="color:#999; font-size:12px; margin-left:4px;">{{ $getSub($item) }}</span> @endif
                    </div>
                    <span style="color:#c9a84c; font-size:13px; font-weight:500;">{{ $item->total }} раз</span>
                </div>
            @empty
                <p style="color:#999; font-size:14px;">Нет данных</p>
            @endforelse

            @if($items->count() > 3)
                <div id="top-more-{{ $loop->index }}" style="display:none;">
                    @foreach($items->skip(3) as $i => $item)
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f0f0f0;">
                            <div>
                                <span style="color:#999; font-size:13px; margin-right:8px;">#{{ $i+4 }}</span>
                                <strong>{{ $getName($item) }}</strong>
                                @if($getSub) <span style="color:#999; font-size:12px; margin-left:4px;">{{ $getSub($item) }}</span> @endif
                            </div>
                            <span style="color:#c9a84c; font-size:13px; font-weight:500;">{{ $item->total }} раз</span>
                        </div>
                    @endforeach
                </div>
                <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;" id="top-btns-{{ $loop->index }}">
                    @foreach([5,10,15,20,50] as $n)
                        @if($items->count() >= $n)
                        <button onclick="showTop({{ $loop->parent->index }}, {{ $n }})" class="btn btn-secondary" style="font-size:12px; padding:4px 10px;">Топ {{ $n }}</button>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
        @endforeach

        {{-- Топ бренды --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">Топ бренды</h3>
            @forelse($topBrands->take(3) as $i => $item)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f0f0f0;">
                    <div>
                        <span style="color:#999; font-size:13px; margin-right:8px;">#{{ $i+1 }}</span>
                        <strong>{{ $item['brand']?->name ?? '—' }}</strong>
                    </div>
                    <span style="color:#c9a84c; font-size:13px; font-weight:500;">{{ $item['total'] }} раз</span>
                </div>
            @empty
                <p style="color:#999; font-size:14px;">Нет данных</p>
            @endforelse
        </div>

        {{-- Любимое место --}}
        @if($favTables->count() > 0)
        <div class="card">
            <h3 style="margin-bottom:16px;">Любимое место</h3>
            @foreach($favTables as $fav)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f0f0f0;">
                    <strong>{{ $fav->table?->name ?? '—' }}</strong>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="background:#f0f0f0; border-radius:4px; height:8px; width:100px; overflow:hidden;">
                            <div style="background:#c9a84c; height:100%; width:{{ $totalTableVisits > 0 ? round($fav->total / $totalTableVisits * 100) : 0 }}%;"></div>
                        </div>
                        <span style="font-size:13px; color:#666; min-width:60px; text-align:right;">
                            {{ $fav->total }} ({{ $totalTableVisits > 0 ? round($fav->total / $totalTableVisits * 100) : 0 }}%)
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- История визитов --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">История визитов</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Дата и время</th>
                        <th>Стол</th>
                        <th>Статус</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visitHistory as $order)
                    @php
                        $statusLabels = ['pending'=>'Новый','in_progress'=>'В работе','ready'=>'Готов','served'=>'У гостя','redo'=>'Переделка','closed'=>'Закрыт','cancelled'=>'Отменён'];
                    @endphp
                    <tr>
                        <td style="color:#999; font-size:13px;">{{ $order->id }}</td>
                        <td style="font-size:13px;">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                        <td style="font-size:13px;">{{ $order->table?->name ?? '—' }}</td>
                        <td><span class="badge badge-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                        <td><a href="{{ route('orders.show', $order) }}" class="btn btn-secondary" style="font-size:12px;">Открыть</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center; color:#999; padding:20px;">Нет визитов</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($visitHistory->hasPages())
                <div style="margin-top:16px;">{{ $visitHistory->links() }}</div>
            @endif
        </div>

    </div>

    {{-- Правая колонка --}}
    <div>

        {{-- Информация --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">Информация</h3>
            <table style="width:100%;">
                <tr><td style="padding:6px 0; color:#666; font-size:13px; width:80px;">ID</td><td>#{{ $guest->id }}</td></tr>
                <tr><td style="padding:6px 0; color:#666; font-size:13px;">Username</td><td>{{ "@" . $guest->username }}</td></tr>
                @if($guest->phone)
                <tr><td style="padding:6px 0; color:#666; font-size:13px;">Телефон</td><td>{{ $guest->phone }}</td></tr>
                @endif
                @if($guest->email)
                <tr><td style="padding:6px 0; color:#666; font-size:13px;">Email</td><td>{{ $guest->email }}</td></tr>
                @endif
                <tr><td style="padding:6px 0; color:#666; font-size:13px;">Добавил</td><td style="font-size:13px;">{{ $guest->creator?->name ?? '—' }}</td></tr>
                <tr><td style="padding:6px 0; color:#666; font-size:13px;">Добавлен</td><td style="font-size:13px;">{{ $guest->created_at->format('d.m.Y') }}</td></tr>
            </table>

            {{-- Редактировать --}}
            @if($guest->created_by === auth()->id() || auth()->user()->hasRole('admin'))
            <div style="margin-top:16px; padding-top:16px; border-top:1px solid #eee;">
                <button onclick="toggleEditForm()" class="btn btn-secondary" style="width:100%; font-size:13px;">✏️ Редактировать</button>
                <div id="edit-form" style="display:none; margin-top:12px;">
                    <form method="POST" action="{{ route('guests.update', $guest) }}">
                        @csrf @method('PUT')
                        <div class="form-group"><label style="font-size:12px;">Имя</label><input type="text" name="name" value="{{ $guest->name }}" required></div>
                        <div class="form-group"><label style="font-size:12px;">Username</label><input type="text" name="username" value="{{ $guest->username }}"></div>
                        <div class="form-group"><label style="font-size:12px;">Телефон</label><input type="text" name="phone" value="{{ $guest->phone }}"></div>
                        <div class="form-group"><label style="font-size:12px;">Email</label><input type="email" name="email" value="{{ $guest->email }}"></div>
                        <button type="submit" class="btn btn-primary" style="width:100%; font-size:13px;">Сохранить</button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Статус (только админ) --}}
            @role('admin')
            <div style="margin-top:12px; padding-top:12px; border-top:1px solid #eee;">
                <div style="font-size:12px; color:#666; margin-bottom:8px;">Принудительный статус</div>
                <form method="POST" action="{{ route('guests.status', $guest) }}" style="display:flex; gap:8px;">
                    @csrf
                    <select name="status_override" style="flex:1; padding:6px; border:1px solid #ddd; border-radius:6px; font-size:13px;">
                        <option value="">Авто</option>
                        <option value="new"     {{ $guest->status_override === 'new'     ? 'selected' : '' }}>Новый</option>
                        <option value="regular" {{ $guest->status_override === 'regular' ? 'selected' : '' }}>Регуляр</option>
                        <option value="permanent" {{ $guest->status_override === 'permanent' ? 'selected' : '' }}>Постоянный</option>
                        <option value="vip"     {{ $guest->status_override === 'vip'     ? 'selected' : '' }}>VIP</option>
                    </select>
                    <button type="submit" class="btn btn-primary" style="font-size:12px;">OK</button>
                </form>
            </div>
            @endrole
        </div>

        {{-- Ярлыки --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">Ярлыки</h3>

            @php
            $typeGroups = [
                'positive' => ['label' => '✅ Позитив', 'bg' => '#d4edda', 'color' => '#155724'],
                'neutral'  => ['label' => '➖ Нейтрал', 'bg' => '#e2e3e5', 'color' => '#383d41'],
                'negative' => ['label' => '❌ Негатив', 'bg' => '#f8d7da', 'color' => '#721c24'],
            ];
            $guestLabels = $guest->labels;
            @endphp

            {{-- По категориям --}}
            @foreach($typeGroups as $type => $group)
            @php $typeLabels = $guestLabels->where('type', $type); @endphp
            <div style="margin-bottom:14px;">
                <div style="font-size:12px; color:#666; margin-bottom:6px; font-weight:600;">{{ $group['label'] }}</div>
                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                    @forelse($typeLabels as $gl)
                    <div style="display:flex; align-items:center; gap:4px; background:{{ $group['bg'] }}; color:{{ $group['color'] }}; padding:4px 10px; border-radius:20px; font-size:13px;">
                        <span>{{ $gl->label->name }}</span>
                        <form method="POST" action="{{ route('guests.labels.remove', [$guest, $gl]) }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:none; border:none; cursor:pointer; color:{{ $group['color'] }}; font-size:14px; padding:0 0 0 4px; line-height:1;" onclick="return confirm('Снять ярлык?')">×</button>
                        </form>
                    </div>
                    @empty
                    <span style="color:#999; font-size:13px;">—</span>
                    @endforelse
                </div>
            </div>
            @endforeach

            {{-- Добавить ярлык --}}
            <div style="padding-top:12px; border-top:1px solid #eee;">
                <form method="POST" action="{{ route('guests.labels.add', $guest) }}">
                    @csrf
                    <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:8px; align-items:end;">
                        <div>
                            <label style="font-size:12px; color:#666; display:block; margin-bottom:4px;">Ярлык</label>
                            <select name="label_id" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px; font-size:13px;">
                                <option value="">— Выбрать —</option>
                                @foreach(\App\Models\Label::where('is_active', true)->orderBy('name')->get() as $lbl)
                                    <option value="{{ $lbl->id }}">{{ $lbl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="font-size:12px; color:#666; display:block; margin-bottom:4px;">Категория</label>
                            <select name="type" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px; font-size:13px;">
                                <option value="positive">✅ Позитив</option>
                                <option value="neutral">➖ Нейтрал</option>
                                <option value="negative">❌ Негатив</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="font-size:13px;">Добавить</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Заметки --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">Заметки</h3>
            <form method="POST" action="{{ route('guests.notes.store', $guest) }}" style="margin-bottom:16px;">
                @csrf
                <div style="display:flex; gap:8px;">
                    <input type="text" name="note" placeholder="Добавить заметку..." style="flex:1; padding:8px; border:1px solid #ddd; border-radius:6px; font-size:14px;" required>
                    <button type="submit" class="btn btn-primary" style="font-size:13px;">+</button>
                </div>
            </form>

            @forelse($guest->notes->whereNull('deleted_at') as $note)
                <div style="padding:10px; background:#f9f9f9; border-radius:6px; margin-bottom:8px;">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <div>
                            <div style="font-size:14px;">{{ $note->note }}</div>
                            <div style="font-size:12px; color:#999; margin-top:4px;">{{ $note->user?->name ?? '—' }} · {{ $note->created_at->format('d.m.Y H:i') }}</div>
                        </div>
                        @if($note->user_id === auth()->id() || auth()->user()->hasRole('admin'))
                        <form method="POST" action="{{ route('guests.notes.destroy', [$guest, $note]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:none; border:none; cursor:pointer; color:#999; font-size:18px;" onclick="return confirm('Удалить?')">×</button>
                        </form>
                        @endif
                    </div>
                </div>
            @empty
                <p style="color:#999; font-size:14px;">Заметок нет</p>
            @endforelse
        </div>

        {{-- История изменений --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">История изменений</h3>
            @php
            $actionLabels = ['created'=>'Создан','updated'=>'Обновлён','note_added'=>'Заметка добавлена','note_deleted'=>'Заметка удалена','status_changed'=>'Статус изменён'];
            $statusLabels = ['new'=>'Новый','regular'=>'Регуляр','permanent'=>'Постоянный','vip'=>'VIP'];
            $fieldLabels  = ['name'=>'Имя','username'=>'Username','phone'=>'Телефон','email'=>'Email'];
            @endphp
            @forelse($guest->logs as $log)
                <div style="padding:8px 0; border-bottom:1px solid #f0f0f0; font-size:13px;">
                    <div><strong>{{ $log->user?->name ?? '—' }}</strong> — {{ $actionLabels[$log->action] ?? $log->action }}
                        @if($log->action === 'created' && isset($log->new_value['context']) && $log->new_value['context'] === 'order')
                            <span style="color:#999; font-size:12px;">
                                · при оформлении
                                <a href="{{ route('orders.show', $log->new_value['order_id']) }}" style="color:#c9a84c;">заказа #{{ $log->new_value['order_id'] }}</a>
                            </span>
                        @endif
                    </div>

                    @if($log->action === 'updated' && ($log->old_value || $log->new_value))
                        @php
                            $fieldLabels  = ['name'=>'Имя','username'=>'Username','phone'=>'Телефон','email'=>'Email'];
                            $typeLabels   = ['positive'=>'✅ Позитив','neutral'=>'➖ Нейтрал','negative'=>'❌ Негатив'];
                        @endphp

                        {{-- Ярлык добавлен --}}
                        @if(isset($log->new_value['label_added']))
                            <div style="color:#666; font-size:12px; margin-top:2px;">
                                🏷️ Ярлык добавлен: <strong>{{ $log->new_value['label_added'] }}</strong>
                                @if(isset($log->new_value['type']))
                                    · <span>{{ $typeLabels[$log->new_value['type']] ?? $log->new_value['type'] }}</span>
                                @endif
                            </div>

                        {{-- Ярлык удалён --}}
                        @elseif(isset($log->old_value['label_removed']))
                            <div style="color:#666; font-size:12px; margin-top:2px;">
                                🏷️ Ярлык снят: <strong>{{ $log->old_value['label_removed'] }}</strong>
                                @if(isset($log->old_value['type']))
                                    · <span>{{ $typeLabels[$log->old_value['type']] ?? $log->old_value['type'] }}</span>
                                @endif
                            </div>

                        {{-- Категория ярлыка изменена --}}
                        @elseif(isset($log->old_value['label']) && isset($log->new_value['label']))
                            <div style="color:#666; font-size:12px; margin-top:2px;">
                                🏷️ Ярлык <strong>{{ $log->new_value['label'] }}</strong>:
                                <span style="color:#c95a2a;">{{ $typeLabels[$log->old_value['type']] ?? '—' }}</span>
                                →
                                <span style="color:#4caf74;">{{ $typeLabels[$log->new_value['type']] ?? '—' }}</span>
                            </div>

                        {{-- Обычное обновление полей --}}
                        @else
                            @foreach($log->new_value as $field => $newVal)
                                @if(isset($log->old_value[$field]) && $log->old_value[$field] !== $newVal)
                                    <div style="color:#666; font-size:12px; margin-top:2px;">
                                        {{ $fieldLabels[$field] ?? $field }}:
                                        <span style="color:#c95a2a;">{{ $log->old_value[$field] ?: '—' }}</span>
                                        →
                                        <span style="color:#4caf74;">{{ $newVal ?: '—' }}</span>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    @endif

                    {{-- Статус изменён --}}
                    @if($log->action === 'status_changed')
                        <div style="color:#666; font-size:12px; margin-top:2px;">
                            <span style="color:#c95a2a;">{{ $statusLabels[$log->old_value['status'] ?? ''] ?? ($log->old_value['status'] ? $log->old_value['status'] : 'Авто') }}</span>
                            →
                            <span style="color:#4caf74;">{{ $statusLabels[$log->new_value['status'] ?? ''] ?? ($log->new_value['status'] ? $log->new_value['status'] : 'Авто') }}</span>
                        </div>
                    @endif

                    {{-- Заметка добавлена --}}
                    @if($log->action === 'note_added' && !empty($log->new_value['note']))
                        <div style="color:#666; font-size:12px; margin-top:2px; font-style:italic;">
                            💬 {{ $log->new_value['note'] }}
                        </div>
                    @endif

                    {{-- Заметка удалена --}}
                    @if($log->action === 'note_deleted' && !empty($log->old_value['note']))
                        <div style="color:#999; font-size:12px; margin-top:2px; font-style:italic; text-decoration:line-through;">
                            💬 {{ $log->old_value['note'] }}
                        </div>
                    @endif

                    <div style="color:#999; font-size:12px; margin-top:2px;">{{ $log->created_at->format('d.m.Y H:i:s') }}</div>
                </div>
            @empty
                <p style="color:#999; font-size:14px;">Нет истории</p>
            @endforelse
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleEditForm() {
    const form = document.getElementById('edit-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function showTop(topIndex, limit) {
    const more = document.getElementById('top-more-' + topIndex);
    if (more) {
        const items = more.querySelectorAll('div');
        items.forEach((el, i) => {
            el.style.display = i < (limit - 3) ? 'flex' : 'none';
        });
        more.style.display = 'block';
    }
}
</script>
@endpush