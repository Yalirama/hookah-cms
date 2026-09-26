{{-- ГОСТИ --}}
<div class="analyticsCard analyticsCardQuests">
    <p class="analyticsCardTitle">гости</p>

    <div class="analyticsCardRow">
        {{-- Сегодня --}}
        @php $g = $guestsOverview['today']; @endphp
        <div class="analyticsQuestsItem">
            <p class="analyticsQuestsTitle">{{ $g['count'] }}</p>
            <p class="analyticsQuestsSubtitle">Сегодня</p>
            @if($g['diffPct'] !== null)
                <p class="analyticsQuestsDescription {{ $g['diff'] >= 0 ? '--minus' : '--plus' }}">
                    {{ $g['diff'] >= 0 ? '↑' : '↓' }} {{ $g['diff'] >= 0 ? '+' : '' }}{{ $g['diff'] }} vs вчера
                </p>
            @endif
            <div class="analyticsQuestsItemRow">
                @foreach($g['history'] as $bar)
                    <span class="{{ $bar['active'] ? 'active' : '' }}" style="height: {{ $bar['height'] }}%" title="{{ $bar['count'] }}"></span>
                @endforeach
            </div>
        </div>

        {{-- За неделю --}}
        @php $g = $guestsOverview['week']; @endphp
        <div class="analyticsQuestsItem">
            <p class="analyticsQuestsTitle">{{ $g['count'] }}</p>
            <p class="analyticsQuestsSubtitle">За неделю</p>
            @if($g['diffPct'] !== null)
                <p class="analyticsQuestsDescription {{ $g['diff'] >= 0 ? '--minus' : '--plus' }}">
                    {{ $g['diff'] >= 0 ? '↑' : '↓' }} {{ $g['diff'] >= 0 ? '+' : '' }}{{ $g['diffPct'] }}% vs пред.
                </p>
            @endif
            <div class="analyticsQuestsItemRow">
                @foreach($g['history'] as $bar)
                    <span class="{{ $bar['active'] ? 'active' : '' }}" style="height: {{ $bar['height'] }}%" title="{{ $bar['count'] }}"></span>
                @endforeach
            </div>
        </div>

        {{-- За месяц --}}
        @php $g = $guestsOverview['month']; @endphp
        <div class="analyticsQuestsItem">
            <p class="analyticsQuestsTitle">{{ $g['count'] }}</p>
            <p class="analyticsQuestsSubtitle">За месяц</p>
            @if($g['diffPct'] !== null)
                <p class="analyticsQuestsDescription {{ $g['diff'] >= 0 ? '--minus' : '--plus' }}">
                    {{ $g['diff'] >= 0 ? '↑' : '↓' }} {{ $g['diff'] >= 0 ? '+' : '' }}{{ $g['diffPct'] }}% vs пред.
                </p>
            @endif
            <div class="analyticsQuestsItemRow">
                @foreach($g['history'] as $bar)
                    <span class="{{ $bar['active'] ? 'active' : '' }}" style="height: {{ $bar['height'] }}%" title="{{ $bar['count'] }}"></span>
                @endforeach
            </div>
        </div>

        {{-- Всего за год --}}
        @php $g = $guestsOverview['year']; @endphp
        <div class="analyticsQuestsItem">
            <p class="analyticsQuestsTitle">{{ $g['count'] }}</p>
            <p class="analyticsQuestsSubtitle">Всего за год</p>
            @if($g['isRecord'])
                <p class="analyticsQuestsText">Рекорд</p>
            @elseif($g['diffPct'] !== null)
                <p class="analyticsQuestsDescription {{ $g['diff'] >= 0 ? '--minus' : '--plus' }}">
                    {{ $g['diff'] >= 0 ? '↑' : '↓' }} {{ $g['diff'] >= 0 ? '+' : '' }}{{ $g['diffPct'] }}% vs пред.
                </p>
            @endif
        </div>
    </div>
</div>

