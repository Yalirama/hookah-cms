@use('App\Support\Fmt')
@use('App\Support\StatAccess')
<p class="statPeriod">{{ $p->label }}</p>

@if(StatAccess::can('tables_load'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Загрузка столов</p><button type="button" class="hintBtn" data-hint="Сколько часов стол был занят. Если за столом одновременно сидели несколько заказов, время считается один раз (пересекающиеся промежутки объединяются). «Длит.» — сколько в среднем занят стол на один заказ. Если гости пересаживались, время делится между столами. Один отрезок за столом учитывается не дольше 12 часов, чтобы забытые открытые заказы не искажали цифры.">?</button></div>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Стол</th><th>Тип</th><th>Заказы</th><th>Часов</th><th>Длит.</th></tr></thead><tbody>
    @forelse($d['load'] as $r)<tr><td>{{ $r['name'] }}</td><td>{{ $r['type'] }}</td><td>{{ $r['orders'] }}</td><td>{{ Fmt::num($r['hours'], 1) }}</td><td>{{ Fmt::mins($r['avgMin']) }}</td></tr>
    @empty<tr><td colspan="5">Нет заказов в периоде</td></tr>@endforelse
    </tbody></table></div>
    @if($d['types'])
    <p class="statSub">Средняя длительность закрытого заказа по типу стола</p>
    @include('stats.partials._bars', ['rows' => collect($d['types'])->map(fn ($r) => ['label' => $r['name'], 'v' => $r['avgMin'], 'text' => Fmt::mins($r['avgMin']) . ' · ' . $r['orders']])->all()])
    @endif
</div>
@endif

@if(StatAccess::can('tables_moves'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Рейтинг столов по пересадкам</p><button type="button" class="hintBtn" data-hint="Доля заказов, из которых гости пересаживались с этого стола. Чем выше процент, тем чаще стол не устраивает. Причины читаются из комментариев при смене стола и склеиваются по смыслу, даже если написаны по-разному или с опечатками («душно», «жарко», «духота» — одна причина).">?</button></div>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Стол</th><th>Пересели</th><th>Доля</th><th>Главные причины</th></tr></thead><tbody>
    @forelse($d['rating'] as $r)<tr><td>{{ $r['name'] }}</td><td>{{ $r['moved'] }} из {{ $r['total'] }}</td><td>{{ $r['pct'] }}%</td><td>@foreach($r['reasons'] as $k => $n){{ $k }} ({{ $n }})@if(!$loop->last), @endif @endforeach</td></tr>
    @empty<tr><td colspan="4">Пересадок не было</td></tr>@endforelse
    </tbody></table></div>
    @if($d['reasons'])
    <p class="statSub">Причины пересадок — всего</p>
    @include('stats.partials._bars', ['rows' => collect($d['reasons'])->map(fn ($n, $k) => ['label' => $k, 'v' => $n])->values()->all()])
    @endif
</div>
@endif

@if(StatAccess::can('tables_flow'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Куда пересаживаются</p><button type="button" class="hintBtn" data-hint="Самые частые направления: с какого стола на какой переходят гости и почему. Помогает понять, какой стол гости считают лучше.">?</button></div>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Откуда</th><th>Куда</th><th>Раз</th><th>Причина</th></tr></thead><tbody>
    @forelse($d['flow'] as $r)<tr><td>{{ $r['from'] }}</td><td>{{ $r['to'] }}</td><td>{{ $r['n'] }}</td><td>{{ $r['reason'] }}</td></tr>
    @empty<tr><td colspan="4">Пересадок не было</td></tr>@endforelse
    </tbody></table></div>
</div>
@endif
