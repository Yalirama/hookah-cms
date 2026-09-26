@extends('layouts.app')

@section('content')

@php
$strengthLabels = ['light' => 'Лёгкий', 'medium' => 'Средний', 'strong' => 'Крепкий'];
$strengthColors = ['light' => ['#d4edda','#155724'], 'medium' => ['#fff3cd','#856404'], 'strong' => ['#f8d7da','#721c24']];
$maxDay  = max(array_values($dayCounts)  ?: [1]);
$maxSlot = max(array_values($slotCounts) ?: [1]);
@endphp

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1>{{ $mix->name }}</h1>
        @if($mix->description_short)<p>{{ $mix->description_short }}</p>@endif
    </div>
    <div style="display:flex; gap:10px; align-items:center;">
        @if($mix->strength && isset($strengthColors[$mix->strength]))
            <span class="badge" style="background:{{ $strengthColors[$mix->strength][0] }}; color:{{ $strengthColors[$mix->strength][1] }}; font-size:14px; padding:6px 14px;">
                {{ $strengthLabels[$mix->strength] }}
            </span>
        @endif
        <a href="{{ route('mixes.edit', $mix) }}" class="btn btn-primary">✏️ Редактировать</a>
        <a href="{{ route('mixes.index') }}" class="btn btn-secondary">← Назад</a>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 340px; gap:20px;">

    <div>

        {{-- Основная информация --}}
        <div class="card">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div>
                    <div style="font-size:12px; color:#999; margin-bottom:4px;">ID</div>
                    <div>#{{ $mix->id }}</div>
                </div>
                <div>
                    <div style="font-size:12px; color:#999; margin-bottom:4px;">Создал</div>
                    <div>{{ $mix->creator?->name ?? '—' }} <span style="color:#999; font-size:12px;">{{ $mix->created_at->format('d.m.Y H:i') }}</span></div>
                </div>
                @if($mix->updater)
                <div>
                    <div style="font-size:12px; color:#999; margin-bottom:4px;">Последнее изменение</div>
                    <div>{{ $mix->updater?->name ?? '—' }} <span style="color:#999; font-size:12px;">{{ $mix->updated_at->format('d.m.Y H:i') }}</span></div>
                </div>
                @endif
            </div>

            @if($mix->description_long)
                <div style="padding-top:16px; border-top:1px solid #f0f0f0;">
                    <div style="font-size:12px; color:#999; margin-bottom:6px;">Описание</div>
                    <p style="font-size:14px; color:#444; line-height:1.6;">{{ $mix->description_long }}</p>
                </div>
            @endif
        </div>

        {{-- Состав --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">Состав</h3>
            @forelse($mix->ingredients as $ing)
                <div style="border:1px solid #eee; border-radius:8px; margin-bottom:10px; overflow:hidden;">

                    {{-- Основной ингредиент --}}
                    <div style="padding:12px 16px; display:flex; justify-content:space-between; align-items:center; {{ $ing->trashed() ? 'opacity:0.5' : '' }}">
                        <div>
                            <strong>{{ $ing->tobacco?->brand?->name ?? '—' }}</strong>
                            · {{ $ing->tobacco?->name ?? 'Удалённый табак' }}
                            @if($ing->trashed()) <span style="color:#999; font-size:12px;">(удалён)</span> @endif
                        </div>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span style="color:#c9a84c; font-weight:bold;">{{ $ing->grams }}г</span>
                            @if($ing->alternatives->count() > 0)
                                <button onclick="toggleAlts({{ $ing->id }})" style="background:none; border:1px solid #ddd; border-radius:4px; padding:4px 10px; cursor:pointer; font-size:12px; color:#666;">
                                    Альтернативы ({{ $ing->alternatives->count() }}) <span id="alt-arrow-{{ $ing->id }}">▼</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Альтернативы --}}
                    @if($ing->alternatives->count() > 0)
                        <div id="alts-{{ $ing->id }}" style="display:none; background:#f9f9f9; border-top:1px solid #eee; padding:8px 16px;">
                            <div style="font-size:12px; color:#999; margin-bottom:8px;">Альтернативы:</div>
                            @foreach($ing->alternatives as $alt)
                                <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #eee; font-size:14px;">
                                    <span>{{ $alt->tobacco?->brand?->name ?? '—' }} · {{ $alt->tobacco?->name ?? '—' }}</span>
                                    <span style="color:#666;">{{ $alt->grams }}г</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                </div>
            @empty
                <p style="color:#999;">Нет ингредиентов</p>
            @endforelse
        </div>

        {{-- Статистика --}}
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card">
                <div class="label">Всего заказов</div>
                <div class="value">{{ $totalOrders }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Запросов на переделку</div>
                <div class="value" style="color:#c95a2a;">{{ $redoOrders }}</div>
            </div>
            <div class="stat-card">
                <div class="label">За 30 дней</div>
                <div class="value" style="color:#4caf74;">{{ $last30 }}</div>
            </div>
        </div>

        {{-- Когда заказывают --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">Когда заказывают</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div>
                    <div style="font-size:13px; color:#666; margin-bottom:12px;">По времени суток</div>
                    @foreach($timeSlots as $key => $slot)
                        <div style="margin-bottom:10px;">
                            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                                <span>{{ $slot['label'] }} <span style="color:#999;">{{ $slot['from'] }}–{{ $slot['to'] }}</span></span>
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

        {{-- Заметки --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">Заметки мастеров</h3>
            <form method="POST" action="{{ route('mixes.notes.store', $mix) }}" style="margin-bottom:20px;">
                @csrf
                <div style="display:flex; gap:10px;">
                    <input type="text" name="note" placeholder="Добавить заметку..." style="flex:1; padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;" required>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>

            @forelse($mix->notes->whereNull('deleted_at') as $note)
                <div style="padding:12px; background:#f9f9f9; border-radius:6px; margin-bottom:8px;">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <div>
                            <div style="font-size:14px;">{{ $note->note }}</div>
                            <div style="font-size:12px; color:#999; margin-top:4px;">
                                {{ $note->user?->name ?? '—' }} · {{ $note->created_at->format('d.m.Y H:i') }}
                            </div>
                        </div>
                        @if($note->user_id === auth()->id() || auth()->user()->hasRole('admin'))
                            <form method="POST" action="{{ route('mixes.notes.destroy', [$mix, $note]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; cursor:pointer; color:#999; font-size:18px;" onclick="return confirm('Удалить?')">×</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p style="color:#999; font-size:14px;">Заметок пока нет</p>
            @endforelse
        </div>

    </div>

    {{-- Правая колонка --}}
    <div>
        <div class="card" style="position:sticky; top:20px;">
            <h3 style="margin-bottom:12px;">История правок</h3>
            @forelse($mix->revisions->take(10) as $rev)
                <div style="padding:8px 0; border-bottom:1px solid #f0f0f0; font-size:13px;">
                    <div><strong>{{ $rev->user?->name ?? '—' }}</strong></div>
                    <div style="color:#666;">{{ $rev->changed_fields['action'] === 'created' ? 'Создал микс' : 'Отредактировал' }}</div>
                    @if($rev->comment)<div style="color:#999;">{{ $rev->comment }}</div>@endif
                    <div style="color:#999; font-size:12px;">{{ $rev->created_at->format('d.m.Y H:i') }}</div>
                </div>
            @empty
                <p style="color:#999; font-size:13px;">Нет истории</p>
            @endforelse

            @if(!$mix->is_active)
                <div style="margin-top:16px; padding:10px; background:#f8d7da; border-radius:6px; font-size:13px; color:#721c24;">Микс деактивирован</div>
            @endif

            @role('admin')
            @if($mix->is_active)
                <form method="POST" action="{{ route('mixes.deactivate', $mix) }}" style="margin-top:16px;">
                    @csrf
                    <button type="submit" class="btn btn-danger" style="width:100%;" onclick="return confirm('Деактивировать микс?')">Деактивировать</button>
                </form>
            @endif
            @endrole
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function toggleAlts(id) {
    const el    = document.getElementById('alts-' + id);
    const arrow = document.getElementById('alt-arrow-' + id);
    if (el.style.display === 'none') {
        el.style.display = 'block';
        arrow.textContent = '▲';
    } else {
        el.style.display = 'none';
        arrow.textContent = '▼';
    }
}
</script>
@endpush