{{-- НОВЫЕ vs ПОВТОРНЫЕ --}}
@if($newGuests + $returningGuests > 0)
    @php
        $total = $newGuests + $returningGuests;
        $newPct = round($newGuests / $total * 100);
        $retPct = 100 - $newPct;
    @endphp
    <div class="analyticsCard analyticsCardQuestsPercent">
        <p class="analyticsCardTitle">НОВЫЕ <span>vs</span> ПОВТОРНЫЕ</p>

        <div class="analyticsCardRow">
            <div class="analyticsQuestsItem">
                <div class="analyticsCardQuestsBody">
                    <span style="--percent: {{ $newPct }}"></span>
                </div>

                <div class="analyticsCardQuestsBodyRow">
                    <div class="analyticsCardQuestsBodyRowItem">
                        <span class="analyticsCardQuestsBodyRowItemCircle"></span>
                        <div class="analyticsCardQuestsBodyRowItemBody">
                            <p>{{ $newGuests }} новых</p>
                            <span>{{ $newPct }}%</span>
                        </div>
                    </div>

                    <div class="analyticsCardQuestsBodyRowItem">
                        <span class="analyticsCardQuestsBodyRowItemCircle"></span>
                        <div class="analyticsCardQuestsBodyRowItemBody">
                            <p>{{ $returningGuests }} повторных</p>
                            <span>{{ $retPct }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- КОГДА ПРИХОДЯТ --}}
@if(count($slotCounts) > 0)
    @php
        $maxSlot = max(array_column($slotCounts, 'count')) ?: 1;
        $slotIcons = [
            'morning' => 'sunrise-icon.svg',
            'day'     => 'day-icon.svg',
            'evening' => 'evening-icon.svg',
            'night'   => 'night-icon.svg',
        ];
    @endphp
    <div class="analyticsCard analyticsCardDayTime">
        <p class="analyticsCardTitle">КОГДА ПРИХОДЯТ</p>

        <div class="analyticsCardRow">
            @foreach($slotCounts as $key => $slot)
                <div class="analyticsQuestsItem {{ $slot['count'] === $maxSlot ? 'active' : '' }}">
                    <img src="/assets/img/{{ $slotIcons[$key] ?? 'day-icon.svg' }}" alt="">
                    <p>{{ $slot['label'] }}</p>
                    <span>{{ round($slot['count'] / (array_sum(array_column($slotCounts, 'count')) ?: 1) * 100) }}%</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ЗАГРУЖЕННОСТЬ ПО ЧАСАМ • НЕДЕЛЯ --}}
@if(count($heatmapSlots) > 0 && is_array($days) && count($days) > 0)
    @php
        $allValues = [];
        foreach ($heatmap as $day => $slots) {
            foreach ($slots as $val) $allValues[] = $val;
        }
        $maxHeat = max($allValues) ?: 1;
        $stageOf = function ($val) use ($maxHeat) {
            if ($val <= 0) return 1;
            return min(5, max(1, (int) ceil($val / $maxHeat * 5)));
        };
    @endphp
    <div class="analyticsCard analyticsWorkloadByTimeAndWeek">
        <p class="analyticsCardTitle">ЗАГРУЖЕННОСТЬ ПО ЧАСАМ &#8226; НЕДЕЛЯ</p>

        <div class="analyticsCardRow">
            <div class="analyticsQuestsItem">
                <div class="analyticsWorkloadByTimeAndWeekTop">
                    <span></span>
                    @foreach($days as $day)
                        <span>{{ $day }}</span>
                    @endforeach
                </div>

                <div class="analyticsWorkloadByTimeAndWeekBtm">
                    @foreach($heatmapSlots as $slot)
                        <div class="analyticsWorkloadByTimeAndWeekBtmItem">
                            <div class="--time">{{ $slot['label'] }}</div>
                            @foreach($days as $day)
                                @php $val = $heatmap[$day][$slot['label']] ?? 0; @endphp
                                <span class="--stage{{ $stageOf($val) }}" title="{{ $val }}"></span>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="analyticsWorkloadByTimeAndWeekSUV">
                    <p>Меньше</p>
                    <span class="--stage1"></span>
                    <span class="--stage2"></span>
                    <span class="--stage3"></span>
                    <span class="--stage4"></span>
                    <span class="--stage5"></span>
                    <p>Больше</p>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Универсальный компонент "ТОП 5" --}}
