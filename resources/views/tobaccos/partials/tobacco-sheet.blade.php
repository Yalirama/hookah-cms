@php
    $s = ['light' => ['Лёгкий', '#d4edda', '#155724'], 'medium' => ['Средний', '#fff3cd', '#856404'], 'strong' => ['Крепкий', '#f8d7da', '#721c24']];
    $slotIcons = ['morning' => '/assets/img/day-icon.svg', 'day' => '/assets/img/day-icon.svg', 'evening' => '/assets/img/evening-icon.svg', 'night' => '/assets/img/night-icon.svg'];
    $totalSlotCount = array_sum($slotCounts) ?: 1;
    $dayShortNames = ['Пн' => 'Пн', 'Вт' => 'Вт', 'Ср' => 'Ср', 'Чт' => 'Чт', 'Пт' => 'Пт', 'Сб' => 'Сб', 'Вс' => 'Вс'];
    $dayOrder = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
    $maxDayCount = max(array_values($dayCounts) ?: [1]) ?: 1;
@endphp

<div class="viewProductSameTabHead">
    <img src="{{ $tobacco->brand?->image_url ?? '/assets/img/product-img.webp' }}" alt="">

    <div class="viewProductSameTabHeadBody">
        <p class="viewProductSameTabHeadTitle">{{ $tobacco->brand?->name }}</p>
        <p class="viewProductSameTabHeadText">{{ $tobacco->name }}{{ $tobacco->alt_name ? ' - ' . $tobacco->alt_name : '' }}</p>
    </div>
</div>

<div class="actionTabCnt --actionTabCnt04f2a">
    <p class="analyticsCardTitle">ИНФОРМАЦИЯ</p>

    @if($tobacco->description_short)
    <p class="actionTabCntText">{{ $tobacco->description_short }}</p>
    @endif

    @php $categories = $tobacco->flavorTags->map(fn($t) => $t->category)->filter()->unique('id'); @endphp
    @if($categories->count())
    <div class="actionTabCntRow9d3mc">
        @foreach($categories as $cat)
        <span>#{{ $cat->name }}</span>
        @endforeach
    </div>
    @endif

    @if($tobacco->flavorTags->count())
    <div class="actionTabCntRow0s2fp">
        @foreach($tobacco->flavorTags as $tag)
        <span>{{ $tag->name }}</span>
        @endforeach
    </div>
    @endif
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">ОБЩАЯ СТАТИСТИКА</p>

    <div class="actionTabCntRow8n37d">
        <div class="item">
            <p>{{ $totalOrders }}</p>
            <span>Всего заказов</span>
        </div>
        <div class="item">
            <p>{{ $redoOrders }}</p>
            <span>Запросов на переделку</span>
        </div>
        <div class="item">
            <p>{{ $last7 }}</p>
            <span>Заказов за последние 7 дней</span>
        </div>
        <div class="item">
            <p>{{ $last30 }}</p>
            <span>Заказов за последние 30 дней</span>
        </div>
        <div class="item">
            <p>{{ $last60 }}</p>
            <span>Заказов за последние 2 месяца</span>
        </div>
        <div class="item">
            <p>{{ $last90 }}</p>
            <span>Заказов за последние 3 месяца</span>
        </div>
        <div class="item">
            <p>{{ $last365 }}</p>
            <span>Заказов за последний год</span>
        </div>
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

    @if(auth()->check())
    <form method="POST" action="{{ route('tobaccos.notes.store', $tobacco) }}" class="actionTabCntForm8s2id">
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
    @endif

    @foreach($tobacco->notes->whereNull('deleted_at') as $note)
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
                <form method="POST" action="{{ route('tobaccos.notes.destroy', [$tobacco, $note]) }}" onsubmit="return confirm('Удалить заметку?')">
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