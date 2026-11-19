@extends('layouts.app')

@section('content')
<header class="defaultFixed statsHeader" id="myStatsHeader">
    <div class="headerTop">
        <h1>Моя статистика</h1>
        @include('partials.shift-toggle')
        <a href="{{ route('analytics.index') }}" class="headerUser"><img src="/assets/img/brands/mix.svg" alt=""></a>
    </div>
    <div class="topNav">
        <div class="filters">
            <div class="filtersRow">
                @foreach(['today' => 'Сегодня', 'week' => 'Неделя', 'month' => 'Месяц', 'year' => 'Год', 'all' => 'Всё время'] as $k => $l)
                    <a href="{{ route('my-stats', ['p' => $k]) }}" class="filtersBtn {{ $period->key === $k ? 'active' : '' }}">{{ $l }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('my-stats') }}" class="statDates myStatsDates">
                <input type="hidden" name="p" value="custom">
                <input type="date" name="from" value="{{ $period->from->toDateString() }}">
                <input type="date" name="to" value="{{ $period->to->toDateString() }}">
                <button type="submit" class="filtersBtn {{ $period->key === 'custom' ? 'active' : '' }}">Показать</button>
            </form>
        </div>
    </div>
</header>

<section class="analytics defaultFixed statsBody" id="my-stats-content">
    <p class="statPeriod">{{ $p->label }}</p>

    @if(!$me)
    <div class="analyticsCard statCard"><p class="statNote">В этом периоде нет ваших действий в заказах.</p></div>
    @else
    @if($ranks)
    <div class="analyticsCard statCard">
        <div class="hintRow"><p class="analyticsCardTitle">Моё место среди мастеров</p><button type="button" class="hintBtn" data-hint="Ваше место по каждому показателю за выбранный период среди всех, у кого были действия. Скорость и переделки считаются, только если приготовлено не меньше трёх заказов.">?</button></div>
        @foreach($ranks as $r)
        <p class="statTop"><i>{{ $r['place'] }}</i><span>{{ $r['label'] }} · из {{ $r['total'] }}</span><b>{{ rtrim(rtrim(number_format($r['value'], 1, ',', ''), '0'), ',') }} {{ $r['unit'] }}</b></p>
        @endforeach
    </div>
    @endif

    <div class="analyticsCard statCard statMaster">
        @include('stats.partials._master-card', ['m' => $me, 'first' => false])
    </div>

    <div class="analyticsCard statCard">
        <p class="analyticsCardTitle">Когда и с кем я работаю</p>
        <div class="subTabs" data-group="act">
            @foreach($actions as $a)<button type="button" class="filtersBtn subTab {{ $loop->first ? 'active' : '' }}" data-pane="{{ $a['key'] }}">{{ $a['label'] }}</button>@endforeach
        </div>
        @foreach($actions as $a)
        <div data-pane-group="act" data-pane="{{ $a['key'] }}" @if(!$loop->first) hidden @endif>
            @if($a['total'] === 0)
            <p class="statNote">В этом периоде нет таких действий.</p>
            @else
            <p class="statNote">Всего: {{ $a['total'] }}</p>
            @include('partials.freq-bars', ['what' => 'вы выполняете это действие', 'scope' => true, 'title' => 'Дни недели', 'items' => $a['days']])
            @include('partials.freq-bars', ['what' => 'вы выполняете это действие', 'scope' => true, 'title' => 'Время суток', 'items' => $a['hours']])
            @include('partials.freq-bars', ['what' => 'вы выполняете это действие', 'scope' => true, 'title' => 'По месяцам', 'items' => $a['months']])
            @include('partials.freq-bars', ['what' => 'вы выполняете это действие', 'scope' => true, 'title' => 'Сезонность', 'items' => $a['seasons']])
            @if(count($a['years']) > 1)
            @include('partials.freq-bars', ['what' => 'вы выполняете это действие', 'scope' => true, 'title' => 'По годам', 'items' => $a['years']])
            @endif
            @if($a['guests'])
            <div class="actionTabCnt">
                <div class="hintRow"><p class="analyticsCardTitle">Гости: {{ mb_strtolower($a['label']) }}</p><button type="button" class="hintBtn" data-hint="Гости, с которыми вы чаще всего делали это действие. Считаются только заказы, где указан гость.">?</button></div>
                @foreach($a['guests'] as $i => $g)<p class="statTop"><i>{{ $i + 1 }}</i><span>{{ $g['name'] }}</span><b>{{ $g['n'] }} раз</b></p>@endforeach
            </div>
            @endif
            @endif
        </div>
        @endforeach
    </div>

    @if($topMixes)
    <div class="analyticsCard statCard">
        <div class="hintRow myTopTitle"><p class="analyticsCardTitle">Миксы, которые я чаще готовил</p><button type="button" class="hintBtn" data-hint="Миксы из заказов, которые вы перевели в «Готов» за выбранный период. Число — сколько раз.">?</button></div>
        @foreach($topMixes as $i => $t)<p class="statTop"><i>{{ $i + 1 }}</i><span>{{ $t['name'] }}</span><b>{{ $t['n'] }} шт</b></p>@endforeach
    </div>
    @endif

    @if($topTobaccos)
    <div class="analyticsCard statCard">
        <div class="hintRow myTopTitle"><p class="analyticsCardTitle">Табаки, которые я чаще готовил</p><button type="button" class="hintBtn" data-hint="Табаки, заказанные отдельными позициями, в заказах, которые вы перевели в «Готов». Табаки внутри миксов здесь не считаются, они видны в топе миксов.">?</button></div>
        @foreach($topTobaccos as $i => $t)<p class="statTop"><i>{{ $i + 1 }}</i><span>{{ $t['name'] }}</span><b>{{ $t['n'] }} шт</b></p>@endforeach
    </div>
    @endif
    @endif
</section>

<script>
(function () {
    var h = document.getElementById('myStatsHeader'), b = document.getElementById('my-stats-content');
    function fit() { if (h && b) b.style.paddingTop = h.offsetHeight + 'px'; }
    fit(); window.addEventListener('resize', fit);
})();
document.addEventListener('click', function (e) {
    var b = e.target.closest('.subTab');
    if (b) {
        var row = b.closest('.subTabs'), g = row.dataset.group;
        row.querySelectorAll('.subTab').forEach(function (x) { x.classList.toggle('active', x === b); });
        document.querySelectorAll('[data-pane-group="' + g + '"]').forEach(function (p) { p.hidden = p.dataset.pane !== b.dataset.pane; });
        return;
    }
    var t = e.target.closest('.statFold'); if (!t) return;
    t.closest('.statFoldBox').classList.toggle('open');
});
</script>
@endsection
