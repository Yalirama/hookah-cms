@php
$typeGroups = [
    'positive' => ['label' => 'Позитивно', 'class' => '--positive'],
    'neutral'  => ['label' => 'Нейтрально', 'class' => '--neitral'],
    'negative' => ['label' => 'Негативно', 'class' => '--negative'],
];
$actionLabels = [
    'created' => 'Создан', 'updated' => 'Обновлён', 'note_added' => 'Заметка добавлена',
    'note_deleted' => 'Заметка удалена', 'activated' => 'Активирован', 'deactivated' => 'Деактивирован',
];
$fieldLabels = ['name' => 'Название', 'description' => 'Описание'];
$statusColors = [
    'new' => '--questNew', 'regular' => '--questRegular', 'permanent' => '--questPermanent', 'vip' => '--questVip',
];
@endphp

<div class="viewProductSameTabHead">
    <p class="analyticsCardTitle">Название</p>
    <div class="viewProductSameTabHeadBody">
        <p class="viewProductSameTabHeadTitle">{{ $label->name }}</p>
        <p class="viewProductSameTabHeadText">#{{ $label->id }}</p>
    </div>
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">ОБЩАЯ ИНФОРМАЦИЯ</p>
    <div class="actionTabCntRow8n37d">
        <div class="item"><p>{{ $usageCount }}</p><span>Раз используется</span></div>
        <div class="item"><p>{{ $label->created_at->translatedFormat('d.m.Y') }}</p><span>Создан</span></div>
    </div>
    @role('admin')
    <button onclick="toggleBadge()" class="actionTabActionsButton --actionTabActionsButton-pg4d2 {{ $label->is_active ? '--buttonSecondary' : '--buttonPrimary' }}">
        {{ $label->is_active ? 'Деактивировать' : 'Активировать' }}
    </button>
    @endrole
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">Заметки</p>
    <form action="#" class="actionTabCntForm8s2id" onsubmit="return false;">
        @foreach($label->notes as $note)
        <div class="actionTabCntForm8s2idRow">
            <textarea readonly rows="3">{{ $note->note }}</textarea>
            <div class="actionTabCntForm8s2idRowAction">
                <div class="actionTabCntForm8s2idRowActionItem">
                    <div class="item"><img src="/assets/img/master-done.svg"><p>{{ $note->user?->name ?? '—' }}</p></div>
                    <div class="item"><img src="/assets/img/time-icon.svg"><p>{{ $note->created_at->translatedFormat('d.m.Y H:i') }}</p></div>
                </div>
                @if($note->user_id === auth()->id() || auth()->user()->hasRole('admin'))
                <button type="button" onclick="deleteBadgeNote({{ $note->id }})">
                    <span>Удалить</span>
                </button>
                @endif
            </div>
        </div>
        @endforeach
        <div class="actionTabCntForm8s2idRow">
            <textarea id="view-new-note" placeholder="Добавить заметку" rows="3"></textarea>
            <div class="actionTabCntForm8s2idRowAction">
                <button type="button" onclick="addBadgeNote()">
                    <img src="/assets/img/edit-icon-gold.svg"><span>Добавить</span>
                </button>
            </div>
        </div>
    </form>
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">Гости</p>
    <div class="labelGuestAccordion">
        @foreach($typeGroups as $type => $group)
        @if($guestsByType[$type]->count() > 0)
        <div class="labelGuestAccordionCard {{ $group['class'] }}" data-open="0">
            <button type="button" class="labelGuestAccordionHead" onclick="toggleGuestAccordion(this)">
                <span>{{ $group['label'] }} - {{ $guestsByType[$type]->count() }}</span>
                <img src="/assets/img/arrow-down.svg" class="labelGuestAccordionArrow" alt="">
            </button>
            <div class="labelGuestAccordionBody">
                <div class="labelGuestAccordionBodyInner">
                    @foreach($guestsByType[$type] as $i => $gl)
                    @php $guest = $gl->guest; @endphp
                    <div class="ordersCard item {{ $i >= 5 ? 'hidden' : '' }}">
                        <div class="ordersCardTop">
                            <div class="ordersCardUser">
                                <div class="ordersCardUserImg"><span>{{ mb_substr($guest->name, 0, 2) }}</span></div>
                                <div class="ordersCardUserInfo">
                                    <div class="ordersCardUserInfoTop">
                                        <p class="ordersCardUserInfoName">{{ $guest->name }}</p>
                                        <p class="ordersCardUserInfoUsername">{{ '@' . $guest->username }}</p>
                                    </div>
                                    <div class="ordersCardUserInfoBtm">
                                        <div class="ordersCardUserInfoStatus {{ $statusColors[$guest->status] ?? '' }}">
                                            <span>{{ $guest->status_label }}</span>
                                        </div>
                                        <p class="ordersCardUserInfoVisit">{{ $guest->visits_count }} визитов</p>
                                    </div>
                                </div>
                            </div>
                            <div class="ordersCardInfo">
                                <div class="ordersCardId">#{{ str_pad($guest->id, 4, '0', STR_PAD_LEFT) }}</div>
                                <button type="button" onclick="openGuestSheet({{ $guest->id }})" class="btnOpen">Открыть</button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @if($guestsByType[$type]->count() > 5)
                    <div class="actionTabViewOrderCard --actionTabViewOrderCard9k2sn">
                        <div class="actionTabViewOrderCardTop">
                            <button type="button" onclick="revealMore(this)">
                                <img src="/assets/img/arrow-down.svg"><span>Показать больше</span>
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
        @endforeach
    </div>
</div>

<div class="actionTabCnt">
    <div class="actionTabViewOrderCard --actionTabViewOrderCard9k2sn">
        <div class="actionTabViewOrderCardTop">
            <button type="button" onclick="openLabelHistorySheet({{ $label->id }}, '{{ $label->name }}')">
                <img src="/assets/img/show-icon.svg" alt="">
                <span>Журнал изменений</span>
            </button>
        </div>
    </div>
</div>