@php
    $renderTop = function ($title, $rows) {
        // $rows: массив ['title' => string, 'count' => int, 'pct' => int]
        return compact('title', 'rows');
    };
@endphp

{{-- ТОП МИКСОВ --}}
@if($topMixes->count() > 0)
    @php $maxMix = $topMixes->max('cnt') ?: 1; @endphp
    <div class="analyticsCard analyticsCardTop">
        <div class="analyticsCardTopHead">
            <p class="analyticsCardTitle">ТОП 5 МИКСОВ</p>
        </div>

        <div class="analyticsCardTopRow topListRow">
            @foreach($topMixes as $i => $item)
                <div class="analyticsCardTopItem {{ $i >= 5 ? 'topListHidden' : '' }}" @if($i >= 5) style="display:none;" @endif>
                    <div class="analyticsCardTopItemLeft">
                        <div class="analyticsCardTopItemCount"><span>{{ $i + 1 }}</span></div>
                        <p class="analyticsCardTopItemTitle">{{ $item->itemable?->name ?? '—' }}</p>
                    </div>

                    <div class="analyticsCardTopItemRight">
                        <div class="analyticsCardTopItemBar">
                            <span style="width: {{ round($item->cnt / $maxMix * 100) }}%"></span>
                        </div>
                        <span class="analyticsCardTopItemDesc">x{{ $item->cnt }}</span>
                    </div>
                </div>
            @endforeach

            @if($topMixes->count() > 5)
            <div class="actionTabViewOrderCard --actionTabViewOrderCard9k2sn --actionTabViewOrderCardf3s13">
                <div class="actionTabViewOrderCardTop">
                    <button type="button" onclick="expandTopList(this)">
                        <img src="/assets/img/arrow-down.svg" alt="">
                        <span>Показать больше</span>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
@endif

{{-- ТОП ТАБАКОВ --}}
@if($topTobaccos->count() > 0)
    @php $maxTob = $topTobaccos->max('cnt') ?: 1; @endphp
    <div class="analyticsCard analyticsCardTop">
        <div class="analyticsCardTopHead">
            <p class="analyticsCardTitle">ТОП 5 табаков</p>
        </div>

        <div class="analyticsCardTopRow topListRow">
            @foreach($topTobaccos as $i => $item)
                <div class="analyticsCardTopItem {{ $i >= 5 ? 'topListHidden' : '' }}" @if($i >= 5) style="display:none;" @endif>
                    <div class="analyticsCardTopItemLeft">
                        <div class="analyticsCardTopItemCount"><span>{{ $i + 1 }}</span></div>
                        <p class="analyticsCardTopItemTitle">{{ $item->itemable?->brand?->name ?? '—' }} — {{ $item->itemable?->name ?? '—' }}</p>
                    </div>

                    <div class="analyticsCardTopItemRight">
                        <div class="analyticsCardTopItemBar">
                            <span style="width: {{ round($item->cnt / $maxTob * 100) }}%"></span>
                        </div>
                        <span class="analyticsCardTopItemDesc">x{{ $item->cnt }}</span>
                    </div>
                </div>
            @endforeach

            @if($topTobaccos->count() > 5)
            <div class="actionTabViewOrderCard --actionTabViewOrderCard9k2sn --actionTabViewOrderCardf3s13">
                <div class="actionTabViewOrderCardTop">
                    <button type="button" onclick="expandTopList(this)">
                        <img src="/assets/img/arrow-down.svg" alt="">
                        <span>Показать больше</span>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
@endif

