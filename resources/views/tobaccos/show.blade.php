@extends('layouts.app')

@section('content')

@php
$strengthLabels = ['light' => 'Лёгкий', 'medium' => 'Средний', 'strong' => 'Крепкий'];
$strengthColors = ['light' => ['#d4edda','#155724'], 'medium' => ['#fff3cd','#856404'], 'strong' => ['#f8d7da','#721c24']];
$maxDay = max(array_values($dayCounts) ?: [1]);
$maxSlot = max(array_values($slotCounts) ?: [1]);
@endphp

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <div style="font-size:13px; color:#999; margin-bottom:4px;">{{ $tobacco->brand?->name }}</div>
        <h1>{{ $tobacco->name }}</h1>
        @if($tobacco->alt_name)
            <p style="color:#999; font-size:14px; margin-top:-4px;">{{ $tobacco->alt_name }}</p>
        @endif
        @if($tobacco->description_short)
            <p>{{ $tobacco->description_short }}</p>
        @endif
    </div>
    <div style="display:flex; gap:10px; align-items:center;">
        @if(isset($strengthColors[$tobacco->strength]))
            <span class="badge" style="background:{{ $strengthColors[$tobacco->strength][0] }}; color:{{ $strengthColors[$tobacco->strength][1] }}; font-size:14px; padding:6px 14px;">
                {{ $strengthLabels[$tobacco->strength] }}
            </span>
        @endif
        <a href="{{ route('tobaccos.index') }}" class="btn btn-secondary">← Назад</a>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 340px; gap:20px;">

    {{-- Левая колонка --}}
    <div>

        {{-- Основная информация --}}
        <div class="card">
            <div style="display:flex; gap:16px; flex-wrap:wrap;">
                <div style="flex:1; min-width:200px;">
                    <div style="font-size:12px; color:#999; margin-bottom:6px;">ID</div>
                    <div style="font-size:15px;">#{{ $tobacco->id }}</div>
                </div>
                <div style="flex:1; min-width:200px;">
                    <div style="font-size:12px; color:#999; margin-bottom:6px;">Бренд</div>
                    <div style="font-size:15px;">{{ $tobacco->brand?->name ?? '—' }}</div>
                </div>
                <div style="flex:2; min-width:300px;">
                    <div style="font-size:12px; color:#999; margin-bottom:6px;">Вкусы</div>
                    <div>
                        @forelse($tobacco->flavorTags as $tag)
                            <span class="badge" style="background:#f0f0f0; color:#555; margin:2px;">{{ $tag->name }}</span>
                        @empty
                            <span style="color:#999;">—</span>
                        @endforelse
                    </div>
                    @php
                        $categories = $tobacco->flavorTags->map(fn($t) => $t->category)->filter()->unique('id');
                    @endphp
                    @if($categories->count())
                        <div style="font-size:12px; color:#999; margin-top:8px; margin-bottom:6px;">Категории</div>
                        <div>
                            @foreach($categories as $cat)
                                <span class="badge" style="background:{{ $cat->color }}22; color:{{ $cat->color }}; margin:2px; border:1px solid {{ $cat->color }}44;">{{ $cat->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if($tobacco->description_long)
                <div style="margin-top:16px; padding-top:16px; border-top:1px solid #f0f0f0;">
                    <div style="font-size:12px; color:#999; margin-bottom:6px;">Описание</div>
                    <p style="font-size:14px; color:#444; line-height:1.6;">{{ $tobacco->description_long }}</p>
                </div>
            @endif
        </div>

        {{-- Статистика заказов --}}
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
                {{-- Слоты --}}
                <div>
                    <div style="font-size:13px; color:#666; margin-bottom:12px;">По времени суток</div>
                    @foreach($timeSlots as $key => $slot)
                        <div style="margin-bottom:10px;">
                            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                                <span>{{ $slot['label'] }} <span style="color:#999;">{{ $slot['from'] }}–{{ $slot['to'] }}</span></span>
                                <strong>{{ $slotCounts[$key] ?? 0 }}</strong>
                            </div>
                            <div style="background:#f0f0f0; border-radius:4px; height:8px; overflow:hidden;">
                                <div style="background:#c9a84c; height:100%; width:{{ $maxSlot > 0 ? round(($slotCounts[$key] ?? 0) / $maxSlot * 100) : 0 }}%; transition:width 0.3s;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Дни недели --}}
                <div>
                    <div style="font-size:13px; color:#666; margin-bottom:12px;">По дням недели</div>
                    @foreach($dayCounts as $day => $count)
                        <div style="margin-bottom:10px;">
                            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                                <span>{{ $day }}</span>
                                <strong>{{ $count }}</strong>
                            </div>
                            <div style="background:#f0f0f0; border-radius:4px; height:8px; overflow:hidden;">
                                <div style="background:#4caf74; height:100%; width:{{ $maxDay > 0 ? round($count / $maxDay * 100) : 0 }}%; transition:width 0.3s;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Заметки мастеров --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">Заметки мастеров</h3>

            {{-- Форма добавления --}}
            <form method="POST" action="{{ route('tobaccos.notes.store', $tobacco) }}" style="margin-bottom:20px;">
                @csrf
                <div style="display:flex; gap:10px;">
                    <input type="text" name="note" placeholder="Добавить заметку..." style="flex:1; padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;" required>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>

            @forelse($tobacco->notes->whereNull('deleted_at') as $note)
                <div style="padding:12px; background:#f9f9f9; border-radius:6px; margin-bottom:8px;">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <div>
                            <div style="font-size:14px;">{{ $note->note }}</div>
                            <div style="font-size:12px; color:#999; margin-top:4px;">
                                {{ $note->user?->name ?? '—' }} · {{ $note->created_at->format('d.m.Y H:i') }}
                            </div>
                        </div>
                        @if($note->user_id === auth()->id() || auth()->user()->hasRole('admin'))
                            <form method="POST" action="{{ route('tobaccos.notes.destroy', [$tobacco, $note]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; cursor:pointer; color:#999; font-size:18px;" onclick="return confirm('Удалить заметку?')">×</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p style="color:#999; font-size:14px;">Заметок пока нет</p>
            @endforelse
        </div>

        {{-- История деактивации --}}
        @if($tobacco->venueTobacco && $tobacco->venueTobacco->logs->count())
        <div class="card">
            <h3 style="margin-bottom:16px;">История активации/деактивации</h3>
            @foreach($tobacco->venueTobacco->logs->sortByDesc('created_at') as $log)
                <div style="padding:10px 0; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:start;">
                    <div>
                        <span style="font-size:13px;">
                            @if($log->action === 'deactivated')
                                <span style="color:#dc3545;">⛔ Деактивирован</span>
                            @else
                                <span style="color:#28a745;">✅ Активирован</span>
                            @endif
                        </span>
                        @if($log->comment)
                            <div style="font-size:12px; color:#666; margin-top:2px;">{{ $log->comment }}</div>
                        @endif
                        <div style="font-size:12px; color:#999; margin-top:2px;">{{ $log->user?->name ?? '—' }}</div>
                    </div>
                    <div style="font-size:12px; color:#999; white-space:nowrap;">{{ $log->created_at->format('d.m.Y H:i') }}</div>
                </div>
            @endforeach
        </div>
        @endif

    </div>

    {{-- Правая колонка --}}
    <div>
        <div class="card" style="position:sticky; top:20px;">
            <h3 style="margin-bottom:12px;">Информация</h3>
            <table style="width:100%;">
                <tr>
                    <td style="padding:8px 0; color:#666; font-size:13px;">Бренд</td>
                    <td style="font-size:14px;">{{ $tobacco->brand?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:#666; font-size:13px;">Крепость</td>
                    <td>
                        @if(isset($strengthColors[$tobacco->strength]))
                            <span class="badge" style="background:{{ $strengthColors[$tobacco->strength][0] }}; color:{{ $strengthColors[$tobacco->strength][1] }};">
                                {{ $strengthLabels[$tobacco->strength] }}
                            </span>
                        @else —
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:#666; font-size:13px;">Добавлен</td>
                    <td style="font-size:13px;">{{ $tobacco->created_at->format('d.m.Y') }}</td>
                </tr>
            </table>
        </div>
    </div>

</div>
@endsection