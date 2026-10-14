@php
    $initials = mb_strtoupper(collect(explode(' ', trim($guest->name)))->map(fn($w) => mb_substr($w, 0, 1))->join(''), 'UTF-8');
    $sinceLabel = 'С нами с ' . $guest->created_at->locale('ru')->translatedFormat('F Y');
    $statusClassMap = ['new' => '--questNew', 'regular' => '--questRegular', 'permanent' => '--questPermanent', 'vip' => '--questVip'];
    $slotIcons = ['morning' => '/assets/img/day-icon.svg', 'day' => '/assets/img/day-icon.svg', 'evening' => '/assets/img/evening-icon.svg', 'night' => '/assets/img/night-icon.svg'];
    $dayOrder = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
    $positiveLabels = $guest->labelsByType('positive');
    $neutralLabels = $guest->labelsByType('neutral');
    $negativeLabels = $guest->labelsByType('negative');
@endphp

<div class="viewProductSameTabHead">
    <div class="viewProductSameTabHeadUserImg"><span>{{ $initials }}</span></div>
    <div class="viewProductSameTabHeadBody">
        <p class="viewProductSameTabHeadTitle">{{ $guest->name }}</p>
        <p class="viewProductSameTabHeadText">{{ '@' . $guest->username }}</p>

        <div class="actionTabCntRow0vi43">
            @if($guest->phone)<span>{{ $guest->phone }}</span>@endif
            @if($guest->email)<span>{{ $guest->email }}</span>@endif
        </div>

        <div class="actionTabCntRow9n3d2">
            <div class="ordersCardUserInfoStatus {{ $statusClassMap[$guest->status] ?? '' }}"><span>{{ $guest->status_label }}</span></div>
            <div class="ordersCardUserInfoStatus --questNew"><span>{{ $sinceLabel }}</span></div>
        </div>
    </div>
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">ОБЩАЯ СТАТИСТИКА</p>
    <div class="actionTabCntRow8n37d">
        <div class="item"><p>{{ $visitsCount }}</p><span>Визиты</span></div>
        <div class="item"><p>{{ $avgSession ? gmdate('H\ч i\м', round($avgSession * 60)) : '—' }}</p><span>Средняя сессия</span></div>
        <div class="item"><p>{{ $avgServiced }}</p><span>Обслуживаний за сессию</span></div>
        <div class="item"><p>{{ $totalOrders }}</p><span>Всего заказов</span></div>
        <div class="item"><p>{{ $lastVisitDays ?? '—' }}</p><span>Дней с последнего</span></div>
        <div class="item"><p>{{ $redoCount }}</p><span>Запросов на переделку</span></div>
    </div>
</div>

<div class="actionTabCnt">
    <div class="actionTabCntRow3od21">
        <p class="analyticsCardTitle">ЯРЛЫКИ ПРЕДПОЧТЕНИЙ</p>
        <button type="button" onclick="openGuestLabelsSheet({{ $guest->id }}, '{{ addslashes($guest->name) }}')">
            <img src="/assets/img/edit-icon-gold.svg" alt="">
            <span>Редактировать</span>
        </button>
    </div>

    <div class="actionTabCntRow2nvj3">
        <div class="row --positive">
            <img src="/assets/img/positive-icon.svg" alt="">
            <div class="body">
                @forelse($positiveLabels as $l) <div class="item"><span>{{ $l->label->name }}</span></div> @empty <span style="color:var(--info);">—</span> @endforelse
            </div>
        </div>
        <div class="row --neitral">
            <img src="/assets/img/neitral-icon.svg" alt="">
            <div class="body">
                @forelse($neutralLabels as $l) <div class="item"><span>{{ $l->label->name }}</span></div> @empty <span style="color:var(--info);">—</span> @endforelse
            </div>
        </div>
        <div class="row --negative">
            <img src="/assets/img/negative-icon.svg" alt="">
            <div class="body">
                @forelse($negativeLabels as $l) <div class="item"><span>{{ $l->label->name }}</span></div> @empty <span style="color:var(--info);">—</span> @endforelse
            </div>
        </div>
    </div>
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">КОГДА ПРИХОДИТ</p>
    <div class="actionTabCntRow6bs7c">
        @foreach($timeSlots as $key => $slot)
        <div class="item">
            <img src="{{ $slotIcons[$key] ?? '/assets/img/day-icon.svg' }}" alt="">
            <p>{{ $slot['label'] }}</p>
            <span>x{{ $slotCounts[$key] ?? 0 }} • {{ round(($slotCounts[$key] ?? 0) / $maxSlot * 100) }}%</span>
        </div>
        @endforeach
    </div>
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">ДНИ НЕДЕЛИ</p>
    <div class="actionTabCntRow1ys7s">
        @foreach($dayOrder as $day)
        @php $c = $dayCounts[$day] ?? 0; $isActive = $c > 0 && $c >= $maxDay * 0.5; @endphp
        <div class="item {{ $isActive ? 'active' : '' }}"><span></span><p>{{ $day }}</p></div>
        @endforeach
    </div>
</div>

@if($topTobaccos->isNotEmpty())
@include('guests.partials.top-list', ['title' => 'ТОП ТАБАКОВ', 'items' => $topTobaccos, 'labelFn' => fn($i) => trim(($i->itemable?->brand?->name ?? '') . ' ' . ($i->itemable?->name ?? '—'))])
@endif