{{-- ТОП БРЕНДОВ --}}
@if($topBrands->count() > 0)
    @php $totalBrands = $topBrands->sum(); @endphp
    <div class="analyticsCard analyticsCardTop">
        <div class="analyticsCardTopHead">
            <p class="analyticsCardTitle">ТОП 5 бренды</p>
        </div>

        <div class="analyticsCardTopRow topListRow">
            @foreach($topBrands as $brand => $cnt)
                @php $pct = round($cnt / $totalBrands * 100); @endphp
                <div class="analyticsCardTopItem {{ $loop->index >= 5 ? 'topListHidden' : '' }}" @if($loop->index >= 5) style="display:none;" @endif>
                    <div class="analyticsCardTopItemLeft">
                        <div class="analyticsCardTopItemCount"><span>{{ $loop->index + 1 }}</span></div>
                        <p class="analyticsCardTopItemTitle">{{ $brand }}</p>
                    </div>

                    <div class="analyticsCardTopItemRight">
                        <div class="analyticsCardTopItemBar">
                            <span style="width: {{ $pct }}%"></span>
                        </div>
                        <span class="analyticsCardTopItemDesc">{{ $pct }}%</span>
                    </div>
                </div>
            @endforeach

            @if($topBrands->count() > 5)
            <div class="actionTabViewOrderCard --actionTabViewOrderCard9k2sn --actionTabViewOrderCardf3s13">
                <div class="actionTabViewOrderCardTop">
                    <button type="button" onclick="expandTopList(this)">
                        <img src="/assets/img/arrow-down.svg" alt="">
                        <span>Показать больше</span>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
@endif

{{-- ТОП МАСТЕРОВ --}}
@if($topMasters->count() > 0)
    @php $totalMasters = $topMasters->sum('cnt'); @endphp
    <div class="analyticsCard analyticsCardTop">
        <div class="analyticsCardTopHead">
            <p class="analyticsCardTitle">ТОП 5 мастеров</p>
        </div>

        <div class="analyticsCardTopRow topListRow">
            @foreach($topMasters as $i => $item)
                @php $pct = round($item->cnt / $totalMasters * 100); @endphp
                <div class="analyticsCardTopItem {{ $i >= 5 ? 'topListHidden' : '' }}" @if($i >= 5) style="display:none;" @endif>
                    <div class="analyticsCardTopItemLeft">
                        <div class="analyticsCardTopItemCount"><span>{{ $i + 1 }}</span></div>
                        <p class="analyticsCardTopItemTitle">{{ $item->creator?->name ?? '—' }}</p>
                    </div>

                    <div class="analyticsCardTopItemRight">
                        <div class="analyticsCardTopItemBar">
                            <span style="width: {{ $pct }}%"></span>
                        </div>
                        <span class="analyticsCardTopItemDesc">{{ $pct }}%</span>
                    </div>
                </div>
            @endforeach

            @if($topMasters->count() > 5)
            <div class="actionTabViewOrderCard --actionTabViewOrderCard9k2sn --actionTabViewOrderCardf3s13">
                <div class="actionTabViewOrderCardTop">
                    <button type="button" onclick="expandTopList(this)">
                        <img src="/assets/img/arrow-down.svg" alt="">
                        <span>Показать больше</span>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
@endif

