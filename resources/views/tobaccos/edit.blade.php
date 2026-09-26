@extends('layouts.app')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1>Редактировать список табаков</h1>
        <p>Выберите табаки доступные в вашем заведении</p>
    </div>
    <a href="{{ route('tobaccos.index') }}" class="btn btn-secondary">← Назад</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('tobaccos.update-venue') }}">
        @csrf

        <div style="margin-bottom:16px; display:flex; gap:10px; align-items:center;">
            <input type="text" id="brand-search" placeholder="Поиск по бренду или названию..." style="flex:1; padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
            <button type="button" onclick="selectAll()" class="btn btn-secondary" style="font-size:13px;">Выбрать все</button>
            <button type="button" onclick="deselectAll()" class="btn btn-secondary" style="font-size:13px;">Снять все</button>
        </div>

        <div id="brands-list">
            @foreach($brands as $brand)
                <div class="brand-group" style="margin-bottom:12px; border:1px solid #eee; border-radius:8px; overflow:hidden;">

                    {{-- Заголовок бренда --}}
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#f8f8f8; cursor:pointer;" onclick="toggleBrand({{ $brand->id }})">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-weight:bold;">{{ $brand->name }}</span>
                            <span style="font-size:13px; color:#999;">{{ $brand->tobaccos->count() }} позиций</span>
                        </div>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <span style="font-size:13px; color:#4caf74;">
                                {{ $brand->tobaccos->filter(fn($t) => $t->venueTobacco?->is_active)->count() }} выбрано
                            </span>
                            <span id="arrow-{{ $brand->id }}">▼</span>
                        </div>
                    </div>

                    {{-- Список табаков бренда --}}
                    <div id="brand-{{ $brand->id }}" style="display:none; padding:8px 0;">
                        @foreach($brand->tobaccos as $tobacco)
                            <label style="display:flex; align-items:center; gap:12px; padding:10px 16px; cursor:pointer; hover:background:#f9f9f9;" class="tobacco-row">
                                <input type="checkbox" name="tobacco_ids[]" value="{{ $tobacco->id }}"
                                    {{ $tobacco->venueTobacco?->is_active ? 'checked' : '' }}
                                    style="width:16px; height:16px;">
                                <div style="flex:1;">
                                    <div style="font-size:14px;">{{ $tobacco->name }}</div>
                                    @if($tobacco->description_short)
                                        <div style="font-size:12px; color:#999;">{{ $tobacco->description_short }}</div>
                                    @endif
                                </div>
                                @php $s = ['light'=>['Лёгкий','#d4edda','#155724'], 'medium'=>['Средний','#fff3cd','#856404'], 'strong'=>['Крепкий','#f8d7da','#721c24']] @endphp
                                @if(isset($s[$tobacco->strength]))
                                    <span class="badge" style="background:{{ $s[$tobacco->strength][1] }}; color:{{ $s[$tobacco->strength][2] }}; font-size:12px;">{{ $s[$tobacco->strength][0] }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                </div>
            @endforeach
        </div>

        <div style="margin-top:20px; display:flex; gap:10px;">
            <button type="submit" class="btn btn-primary">Сохранить список</button>
            <a href="{{ route('tobaccos.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function toggleBrand(id) {
    const el = document.getElementById('brand-' + id);
    const arrow = document.getElementById('arrow-' + id);
    if (el.style.display === 'none') {
        el.style.display = 'block';
        arrow.textContent = '▲';
    } else {
        el.style.display = 'none';
        arrow.textContent = '▼';
    }
}

function selectAll() {
    document.querySelectorAll('input[name="tobacco_ids[]"]').forEach(cb => cb.checked = true);
}

function deselectAll() {
    document.querySelectorAll('input[name="tobacco_ids[]"]').forEach(cb => cb.checked = false);
}

document.getElementById('brand-search').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.brand-group').forEach(group => {
        const rows = group.querySelectorAll('.tobacco-row');
        let visible = 0;
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const match = text.includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        group.style.display = visible > 0 || group.querySelector('.brand-group > div:first-child')?.textContent.toLowerCase().includes(q) ? '' : 'none';
        if (q && visible > 0) {
            document.getElementById('brand-' + group.querySelector('[id^="brand-"]')?.id.replace('brand-', '')).style.display = 'block';
        }
    });
});
</script>
@endpush