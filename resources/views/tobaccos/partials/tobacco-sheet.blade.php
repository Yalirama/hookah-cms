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
    <p class="analyticsCardTitle">Информация</p>

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
    <p class="analyticsCardTitle">Общая статистика</p>

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
    <p class="analyticsCardTitle">Когда заказывают</p>

    <div class="actionTabCntRow6bs7c">
        @foreach($timeSlots as $key => $slot)
        <div class="item">
            <img src="{{ $slotIcons[$key] ?? '/assets/img/day-icon.svg' }}" alt="">
            <p>{{ $slot['label'] }}</p>
            <span>{{ round(($slotCounts[$key] ?? 0) / $totalSlotCount * 100) }}%</span>
            <small>x{{ $slotCounts[$key] ?? 0 }}</small>
        </div>
        @endforeach
    </div>
</div>

@include('partials.freq-bars', ['title' => 'Дни недели', 'items' => collect($dayOrder)->map(fn($d) => ['label' => $d, 'count' => $dayCounts[$d] ?? 0])->all()])

@include('partials.freq-bars', ['title' => 'По месяцам', 'items' => $months])

@include('partials.freq-bars', ['title' => 'Сезонность', 'items' => $seasons])

@if(count($years))
@include('partials.freq-bars', ['title' => 'По годам', 'items' => $years])
@endif

<div class="actionTabCnt">
    <p class="analyticsCardTitle">С чем заказывают</p>
    <p class="statSub">Чаще всего в миксах, закрытых без проблем@if($pairs['closedTotal']) · всего {{ $pairs['closedTotal'] }}@endif</p>
    @forelse($pairs['closed'] as $i => $r)
    <p class="statTop"><i>{{ $i + 1 }}</i><span>{{ $r['name'] }}</span><b>{{ $r['n'] }} · {{ $r['pct'] }}%</b></p>
    @empty
    <p class="statNote">Пока нет завершённых заказов с миксами, где есть этот табак.</p>
    @endforelse
    <p class="statSub">Чаще всего в миксах, ушедших в переделку@if($pairs['redoTotal']) · всего {{ $pairs['redoTotal'] }}@endif</p>
    @forelse($pairs['redo'] as $i => $r)
    <p class="statTop"><i>{{ $i + 1 }}</i><span>{{ $r['name'] }}</span><b>{{ $r['n'] }} · {{ $r['pct'] }}%</b></p>
    @empty
    <p class="statNote">Переделок с этим табаком не было.</p>
    @endforelse
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