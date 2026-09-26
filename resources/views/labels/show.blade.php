@extends('layouts.app')

@section('content')

@php
$actionLabels = [
    'created'     => 'Создан',
    'updated'     => 'Обновлён',
    'note_added'  => 'Заметка добавлена',
    'note_deleted'=> 'Заметка удалена',
    'activated'   => 'Активирован',
    'deactivated' => 'Деактивирован',
];
$fieldLabels = ['name'=>'Название','description'=>'Описание'];
$typeGroups = [
    'positive' => ['label' => '✅ Позитив', 'bg' => '#d4edda', 'color' => '#155724'],
    'neutral'  => ['label' => '➖ Нейтрал', 'bg' => '#e2e3e5', 'color' => '#383d41'],
    'negative' => ['label' => '❌ Негатив', 'bg' => '#f8d7da', 'color' => '#721c24'],
];
$statusColors = [
    'new'       => ['#e2e3e5','#383d41'],
    'regular'   => ['#d4edda','#155724'],
    'permanent' => ['#cce5ff','#004085'],
    'vip'       => ['#fff3cd','#856404'],
];
@endphp

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1>{{ $label->name }}</h1>
        <p>Создал {{ $label->creator?->name ?? '—' }} · {{ $label->created_at->format('d.m.Y') }}</p>
    </div>
    <div style="display:flex; gap:10px; align-items:center;">
        @if(!$label->is_active)
            <span class="badge" style="background:#e2e3e5; color:#383d41; font-size:13px; padding:5px 12px;">Деактивирован</span>
        @endif
        <a href="{{ route('labels.index') }}" class="btn btn-secondary">← Назад</a>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 340px; gap:20px;">

    {{-- Левая колонка --}}
    <div>

        {{-- Статистика по типам --}}
        <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="stat-card">
                <div class="label">Всего гостей</div>
                <div class="value">{{ $usageCount }}</div>
            </div>
            <div class="stat-card">
                <div class="label">✅ Позитив</div>
                <div class="value" style="color:#155724;">{{ $typeCounts['positive'] }}</div>
            </div>
            <div class="stat-card">
                <div class="label">➖ Нейтрал</div>
                <div class="value" style="color:#383d41;">{{ $typeCounts['neutral'] }}</div>
            </div>
            <div class="stat-card">
                <div class="label">❌ Негатив</div>
                <div class="value" style="color:#721c24;">{{ $typeCounts['negative'] }}</div>
            </div>
        </div>

        {{-- Гости по категориям --}}
        @foreach($typeGroups as $type => $group)
        @if($guestsByType[$type]->count() > 0)
        <div class="card">
            <h3 style="margin-bottom:16px; color:{{ $group['color'] }};">{{ $group['label'] }}</h3>
            @foreach($guestsByType[$type] as $gl)
            @php $guest = $gl->guest; $sc = $statusColors[$guest->status] ?? ['#eee','#333']; @endphp
            <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #f0f0f0;">
                <div>
                    <strong>{{ $guest->name }}</strong>
                    <span style="color:#999; font-size:13px; margin-left:6px;">{{ '@' . $guest->username }}</span>
                    <div style="margin-top:4px;">
                        <span class="badge" style="background:{{ $sc[0] }}; color:{{ $sc[1] }}; font-size:11px;">{{ $guest->status_label }}</span>
                        <span style="color:#999; font-size:12px; margin-left:6px;">{{ $guest->visits_count }} визитов</span>
                        <span style="color:#999; font-size:12px; margin-left:6px;">Добавил: {{ $gl->addedBy?->name ?? '—' }} · {{ $gl->added_at->format('d.m H:i') }}</span>
                    </div>
                </div>
                <a href="{{ route('guests.show', $guest) }}" class="btn btn-secondary" style="font-size:13px;">Открыть</a>
            </div>
            @endforeach
        </div>
        @endif
        @endforeach

        {{-- Заметки --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">Заметки</h3>
            <form method="POST" action="{{ route('labels.notes.store', $label) }}" style="margin-bottom:16px;">
                @csrf
                <div style="display:flex; gap:8px;">
                    <input type="text" name="note" placeholder="Добавить заметку..." style="flex:1; padding:8px; border:1px solid #ddd; border-radius:6px; font-size:14px;" required>
                    <button type="submit" class="btn btn-primary" style="font-size:13px;">+</button>
                </div>
            </form>

            @forelse($label->notes->whereNull('deleted_at') as $note)
                <div style="padding:10px; background:#f9f9f9; border-radius:6px; margin-bottom:8px;">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <div>
                            <div style="font-size:14px;">{{ $note->note }}</div>
                            <div style="font-size:12px; color:#999; margin-top:4px;">{{ $note->user?->name ?? '—' }} · {{ $note->created_at->format('d.m.Y H:i') }}</div>
                        </div>
                        @if($note->user_id === auth()->id() || auth()->user()->hasRole('admin'))
                        <form method="POST" action="{{ route('labels.notes.destroy', [$label, $note]) }}">
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

    </div>

    {{-- Правая колонка --}}
    <div>

        {{-- Редактировать --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">Редактировать</h3>
            <form method="POST" action="{{ route('labels.update', $label) }}">
                @csrf @method('PUT')
                <div class="form-group">
                    <label>Название</label>
                    <input type="text" name="name" value="{{ $label->name }}" required>
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" rows="3">{{ $label->description }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Сохранить</button>
            </form>

            @role('admin')
            <form method="POST" action="{{ route('labels.toggle', $label) }}" style="margin-top:10px;">
                @csrf
                <button type="submit" class="btn {{ $label->is_active ? 'btn-danger' : 'btn-success' }}" style="width:100%;">
                    {{ $label->is_active ? 'Деактивировать' : 'Активировать' }}
                </button>
            </form>
            @endrole
        </div>

        {{-- История изменений --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">История изменений</h3>
            @forelse($label->logs as $log)
                <div style="padding:8px 0; border-bottom:1px solid #f0f0f0; font-size:13px;">
                    <div><strong>{{ $log->user?->name ?? '—' }}</strong> — {{ $actionLabels[$log->action] ?? $log->action }}</div>

                    @if($log->action === 'updated' && $log->old_value)
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

                    @if($log->action === 'note_added' && !empty($log->new_value['note']))
                        <div style="color:#666; font-size:12px; margin-top:2px; font-style:italic;">💬 {{ $log->new_value['note'] }}</div>
                    @endif

                    @if($log->action === 'note_deleted' && !empty($log->old_value['note']))
                        <div style="color:#999; font-size:12px; margin-top:2px; font-style:italic; text-decoration:line-through;">💬 {{ $log->old_value['note'] }}</div>
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