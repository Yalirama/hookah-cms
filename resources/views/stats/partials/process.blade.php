@use('App\Support\Fmt')
@use('App\Support\StatAccess')
<p class="statPeriod">{{ $p->label }}</p>

@if(StatAccess::can('process_time'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Где застревают заказы</p><button type="button" class="hintBtn" data-hint="Сколько заказ в среднем проводит в каждом статусе: «Новый» — ждёт мастера, «В работе» — готовится, «Готов» — ждёт, пока его отнесут, «Подан» — гость курит. Если какой-то статус слишком долгий, там узкое место. Интервалы дольше суток не учитываются.">?</button></div>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Статус</th><th>В среднем</th><th>Максимум</th><th>Раз</th></tr></thead><tbody>
    @forelse($d['statusRows'] as $r)<tr><td>{{ $r['label'] }}</td><td>{{ Fmt::mins($r['avgMin']) }}</td><td>{{ Fmt::mins($r['maxMin']) }}</td><td>{{ $r['n'] }}</td></tr>
    @empty<tr><td colspan="4">Нет данных по статусам</td></tr>@endforelse
    </tbody></table></div>
    @if($d['long'])
    <p class="statSub">Самые долгие остановки</p>
    @foreach($d['long'] as $r)<p class="statQuote">Заказ №{{ $r['order'] }} — «{{ $r['status'] }}»: <b>{{ Fmt::mins($r['min']) }}</b></p>@endforeach
    @endif
</div>

<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Скорость по времени суток</p><button type="button" class="hintBtn" data-hint="Сколько в среднем проходит от создания заказа до статуса «Готов» — в зависимости от того, во сколько заказ создан. Утро 6–12, день 12–18, вечер 18–24, ночь 0–6. Скорость каждого мастера смотрите на вкладке «Мастера».">?</button></div>
    @if($d['speed'])@include('stats.partials._bars', ['rows' => collect($d['speed'])->map(fn ($r) => ['label' => $r['label'], 'v' => $r['avgMin'], 'text' => Fmt::mins($r['avgMin']) . ' · ' . $r['n']])->all()])@else<p class="statNote">Нет данных</p>@endif
</div>
@endif

@if(StatAccess::can('process_cancel'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Отмены</p><button type="button" class="hintBtn" data-hint="Какая доля заказов отменена, на каком этапе это случилось, кто отменял и по какой причине. Причины берутся из комментария при отмене и группируются по смыслу. Если комментарий не оставили, пишем «Причина не указана».">?</button></div>
    <div class="statGrid">
        <div class="statTile"><b>{{ $d['cancel']['pct'] }}%</b><span>отменено</span><em>{{ $d['cancel']['n'] }} из {{ $d['cancel']['total'] }}</em></div>
    </div>
    @foreach(['from' => 'На каком этапе', 'who' => 'Кто отменял', 'reasons' => 'Причины'] as $k => $label)
    @if($d['cancel'][$k])<p class="statSub">{{ $label }}</p>@include('stats.partials._bars', ['rows' => collect($d['cancel'][$k])->map(fn ($n, $x) => ['label' => $x, 'v' => $n])->values()->all()])@endif
    @endforeach
</div>
@endif
