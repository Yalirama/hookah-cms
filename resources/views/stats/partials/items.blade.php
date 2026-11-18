@use('App\Support\Fmt')
@use('App\Support\StatAccess')
<p class="statPeriod">{{ $p->label }}</p>

@if(StatAccess::can('items_trend'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Рост и падение позиций</p><button type="button" class="hintBtn" data-hint="Сколько раз заказывали позицию (микс или табак) в выбранном периоде и в прошлом. «Было» — предыдущий такой же период (для месяца — предыдущий месяц). «Год назад» — тот же период в прошлом году. Показаны только позиции, которых набралось минимум два заказа.">?</button></div>
    @if(!$d['hasPrev'])<p class="statNote">Для периода «Всё» сравнивать не с чем — выберите месяц, неделю или год.</p>@endif
    <p class="statSub">Растут</p>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Позиция</th><th>Сейчас</th><th>Было</th><th>Год назад</th></tr></thead><tbody>
    @forelse($d['grow'] as $r)<tr><td>{{ $r['name'] }}<small>{{ $r['kind'] }}</small></td><td>{{ $r['cur'] }}</td><td>{{ $r['prev'] }} <span class="statDelta up">+{{ $r['delta'] }}</span></td><td>{{ $r['yoy'] }}</td></tr>
    @empty<tr><td colspan="4">Нет растущих позиций</td></tr>@endforelse
    </tbody></table></div>
    <p class="statSub">Падают</p>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Позиция</th><th>Сейчас</th><th>Было</th><th>Год назад</th></tr></thead><tbody>
    @forelse($d['fall'] as $r)<tr><td>{{ $r['name'] }}<small>{{ $r['kind'] }}</small></td><td>{{ $r['cur'] }}</td><td>{{ $r['prev'] }} <span class="statDelta down">{{ $r['delta'] }}</span></td><td>{{ $r['yoy'] }}</td></tr>
    @empty<tr><td colspan="4">Нет падающих позиций</td></tr>@endforelse
    </tbody></table></div>
</div>
@endif

@if(StatAccess::can('items_pairs'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">С чем заказывают табак и что переделывают</p><button type="button" class="hintBtn" data-hint="Для самых частых табаков: с какими табаками их забивают вместе (внутри миксов и в одном заказе) и какая доля таких заказов закончилась переделкой. «Хуже всего» — сочетания, которые чаще остальных заканчивались переделкой (от двух заказов).">?</button></div>
    @forelse($d['pairs'] as $r)
    <div class="statPair">
        <p><b>{{ $r['name'] }}</b> <span>{{ $r['n'] }} зак. · переделок {{ $r['redoPct'] }}%</span></p>
        @if($r['top'])<p class="statPairLine">Чаще всего с: @foreach($r['top'] as $x){{ $x['name'] }} ({{ $x['n'] }}, переделок {{ $x['redoPct'] }}%)@if(!$loop->last); @endif @endforeach</p>@endif
        @if($r['worst'])<p class="statPairLine bad">Хуже всего с: @foreach($r['worst'] as $x){{ $x['name'] }} ({{ $x['redoPct'] }}%)@if(!$loop->last); @endif @endforeach</p>@endif
    </div>
    @empty<p class="statNote">В периоде нет заказов.</p>@endforelse
    @if($d['mixPairs'])
    <p class="statSub">Какие миксы заказывают вместе в одном заказе</p>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Пара миксов</th><th>Раз</th><th>Переделок</th></tr></thead><tbody>
    @foreach($d['mixPairs'] as $x)<tr><td>{{ $x['a'] }} + {{ $x['b'] }}</td><td>{{ $x['n'] }}</td><td>{{ $x['redoPct'] }}%</td></tr>@endforeach
    </tbody></table></div>
    @endif
</div>
@endif

@if(StatAccess::can('items_dead'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Мёртвые позиции</p><button type="button" class="hintBtn" data-hint="Активные миксы и табаки, которые давно не заказывали. Табак считается заказанным, даже если его брали внутри микса. «Ни разу» — позицию не заказывали вообще. Подумайте, убрать позицию с витрины, сделать акцию или заменить.">?</button></div>
    @foreach(['90' => '90 дней и больше', '60' => 'от 60 до 90 дней', '30' => 'от 30 до 60 дней'] as $w => $label)
    <div class="statFoldBox">
        <button type="button" class="statFold"><span>{{ $label }}</span><b>{{ count($d['dead'][$w]) }}</b></button>
        <div class="statFoldBody">
            @forelse($d['dead'][$w] as $r)<p>{{ $r['name'] }} <small>{{ $r['kind'] }} · {{ $r['days'] === null ? 'ни разу' : $r['days'] . ' дн.' }}</small></p>@empty<p class="statNote">Таких нет</p>@endforelse
        </div>
    </div>
    @endforeach
</div>
@endif

@if(StatAccess::can('items_seasons'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Вкусы по сезонам</p><button type="button" class="hintBtn" data-hint="Какие вкусовые теги чаще всего попадали в заказы в каждое время года. Считается за всё время по составу заказанных табаков и миксов.">?</button></div>
    @foreach($d['season'] as $s => $tags)
    <p class="statSub">{{ $s }}</p>
    @if($tags)@include('stats.partials._bars', ['rows' => collect($tags)->map(fn ($n, $k) => ['label' => $k, 'v' => $n])->values()->all()])@else<p class="statNote">Пока нет данных</p>@endif
    @endforeach
</div>
@endif

@if(StatAccess::can('items_comments'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">О чём просят в комментариях</p><button type="button" class="hintBtn" data-hint="Комментарии гостей к заказам разбираются по смыслу: «арбуз», «арбузный», «побольше арбуза» — всё это одна просьба. Таблица показывает, как просьбы распределены по сезонам за всё время, и сколько раз их писали в выбранном периоде.">?</button></div>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Просьба</th><th>В периоде</th><th>Зима</th><th>Весна</th><th>Лето</th><th>Осень</th></tr></thead><tbody>
    @forelse($d['commentRows'] as $r)<tr><td>{{ $r['name'] }}</td><td>{{ $r['period'] }}</td><td>{{ $r['Зима'] }}</td><td>{{ $r['Весна'] }}</td><td>{{ $r['Лето'] }}</td><td>{{ $r['Осень'] }}</td></tr>
    @empty<tr><td colspan="6">Комментариев пока нет</td></tr>@endforelse
    </tbody></table></div>
    <p class="statSub">По неделям (последние 8)</p>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Неделя</th><th>Просьб</th><th>Чаще всего</th></tr></thead><tbody>
    @foreach($d['weekRows'] as $w)<tr><td>{{ $w['label'] }}</td><td>{{ $w['n'] }}</td><td>@forelse($w['top'] as $k => $n){{ $k }} ({{ $n }})@if(!$loop->last), @endif @empty — @endforelse</td></tr>@endforeach
    </tbody></table></div>
    @if($d['recent'])
    <p class="statSub">Последние в периоде</p>
    @foreach($d['recent'] as $r)<p class="statQuote">«{{ $r['text'] }}» <small>{{ $r['when'] }}@if($r['tags']) · {{ implode(', ', $r['tags']) }}@endif</small></p>@endforeach
    @endif
</div>
@endif