{{-- ТОП КРЕПОСТЬ --}}
@if($topStrengths->count() > 0)
    @php
        $strengthLabels = ['light' => 'Лёгкий', 'medium' => 'Средний', 'strong' => 'Крепкий'];
        $maxStrength = $topStrengths->max() ?: 1;
    @endphp
    <div class="analyticsCard analyticsCardTop">
        <div class="analyticsCardTopHead">
            <p class="analyticsCardTitle">ТОП крепость</p>
        </div>

        <div class="analyticsCardTopRow">
            @foreach($topStrengths as $strength => $count)
                <div class="analyticsCardTopItem">
                    <div class="analyticsCardTopItemLeft">
                        <div class="analyticsCardTopItemCount"><span>{{ $loop->index + 1 }}</span></div>
                        <p class="analyticsCardTopItemTitle">{{ $strengthLabels[$strength] ?? $strength }}</p>
                    </div>

                    <div class="analyticsCardTopItemRight">
                        <div class="analyticsCardTopItemBar">
                            <span style="width: {{ round($count / $maxStrength * 100) }}%"></span>
                        </div>
                        <span class="analyticsCardTopItemDesc">x{{ $count }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ТОП ВКУСОВ (в мокапе такой карточки не было — добавил тем же компонентом,
        чтобы не терять данные из старой страницы. Убери блок, если он не нужен) --}}
@if($topFlavors->count() > 0)
    @php $maxFlavor = collect($topFlavors)->max('count') ?: 1; @endphp
    <div class="analyticsCard analyticsCardTop">
        <div class="analyticsCardTopHead">
            <p class="analyticsCardTitle">ТОП вкусов</p>
        </div>

        <div class="analyticsCardTopRow topListRow">
            @foreach($topFlavors as $i => $flavor)
                <div class="analyticsCardTopItem {{ $i >= 5 ? 'topListHidden' : '' }}" @if($i >= 5) style="display:none;" @endif>
                    <div class="analyticsCardTopItemLeft">
                        <div class="analyticsCardTopItemCount"><span>{{ $i + 1 }}</span></div>
                        <p class="analyticsCardTopItemTitle">{{ $flavor['name'] }}</p>
                    </div>

                    <div class="analyticsCardTopItemRight">
                        <div class="analyticsCardTopItemBar">
                            <span style="width: {{ round($flavor['count'] / $maxFlavor * 100) }}%"></span>
                        </div>
                        <span class="analyticsCardTopItemDesc">x{{ $flavor['count'] }}</span>
                    </div>
                </div>
            @endforeach

            @if($topFlavors->count() > 5)
            <div class="actionTabViewOrderCard --actionTabViewOrderCard9k2sn --actionTabViewOrderCardf3s13">
                <div class="actionTabViewOrderCardTop">
                    <button type="button" onclick="expandTopList(this)">
                        <img src="/assets/img/arrow-down.svg" alt="">
                        <span>Показать больше</span>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
@endif
{{-- ВКУСЫ ПО ДНЯМ НЕДЕЛИ --}}
<div class="analyticsCard analyticsCardTastes">
    <p class="analyticsCardTitle">ВКУСЫ ПО ДНЯМ НЕДЕЛИ</p>

    <div class="analyticsCardTastesRow">
        @foreach($dayNames7 as $day)
            <div class="analyticsCardTastesItem">
                <p class="analyticsCardTastesItemTitle">{{ $day }}</p>
                <div class="analyticsCardTastesItemRow">
                    @forelse(array_count_values($flavorsByDay[$day] ?? []) as $flavor => $count)
                        <div class="analyticsCardTastesItemRowItem">
                            <span>{{ $flavor }}@if($count > 1) &times;{{ $count }}@endif</span>
                        </div>
                    @empty
                        <div class="analyticsCardTastesItemRowItem">
                            <span>—</span>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- ТОП МЕСТА --}}
@if($topTables->count() > 0)
    @php $totalTables = $topTables->sum('cnt'); @endphp
    <div class="analyticsCard analyticsCardDayTime">
        <p class="analyticsCardTitle">Топ места</p>

        <div class="analyticsCardRow">
            @foreach($topTables->take(4) as $i => $item)
                @php $pct = round($item->cnt / $totalTables * 100); @endphp
                <div class="analyticsQuestsItem {{ $i === 0 ? 'active' : '' }}">
                    <p>{{ $item->table?->name ?? '—' }}</p>
                    <span>{{ $item->cnt }} раз &#8226; {{ $pct }}%</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ДАВНО НЕ ПРИХОДИЛИ --}}
@include('analytics.partials.inactive-guests')
