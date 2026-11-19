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

    @if($topMixes)
    <div class="analyticsCard statCard">
        <p class="analyticsCardTitle">Миксы, которые я чаще готовил</p>
        @foreach($topMixes as $i => $t)<p class="statTop"><i>{{ $i + 1 }}</i><span>{{ $t['name'] }}</span><b>{{ $t['n'] }} шт</b></p>@endforeach
    </div>
    @endif

    @if($topTobaccos)
    <div class="analyticsCard statCard">
        <p class="analyticsCardTitle">Табаки, которые я чаще готовил</p>
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
    var t = e.target.closest('.statFold'); if (!t) return;
    t.closest('.statFoldBox').classList.toggle('open');
});
</script>
@endsection
