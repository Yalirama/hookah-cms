@php $modes = ['admin' => 'Только админ', 'masters' => 'Все мастера', 'users' => 'Выбранные']; @endphp
<div class="actionTabCnt">
    <p class="statAccessLead">Админ видит всю статистику всегда. Здесь выберите, кому ещё показывать каждый блок: всем мастерам или только выбранным.</p>
</div>
@foreach(\App\Support\StatAccess::GROUPS as $gk => $gl)
@php $keys = collect(\App\Support\StatAccess::STATS)->filter(fn ($v) => $v[0] === $gk); @endphp
@if($keys->isNotEmpty())
<div class="actionTabCnt statAccessGroup">
    <p class="analyticsCardTitle">{{ $gl }}</p>
    @foreach($keys as $key => $def)
    @php $r = $rules[$key]; @endphp
    <div class="statAccessRow" data-key="{{ $key }}" data-mode="{{ $r['mode'] }}">
        <p class="statAccessName">{{ $def[1] }}</p>
        <div class="statAccessSeg">
            @foreach($modes as $mk => $ml)
            <button type="button" class="{{ $r['mode'] === $mk ? 'active' : '' }}" data-mode="{{ $mk }}" onclick="statAccessMode(this)">{{ $ml }}</button>
            @endforeach
        </div>
        <div class="statAccessUsers" style="{{ $r['mode'] === 'users' ? '' : 'display:none' }}">
            @foreach($masters as $m)
            <button type="button" class="statAccessUser {{ in_array($m->id, $r['users']) ? 'active' : '' }}" data-user="{{ $m->id }}" onclick="this.classList.toggle('active')">{{ trim($m->name . ' ' . $m->surname) }}</button>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endif
@endforeach
<p class="formErrorBox" id="stat-access-error" style="display:none"></p>
<div class="actionTabActions"><div class="actionTabActionsButtons"><button type="button" class="actionTabActionsButton --buttonAccent" onclick="statAccessSave()">Сохранить доступ</button></div></div>
