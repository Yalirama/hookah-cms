@use('App\Support\Fmt')
@use('App\Support\StatAccess')
<p class="statPeriod">{{ $p->label }}</p>

@if(StatAccess::can('guests_return'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Возвраты гостей</p><button type="button" class="hintBtn" data-hint="Берём гостей, которые пришли впервые в выбранном периоде, и смотрим, вернулись ли они. Считаются только те, у кого с первого визита уже прошло 30 (или 90) дней, иначе они просто не успели вернуться. Визиты в один день считаются за один.">?</button></div>
    <div class="statGrid">
        @foreach($d['ret'] as $w => $r)
        <div class="statTile"><b>{{ $r['pct'] !== null ? $r['pct'] . '%' : '—' }}</b><span>вернулись за {{ $w }} дней</span><em>{{ $r['back'] }} из {{ $r['eligible'] }}</em></div>
        @endforeach
        <div class="statTile"><b>{{ $d['avgBack'] !== null ? Fmt::num($d['avgBack'], 1) : '—' }}</b><span>дней до возврата</span><em>в среднем</em></div>
        <div class="statTile"><b>{{ $d['cohort'] }}</b><span>новых гостей</span><em>в периоде</em></div>
    </div>
</div>
@endif

@if(StatAccess::can('guests_growth'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Рост базы гостей</p><button type="button" class="hintBtn" data-hint="Сколько новых гостей добавилось в базу. Сначала по неделям (последние 12), потом по месяцам. Всего в базе сейчас: {{ $d['totalGuests'] }}.">?</button></div>
    <p class="statSub">По неделям (с понедельника)</p>
    @include('stats.partials._bars', ['rows' => collect($d['weeks'])->map(fn ($w) => ['label' => $w['label'], 'v' => $w['n']])->all()])
    <p class="statSub">По месяцам</p>
    @include('stats.partials._bars', ['rows' => collect($d['monthsG'])->map(fn ($w) => ['label' => $w['label'], 'v' => $w['n']])->all()])
</div>
@endif

@if(StatAccess::can('guests_top'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Топ гостей по заказам</p><button type="button" class="hintBtn" data-hint="Кто чаще всего заказывал в выбранном периоде.">?</button></div>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Гость</th><th>Заказы</th><th>Последний</th>@if($money)<th>Сумма</th>@endif</tr></thead><tbody>
    @forelse($d['top'] as $g)<tr><td><a href="#" onclick="openGuestSheet({{ $g['id'] }});return false;">{{ $g['name'] }}</a></td><td>{{ $g['orders'] }}</td><td>{{ $g['last'] }}</td>@if($money)<td>{{ Fmt::money($g['revenue']) }}</td>@endif</tr>
    @empty<tr><td colspan="4">Нет заказов с гостями</td></tr>@endforelse
    </tbody></table></div>
</div>

<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Ценные гости</p><button type="button" class="hintBtn" data-hint="Гости со статусом VIP или «Постоянный» — тот же статус, что в карточке гостя (с учётом ручной отметки). Пороги задаются в настройках: постоянный от {{ $d['thresholds'][1] }} визитов, VIP от {{ $d['thresholds'][0] }}. Считается за всё время, период не влияет.">?</button></div>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Гость</th><th>Статус</th><th>Визитов</th><th>Был</th>@if($money)<th>Сумма</th>@endif</tr></thead><tbody>
    @forelse($d['valuable'] as $g)<tr><td><a href="#" onclick="openGuestSheet({{ $g['id'] }});return false;">{{ $g['name'] }}</a></td><td>{{ $g['status'] }}</td><td>{{ $g['visits'] }}</td><td>{{ $g['ago'] !== null ? $g['ago'] . ' дн. назад' : '—' }}</td>@if($money)<td>{{ Fmt::money($g['revenue']) }}</td>@endif</tr>
    @empty<tr><td colspan="5">VIP и постоянных пока нет: нужно от {{ $d['thresholds'][1] }} визитов</td></tr>@endforelse
    </tbody></table></div>
</div>
@endif

@if(StatAccess::can('guests_risk'))
<div class="analyticsCard statCard">
    <div class="hintRow"><p class="analyticsCardTitle">Гости в зоне риска</p><button type="button" class="hintBtn" data-hint="Гости, которые ходили регулярно (3 визита и больше), но пропали: не были дольше, чем в 2,5 раза превышает их обычный интервал между визитами (и не меньше 21 дня). Стоит им написать или предложить что-то особенное.">?</button></div>
    <div class="statScroll"><table class="statTable"><thead><tr><th>Гость</th><th>Визитов</th><th>Обычно раз в</th><th>Нет уже</th></tr></thead><tbody>
    @forelse($d['risk'] as $g)<tr><td><a href="#" onclick="openGuestSheet({{ $g['id'] }});return false;">{{ $g['name'] }}</a></td><td>{{ $g['visits'] }}</td><td>{{ Fmt::num($g['avg'], 1) }} дн.</td><td>{{ $g['ago'] }} дн.</td></tr>
    @empty<tr><td colspan="4">Таких гостей нет</td></tr>@endforelse
    </tbody></table></div>
</div>
@endif
