@use('App\Support\Fmt')
@use('App\Support\StatAccess')
<p class="statPeriod">{{ $p->label }}</p>

@if(StatAccess::can('time_months'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Динамика по месяцам</p><button type="button" class="hintBtn" data-hint="Последние 12 месяцев: сколько было заказов и разных гостей{{ $money ? ' и какая выручка' : '' }}. Рядом — изменение к тому же месяцу прошлого года. Отменённые заказы не считаются.">?</button></div>
    <div class="statScroll">
    <table class="statTable">
        <thead><tr><th>Месяц</th><th>Заказы</th><th>к г.</th><th>Гости</th><th>к г.</th>@if($money)<th>Выручка</th><th>к г.</th>@endif</tr></thead>
        <tbody>
        @foreach($d['months'] as $m)
        <tr><td>{{ $m['label'] }}</td><td>{{ $m['cur']['orders'] }}</td><td>{!! Fmt::delta($m['dOrders']) !!}</td><td>{{ $m['cur']['guests'] }}</td><td>{!! Fmt::delta($m['dGuests']) !!}</td>
            @if($money)<td>{{ Fmt::money($m['cur']['revenue']) }}</td><td>{!! Fmt::delta($m['dRevenue']) !!}</td>@endif</tr>
        @endforeach
        </tbody>
    </table>
    </div>
    <p class="statNote">«к г.» — к тому же месяцу год назад. Если там было пусто, стоит прочерк.</p>
</div>

<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Сезоны</p><button type="button" class="hintBtn" data-hint="Зима — декабрь–февраль, весна — март–май, лето — июнь–август, осень — сентябрь–ноябрь. Считаем за всё время. «В месяц» — среднее число заказов за один месяц этого сезона, так сезоны можно честно сравнивать, даже если данных по ним разное количество.">?</button></div>
    @include('stats.partials._bars', ['rows' => collect($d['seasons'])->map(fn ($s) => ['label' => $s['label'], 'v' => $s['perMonth'], 'text' => $s['perMonth'] . ' в мес.'])->all()])
    <div class="statScroll">
    <table class="statTable">
        <thead><tr><th>Сезон</th><th>Заказы</th><th>Месяцев</th><th>Гости</th>@if($money)<th>Выручка</th>@endif</tr></thead>
        <tbody>@foreach($d['seasons'] as $s)<tr><td>{{ $s['label'] }}</td><td>{{ $s['orders'] }}</td><td>{{ $s['months'] }}</td><td>{{ $s['guests'] }}</td>@if($money)<td>{{ Fmt::money($s['revenue']) }}</td>@endif</tr>@endforeach</tbody>
    </table>
    </div>
</div>
@endif

@if(StatAccess::can('time_monthdays'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">По дням месяца</p><button type="button" class="hintBtn" data-hint="Сколько заказов приходится на каждое число месяца в выбранном периоде. Помогает понять, когда в месяце идут пики: например, после зарплаты или в начале месяца. Чем длиннее период, тем точнее картина.">?</button></div>
    @include('stats.partials._bars', ['rows' => collect($d['days'])->map(fn ($v, $k) => ['label' => $k, 'v' => $v['orders'], 'text' => $v['orders'] . ($money && $v['revenue'] ? ' · ' . Fmt::money($v['revenue']) : '')])->values()->all()])
</div>
@endif

@if(StatAccess::can('time_hours'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">По часам</p><button type="button" class="hintBtn" data-hint="В какой час гости садятся за стол: берётся время создания заказа. Видно, когда нужно больше мастеров, а когда можно отдохнуть.">?</button></div>
    @include('stats.partials._bars', ['rows' => collect($d['hours'])->map(fn ($v, $k) => ['label' => sprintf('%02d:00', $k), 'v' => $v['orders'], 'text' => $v['orders'] . ($money && $v['revenue'] ? ' · ' . Fmt::money($v['revenue']) : '')])->values()->all()])
</div>
@endif

@if(StatAccess::can('time_weekend'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Выходные и будни</p><button type="button" class="hintBtn" data-hint="Выходные — суббота и воскресенье, будни — с понедельника по пятницу. «В день» — среднее число заказов за один такой день: считаем только дни, в которые были заказы.">?</button></div>
    <div class="statScroll">
    <table class="statTable">
        <thead><tr><th></th><th>Заказы</th><th>Дней</th><th>В день</th>@if($money)<th>Выручка в день</th>@endif</tr></thead>
        <tbody>@foreach($d['wkRows'] as $r)<tr><td>{{ $r['label'] }}</td><td>{{ $r['orders'] }}</td><td>{{ $r['days'] }}</td><td>{{ $r['avg'] }}</td>@if($money)<td>{{ Fmt::money($r['avgRevenue']) }}</td>@endif</tr>@endforeach</tbody>
    </table>
    </div>
    @include('stats.partials._bars', ['rows' => collect($d['dowRows'])->map(fn ($r) => ['label' => $r['label'], 'v' => $r['avg'], 'text' => $r['avg'] . ' в день'])->all()])
</div>
@endif
