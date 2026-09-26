@extends('layouts.app')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1>Новый заказ</h1>
        <p>Создание заказа для гостя</p>
    </div>
    <a href="{{ route('orders.index') }}" class="btn btn-secondary">← Назад</a>
</div>

<div class="card" style="max-width:680px;">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('orders.store') }}">
        @csrf

        <div class="form-group">
            <label>Название заказа <span style="color:#999; font-size:12px;">(если не указать — сгенерируется автоматически)</span></label>
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Например: Гость Алибек">
        </div>

        <div class="form-group">
            <label>Стол <span style="color:red">*</span></label>
            <select name="table_id" required>
                <option value="">— Выберите стол —</option>
                @foreach($tables->groupBy(fn($t) => $t->type?->name ?? 'Без типа') as $typeName => $group)
                    <optgroup label="{{ $typeName }}">
                        @foreach($group as $table)
                            <option value="{{ $table->id }}" {{ old('table_id') == $table->id ? 'selected' : '' }}>
                                {{ $table->name }} @if($table->capacity)({{ $table->capacity }} мест)@endif
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Время заказа <span style="color:#999; font-size:12px;">(по умолчанию — текущее время)</span></label>
            <input type="datetime-local" name="started_at" value="{{ old('started_at') }}">
        </div>

        <div class="form-group">
            <label>Комментарий от гостя</label>
            <textarea name="guest_comment" rows="3" placeholder="Пожелания гостя...">{{ old('guest_comment') }}</textarea>
        </div>

        {{-- Гость --}}
        <div class="form-group">
            <label>Гость <span style="color:#999; font-size:12px;">(необязательно)</span></label>

            {{-- Поиск гостя --}}
            <div style="position:relative;">
                <input type="text" id="guest-search" placeholder="Поиск по имени, username, телефону..." style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;" autocomplete="off">
                <div id="guest-results" style="display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #ddd; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,0.1); z-index:100; max-height:240px; overflow-y:auto;"></div>
            </div>

            {{-- Выбранный гость --}}
            <div id="selected-guest" style="display:none; margin-top:8px; padding:10px 14px; background:#f9f9f9; border-radius:6px; display:flex; justify-content:space-between; align-items:center;">
                <div id="selected-guest-info"></div>
                <button type="button" onclick="clearGuest()" style="background:none; border:none; cursor:pointer; color:#999; font-size:18px;">×</button>
            </div>
            <input type="hidden" name="guest_id" id="guest-id-input">

            {{-- Создать нового гостя --}}
            <div style="margin-top:8px;">
                <button type="button" onclick="toggleNewGuest()" style="background:none; border:1px dashed #ddd; border-radius:6px; padding:6px 14px; cursor:pointer; font-size:13px; color:#666; width:100%;">
                    + Создать нового гостя
                </button>
                <div id="new-guest-form" style="display:none; margin-top:10px; padding:14px; background:#f9f9f9; border-radius:8px;">
                    <div style="font-size:13px; color:#555; margin-bottom:10px; font-weight:600;">Новый гость</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <label style="font-size:12px; color:#666;">Имя <span style="color:red">*</span></label>
                            <input type="text" name="new_guest_name" id="new-guest-name" placeholder="Имя гостя" style="width:100%; padding:7px 10px; border:1px solid #ddd; border-radius:6px; font-size:13px; margin-top:4px;">
                        </div>
                        <div>
                            <label style="font-size:12px; color:#666;">Телефон</label>
                            <input type="text" name="new_guest_phone" placeholder="+7 777 000 00 00" style="width:100%; padding:7px 10px; border:1px solid #ddd; border-radius:6px; font-size:13px; margin-top:4px;">
                        </div>
                        <div>
                            <label style="font-size:12px; color:#666;">Username</label>
                            <input type="text" name="new_guest_username" placeholder="Авто" style="width:100%; padding:7px 10px; border:1px solid #ddd; border-radius:6px; font-size:13px; margin-top:4px;">
                        </div>
                        <div>
                            <label style="font-size:12px; color:#666;">Email</label>
                            <input type="email" name="new_guest_email" placeholder="email@example.com" style="width:100%; padding:7px 10px; border:1px solid #ddd; border-radius:6px; font-size:13px; margin-top:4px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex; gap:10px; margin-top:8px;">
            <button type="submit" class="btn btn-primary">Создать заказ</button>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
let searchTimeout;

document.getElementById('guest-search').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();

    if (q.length < 2) {
        results.innerHTML = '';
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch(`/orders/guests/search?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(guests => {
                const results = document.getElementById('guest-results');

                if (guests.length === 0) {
                    results.innerHTML = '<div style="padding:12px; color:#999; font-size:14px;">Гостей не найдено</div>';
                } else {
                    const statusColors = {
                        new:     ['#e2e3e5','#383d41'],
                        regular: ['#d4edda','#155724'],
                        vip:     ['#fff3cd','#856404'],
                    };
                    results.innerHTML = guests.map(g => {
                        const sc = statusColors[g.status] || ['#eee','#333'];
                        return `
                            <div onclick="selectGuest(${g.id}, '${g.name}', '${g.username}', '${g.status_label}', ${g.visits_count})"
                                style="padding:10px 14px; cursor:pointer; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center;"
                                onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background=''">
                                <div>
                                    <div style="font-size:14px; font-weight:500;">${g.name}</div>
                                    <div style="font-size:12px; color:#999;">${g.username} ${g.phone ? '· ' + g.phone : ''}</div>
                                </div>
                                <div style="text-align:right;">
                                    <span style="background:${sc[0]}; color:${sc[1]}; padding:2px 8px; border-radius:10px; font-size:11px;">${g.status_label}</span>
                                    <div style="font-size:12px; color:#999; margin-top:2px;">${g.visits_count} визитов</div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }

                results.style.display = 'block';
            });
    }, 300);
});

function selectGuest(id, name, username, statusLabel, visits) {
    document.getElementById('guest-id-input').value = id;
    document.getElementById('guest-search').value = '';
    document.getElementById('guest-results').style.display = 'none';

    document.getElementById('selected-guest-info').innerHTML = `
        <div style="font-size:14px; font-weight:500;">${name}</div>
        <div style="font-size:12px; color:#999;">${username} · ${visits} визитов</div>
    `;
    document.getElementById('selected-guest').style.display = 'flex';

    // Скрываем форму нового гостя
    document.getElementById('new-guest-form').style.display = 'none';
}

function clearGuest() {
    document.getElementById('guest-id-input').value = '';
    document.getElementById('selected-guest').style.display = 'none';
    document.getElementById('selected-guest-info').innerHTML = '';
}

function toggleNewGuest() {
    const form = document.getElementById('new-guest-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    if (form.style.display === 'block') {
        clearGuest(); // убираем выбранного гостя
    }
}

// Закрываем результаты при клике вне
document.addEventListener('click', function(e) {
    if (!e.target.closest('#guest-search') && !e.target.closest('#guest-results')) {
        document.getElementById('guest-results').style.display = 'none';
    }
});
</script>
@endpush
@endsection