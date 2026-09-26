@php
    $strengthLabels = ['light' => 'Лёгкий', 'medium' => 'Средний', 'strong' => 'Крепкий'];
    $mainIngredient = $mix->ingredients->first();
    $mainTobacco = $mainIngredient?->tobacco;
    $categories = $mainTobacco?->flavorTags->map(fn($t) => $t->category)->filter()->unique('id') ?? collect();
    $totalSlotCount = array_sum($slotCounts) ?: 1;
    $dayOrder = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
    $maxDayCount = max(array_values($dayCounts) ?: [1]) ?: 1;
    $slotIcons = ['morning' => '/assets/img/day-icon.svg', 'day' => '/assets/img/day-icon.svg', 'evening' => '/assets/img/evening-icon.svg', 'night' => '/assets/img/night-icon.svg'];
@endphp

<div class="viewProductSameTabHead">
    <img src="/assets/img/brands/mix.svg" alt="">
    <div class="viewProductSameTabHeadBody">
        <p class="viewProductSameTabHeadTitle">{{ $mix->name }}</p>
        <p class="viewProductSameTabHeadText">{{ $mix->description_short }}</p>
    </div>
</div>

<div class="actionTabCnt --actionTabCnt04f2a">
    <p class="analyticsCardTitle">ИНФОРМАЦИЯ</p>

    @if($mix->description_long)
    <p class="actionTabCntText">{{ $mix->description_long }}</p>
    @endif

    @if($categories->count())
    <div class="actionTabCntRow9d3mc">
        @foreach($categories as $cat)
        <span>#{{ $cat->name }}</span>
        @endforeach
    </div>
    @endif

    @if($mainTobacco?->flavorTags->count())
    <div class="actionTabCntRow0s2fp">
        @foreach($mainTobacco->flavorTags as $tag)
        <span>{{ $tag->name }}</span>
        @endforeach
    </div>
    @endif

    <div class="actionTabCntForm8s2idRowAction">
        <div class="actionTabCntForm8s2idRowActionItem">
            <div class="item">
                <img src="/assets/img/master-done.svg" alt="">
                <p>{{ $mix->creator?->name ?? '—' }}</p>
            </div>
            <div class="item">
                <img src="/assets/img/time-icon.svg" alt="">
                <p>{{ $mix->created_at->format('d.m.Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">СОСТАВ</p>

    @foreach($mix->ingredients as $ing)
    <div class="actionTabViewOrderCard actionTabViewOrderCardHide">
        <div class="actionTabViewOrderCardTop" onclick="toggleCollapse(this)">
            <div class="actionTabCntRow0md23">
                <p>{{ $ing->tobacco?->brand?->name ?? '—' }}</p>
                <span>{{ $ing->tobacco?->name ?? '—' }}</span>
            </div>
            @if($ing->alternatives->count())
            <button type="button" class="arrowDown"><img src="/assets/img/arrow-down.svg" alt=""></button>
            @endif
        </div>

        @if($ing->alternatives->count())
        <div class="actionTabSearchSectionResult">
            <p class="actionTabSearchSectionResultTitle --actionTabSearchSectionResultTitle-4ex23">АЛЬТЕРНАТИВЫ</p>
            @foreach($ing->alternatives as $alt)
            <p class="actionTabSearchSectionResultText">{{ $alt->tobacco?->brand?->name ?? '—' }} - <span>{{ $alt->tobacco?->name ?? '—' }}</span></p>
            @endforeach
        </div>
        @endif
    </div>
    @endforeach
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">ОБЩАЯ СТАТИСТИКА</p>
    <div class="actionTabCntRow8n37d">
        <div class="item"><p>{{ $totalOrders }}</p><span>Всего заказов</span></div>
        <div class="item"><p>{{ $redoOrders }}</p><span>Запросов на переделку</span></div>
        <div class="item"><p>{{ $last7 }}</p><span>Заказов за последние 7 дней</span></div>
        <div class="item"><p>{{ $last30 }}</p><span>Заказов за последние 30 дней</span></div>
        <div class="item"><p>{{ $last60 }}</p><span>Заказов за последние 2 месяца</span></div>
        <div class="item"><p>{{ $last90 }}</p><span>Заказов за последние 3 месяца</span></div>
        <div class="item"><p>{{ $last365 }}</p><span>Заказов за последний год</span></div>
    </div>
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">КОГДА ЗАКАЗЫВАЮТ</p>
    <div class="actionTabCntRow6bs7c">
        @foreach($timeSlots as $key => $slot)
        <div class="item">
            <img src="{{ $slotIcons[$key] ?? '/assets/img/day-icon.svg' }}" alt="">
            <p>{{ $slot['label'] }}</p>
            <span>x{{ $slotCounts[$key] ?? 0 }} • {{ round(($slotCounts[$key] ?? 0) / $totalSlotCount * 100) }}%</span>
        </div>
        @endforeach
    </div>
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">ДНИ НЕДЕЛИ</p>
    <div class="actionTabCntRow1ys7s">
        @foreach($dayOrder as $day)
        @php $count = $dayCounts[$day] ?? 0; $isActive = $count > 0 && $count >= $maxDayCount * 0.5; @endphp
        <div class="item {{ $isActive ? 'active' : '' }}">
            <span></span>
            <p>{{ $day }}</p>
        </div>
        @endforeach
    </div>
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">Заметки</p>

    <form method="POST" action="{{ route('mixes.notes.store', $mix) }}" class="actionTabCntForm8s2id">
        @csrf
        <div class="actionTabCntForm8s2idRow">
            <textarea name="note" placeholder="Добавить заметку" rows="3"></textarea>
            <div class="actionTabCntForm8s2idRowAction">
                <button type="submit">
                    <img src="/assets/img/edit-icon-gold.svg" alt="">
                    <span>Добавить</span>
                </button>
            </div>
        </div>
    </form>

    @foreach($mix->notes->whereNull('deleted_at') as $note)
    <div class="actionTabCntForm8s2id">
        <div class="actionTabCntForm8s2idRow">
            <textarea readonly rows="3">{{ $note->note }}</textarea>
            <div class="actionTabCntForm8s2idRowAction">
                <div class="actionTabCntForm8s2idRowActionItem">
                    <div class="item">
                        <img src="/assets/img/master-done.svg" alt="">
                        <p>{{ $note->user?->name ?? '—' }}</p>
                    </div>
                    <div class="item">
                        <img src="/assets/img/time-icon.svg" alt="">
                        <p>{{ $note->created_at->format('d.m.Y H:i') }}</p>
                    </div>
                </div>
                @if($note->user_id === auth()->id() || auth()->user()->hasRole('admin'))
                <form method="POST" action="{{ route('mixes.notes.destroy', [$mix, $note]) }}" onsubmit="return confirm('Удалить заметку?')">
                    @csrf @method('DELETE')
                    <button type="submit">
                        <img src="/assets/img/close-icon.svg" alt="">
                        <span>Удалить</span>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

@if(auth()->user()->hasRole('admin'))
<div class="actionTabCnt">
    <p class="analyticsCardTitle">УПРАВЛЕНИЕ</p>

    @if($mix->is_active)
    <form method="POST" action="{{ route('mixes.deactivate', $mix) }}" class="actionTabViewOrderCardAction actionTabViewOrderCardAction--d032c">
        @csrf
        <div class="defaultForm"><input type="text" name="comment" placeholder="Причина деактивации (необязательно)"></div>
        <div class="actionTabViewOrderCardActionButtons">
            <button type="submit" class="actionTabViewOrderCardButton --orderCancel">Деактивировать</button>
        </div>
    </form>
    @else
    <form method="POST" action="{{ route('mixes.activate', $mix) }}" class="actionTabViewOrderCardAction">
        @csrf
        <div class="defaultForm"><input type="text" name="comment" placeholder="Комментарий (необязательно)"></div>
        <div class="actionTabViewOrderCardActionButtons">
            <button type="submit" class="actionTabViewOrderCardButton --orderDone">Активировать</button>
        </div>
    </form>
    @endif
</div>
@endif

<div class="actionTabActions --actionTabActions9f3d1 --actionTabActions7sd3d">
    <div class="actionTabActionsButtons">
        <button type="button" onclick="openMixEditSheet({{ $mix->id }})" class="actionTabActionsButton --buttonPrimary">Редактировать</button>
        <button type="button" onclick="openMixHistorySheet({{ $mix->id }}, '{{ addslashes($mix->name) }}')" class="actionTabActionsButton --buttonSecondary">Журнал изменений</button>
    </div>
</div>