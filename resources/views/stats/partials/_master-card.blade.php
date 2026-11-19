@use('App\Support\Fmt')
    <div class="hintRow"><p class="analyticsCardTitle">{{ $m['name'] }}</p>@if($first ?? false)<button type="button" class="hintBtn" data-hint="Всё считается по действиям из истории заказов в выбранном периоде. «Взял» — перевёл заказ из «Нового» в «В работе»; «Приготовил» — из «В работе» в «Готов»; «Отнёс гостю» — из «Готов» в «У гостя». «Исход» — чем сейчас закончились заказы, которые он приготовил. «Откат» — возврат заказа на прошлый статус. «Забрали / передали» — смена мастера в заказе. Время смен записывается с начала ведения истории, раньше его нет.">?</button>@endif</div>
    <div class="statGrid statGrid3">
        <div class="statTile"><b>{{ $m['created'] }}</b><span>создал</span></div>
        <div class="statTile"><b>{{ $m['took'] }}</b><span>взял в работу</span></div>
        <div class="statTile"><b>{{ $m['prepared'] }}</b><span>приготовил</span></div>
        <div class="statTile"><b>{{ $m['delivered'] }}</b><span>отнёс гостю</span></div>
        <div class="statTile"><b>{{ $m['closed'] }}</b><span>закрыл</span></div>
        <div class="statTile"><b>{{ $m['serviced'] }}</b><span>обслужил</span></div>
    </div>
    <p class="statSub">Исход приготовленных заказов</p>
    <p class="statLine">Закрыто: <b>{{ $m['outcome']['closed'] }}</b> · Отменено: <b>{{ $m['outcome']['cancelled'] }}</b> · На переделке: <b>{{ $m['outcome']['redo'] }}</b>@if($m['outcome']['other']) · В процессе: <b>{{ $m['outcome']['other'] }}</b>@endif</p>
    <p class="statSub">Скорость и нагрузка</p>
    <p class="statLine">От «Нового» до «Готов»: <b>{{ $m['avgNew'] !== null ? Fmt::mins($m['avgNew']) : '—' }}</b> · Само приготовление: <b>{{ $m['avgCook'] !== null ? Fmt::mins($m['avgCook']) : '—' }}</b></p>
    <p class="statLine">Переделок: <b>{{ $m['redoPct'] !== null ? $m['redoPct'] . '% · ' . $m['outcome']['redo'] . ' шт' : '—' }}</b> · Заменял табак на альтернативу: <b>{{ $m['alt'] }}</b></p>
    <p class="statLine">Часов на смене: <b>{{ $m['shiftHours'] }}</b>@if($m['perHour'] !== null) · Приготовил в час: <b>{{ $m['perHour'] }}</b>@endif</p>
    @if($m['dayparts'])<p class="statLine">По времени суток: @foreach(['Утро', 'День', 'Вечер', 'Ночь'] as $dp)@if(!empty($m['dayparts'][$dp])){{ $dp }} <b>{{ $m['dayparts'][$dp] }}</b> @endif @endforeach</p>@endif
    <p class="statSub">Контроль</p>
    <p class="statLine">Откатов: <b>{{ $m['rollbacks'] }}</b> · Сменил мастера в заказах: <b>{{ $m['madeChanges'] }}</b> · У него забрали: <b>{{ $m['lost'] }}</b> · Ему передали: <b>{{ $m['gained'] }}</b></p>
    @if($m['shifts'])
    <div class="statFoldBox">
        <button type="button" class="statFold"><span>Смены: время и сколько приготовил</span><b>{{ count($m['shifts']) }}</b></button>
        <div class="statFoldBody">
            @foreach($m['shifts'] as $sh)<p>{{ $sh['from'] }} – {{ $sh['to'] }} · {{ $sh['hours'] }} ч<small>Приготовил за смену: {{ $sh['prepared'] }}</small></p>@endforeach
        </div>
    </div>
    @endif
    @if($m['events'])
    <div class="statFoldBox">
        <button type="button" class="statFold"><span>Откаты и смены мастера: где и когда</span><b>{{ count($m['events']) }}</b></button>
        <div class="statFoldBody">
            @foreach(array_slice($m['events'], -80) as $e)<p><b>{{ $e['type'] }}</b> · заказ №{{ $e['order'] }}@if($e['table']) · {{ $e['table'] }}@endif · {{ $e['when'] }}@if($e['note'])<small>{{ $e['note'] }}</small>@endif</p>@endforeach
        </div>
    </div>
    @endif
