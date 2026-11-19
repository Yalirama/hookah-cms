@use('App\Support\Fmt')
<p class="statPeriod">{{ $p->label }}</p>

@if($d['tops'])
@php
    $topCats = ['Объём' => ['prepared', 'created', 'closed'], 'Скорость' => ['avgNew'], 'Переделки' => ['redoPct'], 'Контроль' => ['rollbacks', 'lost', 'alt']];
    $topGroups = [];
    foreach ($topCats as $cat => $keys) {
        $g = array_values(array_filter($d['tops'], fn ($t) => in_array($t['key'] ?? '', $keys, true)));
        if ($g) $topGroups[$cat] = $g;
    }
@endphp
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Топы</p><button type="button" class="hintBtn" data-hint="Лидеры периода по разным показателям. Рейтинги по скорости и переделкам считаются, только если мастер приготовил не меньше трёх заказов, иначе цифры случайны.">?</button></div>
    <div class="subTabs" data-group="tops">
        @foreach($topGroups as $cat => $g)<button type="button" class="filtersBtn subTab {{ $loop->first ? 'active' : '' }}" data-pane="{{ $loop->index }}">{{ $cat }}</button>@endforeach
    </div>
    @foreach($topGroups as $cat => $g)
    <div data-pane-group="tops" data-pane="{{ $loop->index }}" @if(!$loop->first) hidden @endif>
    @foreach($g as $t)
    <p class="statSub">{{ $t['label'] }}</p>
    @foreach($t['rows'] as $i => $r)<p class="statTop"><i>{{ $i + 1 }}</i><span>{{ $r['name'] }}</span><b>{{ rtrim(rtrim(number_format($r['v'], 1, ',', ''), '0'), ',') }} {{ $t['unit'] }}@if(($r['n'] ?? null) !== null) · {{ $r['n'] }} шт@endif</b></p>@endforeach
    @endforeach
    </div>
    @endforeach
</div>
@endif

@if(count($d['rows']) > 1)
<div class="subTabs statMasterTabs" data-group="master">
    @foreach($d['rows'] as $m)<button type="button" class="filtersBtn subTab {{ $loop->first ? 'active' : '' }}" data-pane="{{ $m['id'] }}">{{ $m['name'] }}</button>@endforeach
</div>
@endif
@forelse($d['rows'] as $m)
<div class="analyticsCard statCard statMaster" data-pane-group="master" data-pane="{{ $m['id'] }}" @if(!$loop->first) hidden @endif>
@include('stats.partials._master-card', ['m' => $m, 'first' => $loop->first])
</div>
@empty
<div class="analyticsCard statCard"><p class="statNote">В этом периоде нет действий мастеров.</p></div>
@endforelse
