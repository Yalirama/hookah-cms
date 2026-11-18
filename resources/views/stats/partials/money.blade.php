@use('App\Support\Fmt')
<p class="statPeriod">{{ $p->label }}</p>

@isset($d['revenue'])
@php $r = $d['revenue']; @endphp
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Выручка</p><button type="button" class="hintBtn" data-hint="Сумма цен всех позиций в закрытых заказах. Цена фиксируется в заказе при добавлении позиции. Если у позиции цена не была задана, она берётся из «Цен позиций» в настройках. Позиции без цены считаются нулём.">?</button></div>
    <div class="statGrid">
        <div class="statTile"><b>{{ Fmt::money($r['total']) }}</b><span>выручка</span><em>{!! Fmt::delta($r['delta']) !!} к прошлому периоду</em></div>
        <div class="statTile"><b>{{ Fmt::money($r['avg']) }}</b><span>средний чек</span><em>{{ $r['closed'] }} закрытых</em></div>
    </div>
    @if($r['noPrice'])<p class="statNote bad">У {{ $r['noPrice'] }} позиций в закрытых заказах нет цены — они не вошли в сумму. Задайте цены в настройках.</p>@endif
    @if($r['top'])<p class="statSub">Что принесло больше всего</p>@include('stats.partials._bars', ['rows' => collect($r['top'])->map(fn ($x) => ['label' => $x['name'], 'v' => $x['sum'], 'text' => Fmt::money($x['sum']) . ' · ' . $x['n']])->all()])@endif
</div>
@endisset

@isset($d['margin'])
@php $m = $d['margin']; @endphp
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Маржа миксов</p><button type="button" class="hintBtn" data-hint="Прибыль с одной чаши: цена микса минус себестоимость. Себестоимость — это чаша, поделённая между табаками по рецепту и умноженная на среднюю закупочную цену каждого табака. Размер чаши берётся реальный: средний по заказам этой позиции (если заказов не было — стандартный {{ $m['bowl'] }} г). В список входят миксы и табаки, у которых задана цена.">?</button></div>
    @foreach(['best' => 'Самые прибыльные', 'worst' => 'Наименее прибыльные'] as $k => $label)
    <p class="statSub">{{ $label }}</p>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Позиция</th><th>Чаша</th><th>Цена</th><th>Себест.</th><th>Маржа</th></tr></thead><tbody>
    @forelse($m[$k] as $x)<tr><td>{{ $x['name'] }}<small>{{ $x['kind'] }}</small></td><td>{{ $x['bowl'] }} г</td><td>{{ Fmt::money($x['price']) }}</td><td>{{ Fmt::money($x['cost']) }}</td><td>{{ Fmt::money($x['margin']) }} <small>{{ $x['pct'] }}%</small></td></tr>
    @empty<tr><td colspan="5">Нет позиций с ценой</td></tr>@endforelse
    </tbody></table></div>
    @endforeach
    @if($m['noPrice'])<p class="statNote">Без цены: {{ implode(', ', array_slice($m['noPrice'], 0, 8)) }}@if(count($m['noPrice']) > 8) и ещё {{ count($m['noPrice']) - 8 }}@endif.</p>@endif
</div>
@endisset

@isset($d['prices'])
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Закупочные цены</p><button type="button" class="hintBtn" data-hint="Как изменилась цена за грамм между двумя последними приходами табака. Отмечаем скачки: изменение от {{ $d['prices']['alert'] }}% (порог задаётся в настройках склада). Показаны табаки, у которых последний приход попал в выбранный период. Под каждым — вся история цены по приходам.">?</button></div>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Табак</th><th>Было</th><th>Стало</th><th>Изм.</th></tr></thead><tbody>
    @forelse($d['prices']['rows'] as $x)<tr class="{{ $x['jump'] ? 'jump' : '' }}"><td>{{ $x['name'] }}<small>{{ $x['when'] }}</small></td><td>{{ $x['from'] }}</td><td>{{ $x['to'] }}</td><td>{!! Fmt::delta($x['delta'], true) !!}</td></tr>
    <tr><td colspan="4" class="statHist">@foreach($x['history'] as $h){{ $h['d'] }}: <b>{{ $h['p'] }}</b>@if(!$loop->last) → @endif @endforeach</td></tr>
    @empty<tr><td colspan="4">В периоде не было повторных приходов</td></tr>@endforelse
    </tbody></table></div>
</div>
@endisset

@isset($d['losses'])
@php $l = $d['losses']; @endphp
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Потери</p><button type="button" class="hintBtn" data-hint="Расхождения ревизий (сколько грамм нашли не так, как ожидалось) и списания. Суммы считаются по средней закупочной цене. Минус — недостача или убыток.">?</button></div>
    <div class="statGrid">
        <div class="statTile"><b>{{ Fmt::money($l['auditMinus']) }}</b><span>недостачи по ревизиям</span><em>{{ $l['auditN'] }} расхождений</em></div>
        <div class="statTile"><b>{{ Fmt::money($l['woCost']) }}</b><span>списания</span><em>{{ $l['woN'] }} шт · {{ Fmt::num($l['woGrams'], 1) }} г</em></div>
    </div>
    @if($l['rows'])
    <div class="statScroll"><table class="statTable"><thead><tr><th>Табак</th><th>Ревизия, г</th><th>Списано, г</th><th>Сумма</th></tr></thead><tbody>
    @foreach($l['rows'] as $x)<tr><td>{{ $x['name'] }}</td><td>{{ $x['audit'] }}</td><td>{{ $x['wo'] }}</td><td>{{ Fmt::money($x['cost']) }}</td></tr>@endforeach
    </tbody></table></div>
    @endif
</div>
@endisset