@if($topBrands->isNotEmpty())
@include('guests.partials.top-list', ['title' => 'ТОП БРЕНДОВ', 'items' => $topBrands, 'labelFn' => fn($i) => $i['brand']?->name ?? '—', 'countFn' => fn($i) => $i['total']])
@endif

@if($topMixes->isNotEmpty())
@include('guests.partials.top-list', ['title' => 'ТОП МИКСЫ', 'items' => $topMixes, 'labelFn' => fn($i) => $i->itemable?->name ?? '—'])
@endif

@if($topMasters->isNotEmpty())
@include('guests.partials.top-list', ['title' => 'ТОП МАСТЕРА', 'items' => $topMasters, 'labelFn' => fn($i) => $i->creator?->name ?? '—'])
@endif

@if($favTables->isNotEmpty())
<div class="actionTabCnt">
    <p class="analyticsCardTitle">ЛЮБИМЫЕ МЕСТА</p>
    <div class="actionTabCntRow6bs7c actionTabCntRow8b2j1">
        @foreach($favTables->take(4) as $t)
        <div class="item">
            <p>{{ $t->table?->name ?? '—' }}</p>
            <span>x{{ $t->total }} • {{ $totalTableVisits > 0 ? round($t->total / $totalTableVisits * 100) : 0 }}%</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="actionTabCnt" id="guestVisitHistoryShort">
    <p class="analyticsCardTitle">ИСТОРИЯ ВИЗИТОВ</p>
    <div class="actionTabCntRow8dn21" id="guestVisitHistoryShortRows">
        <p style="color:var(--info); text-align:center; padding:10px;">Загрузка...</p>
    </div>
    <div class="actionTabViewOrderCard --actionTabViewOrderCard9k2sn">
        <div class="actionTabViewOrderCardTop">
            <button type="button" onclick="openGuestVisitHistorySheet({{ $guest->id }}, '{{ addslashes($guest->name) }}')">
                <img src="/assets/img/show-icon.svg" alt="">
                <span>Посмотреть все {{ $totalOrders }}</span>
            </button>
        </div>
    </div>
</div>

<div class="actionTabCnt guestNotesBox" data-user="{{ auth()->user()->name }}">
    <p class="analyticsCardTitle">Заметки</p>
    <form method="POST" action="{{ route('guests.notes.store', $guest) }}" class="actionTabCntForm8s2id guestNoteForm">
        @csrf
        <div class="actionTabCntForm8s2idRow">
            <textarea name="note" placeholder="Добавить заметку" rows="3"></textarea>
            <div class="actionTabCntForm8s2idRowAction">
                <button type="submit"><img src="/assets/img/edit-icon-gold.svg" alt=""><span>Добавить</span></button>
            </div>
        </div>
    </form>

    @foreach($guest->notes->whereNull('deleted_at') as $note)
    <div class="actionTabCntForm8s2id guestNoteItem">
        <div class="actionTabCntForm8s2idRow">
            <textarea readonly rows="3">{{ $note->note }}</textarea>
            <div class="actionTabCntForm8s2idRowAction">
                <div class="actionTabCntForm8s2idRowActionItem">
                    <div class="item"><img src="/assets/img/master-done.svg" alt=""><p>{{ $note->user?->name ?? '—' }}</p></div>
                    <div class="item"><img src="/assets/img/time-icon.svg" alt=""><p>{{ $note->created_at->format('d.m.Y H:i') }}</p></div>
                </div>
                @if($note->user_id === auth()->id() || auth()->user()->hasRole('admin'))
                <form method="POST" action="{{ route('guests.notes.destroy', [$guest, $note]) }}" class="guestNoteDeleteForm">
                    @csrf @method('DELETE')
                    <button type="submit"><img src="/assets/img/close-icon.svg" alt=""><span>Удалить</span></button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

@if(auth()->user()->hasRole('admin'))
<div class="actionTabCnt">
    <p class="analyticsCardTitle">СТАТУС ГОСТЯ</p>
    <form method="POST" action="{{ route('guests.status', $guest) }}" class="actionTabViewOrderCardAction">
        @csrf
        <div class="defaultForm customSelect" id="guestStatusSelect{{ $guest->id }}">
            @php $statusLabels = ['' => 'Авто (по визитам)', 'new' => 'Новый', 'regular' => 'Регулярный', 'permanent' => 'Постоянный', 'vip' => 'VIP']; @endphp
            <div class="customSelectValue" data-placeholder="Авто (по визитам)">{{ $statusLabels[$guest->status_override ?? ''] }}</div>
            <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l4 4 4-4"></path></svg>
            <input type="hidden" name="status_override" value="{{ $guest->status_override }}">
            <div class="customSelectDropdown">
                @foreach($statusLabels as $val => $label)
                <div class="customSelectOption {{ ($guest->status_override ?? '') === $val ? '--active' : '' }}" data-value="{{ $val }}">{{ $label }}</div>
                @endforeach
            </div>
        </div>
        <div class="actionTabViewOrderCardActionButtons --actionTabViewOrderCardActionButtons-n5c24">
            <button type="submit" class="actionTabViewOrderCardButton --orderDone">Сохранить</button>
        </div>
    </form>
</div>
@endif

<div class="actionTabActions --actionTabActions9f3d1 --actionTabActions0v12v">
    <div class="actionTabActionsButtons">
        <button type="button" onclick="openGuestHistorySheet({{ $guest->id }}, '{{ addslashes($guest->name) }}')" class="actionTabActionsButton --buttonSecondary">Журнал изменений</button>
    </div>
</div>