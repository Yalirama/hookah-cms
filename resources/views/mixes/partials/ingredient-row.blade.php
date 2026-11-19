@php
    $mainName = $ing?->tobacco ? trim(($ing->tobacco->brand?->name ?? '') . ' - ' . $ing->tobacco->name) : null;
    $rowId = $formId . '_ing' . $index;
    $stockSvc = app(\App\Services\StockService::class);
    $offText = fn ($t) => !$t ? null : (!$t->isInVenue() ? 'Деактивирован' : ($stockSvc->servingsLeft($t->id) === 0 ? 'Закончился' : null));
@endphp

<div class="actionTabViewOrderCard mixIngredientRow" data-index="{{ $index }}" id="{{ $rowId }}">
    <div class="actionTabViewOrderCardTop">
        <div class="actionTabCntRow0md23 mixIngredientMainLabel" id="{{ $rowId }}_label">
            @if($mainName)
                <p>{{ $ing->tobacco->brand?->name }}</p>
                <span>{{ $ing->tobacco->name }}</span>
                @if($offText($ing->tobacco))<span class="addItemOff">{{ $offText($ing->tobacco) }}</span>@endif
            @else
                <p>Основной вкус #{{ $index + 1 }}</p>
            @endif
        </div>
        <button type="button" onclick="toggleMixIngredientSearch('{{ $rowId }}', 'main')">
            <img src="/assets/img/edit-icon-gold.svg" alt="">
            <span>{{ $mainName ? 'Изменить' : 'Добавить' }}</span>
        </button>
    </div>

    <input type="hidden" name="ingredients[{{ $index }}][tobacco_id]" id="{{ $rowId }}_tobaccoId" value="{{ $ing?->tobacco_id }}">
    <input type="hidden" name="ingredients[{{ $index }}][grams]" id="{{ $rowId }}_grams" value="{{ $ing?->grams }}">
    <input type="hidden" name="ingredients[{{ $index }}][unit]" id="{{ $rowId }}_unit" value="{{ $ing?->unit ?? 'g' }}">

    <div class="mixIngredientSearchWrap" id="{{ $rowId }}_search_main" style="display:none;">
        <div class="search">
            <img src="/assets/img/search-icon.svg" alt="">
            <input type="text" placeholder="Бренд, название, вкус, тег..." oninput="filterMixTobaccoOptions('{{ $rowId }}', 'main', this.value)">
        </div>
        <div class="actionTabSearchSectionResult mixTobaccoOptions --actionTabSearchSectionResult-vc31f4" id="{{ $rowId }}_options_main"></div>
    </div>

    <div class="actionTabSearchSectionResult --actionTabSearchSectionResult-v4v0d">
        <div class="actionTabSearchSectionResultHead">
            <p class="actionTabSearchSectionResultTitle">Альтернативы</p>
            <button type="button" onclick="toggleMixIngredientSearch('{{ $rowId }}', 'alt')">
                <img src="/assets/img/edit-icon-gold.svg" alt="">
                <span>Добавить</span>
            </button>
        </div>

        <div class="mixIngredientSearchWrap --mixIngredientSearchWrap-4vs22" id="{{ $rowId }}_search_alt" style="display:none;">
            <div class="search">
                <img src="/assets/img/search-icon.svg" alt="">
                <input type="text" placeholder="Бренд, название, вкус, тег..." oninput="filterMixTobaccoOptions('{{ $rowId }}', 'alt', this.value)">
            </div>
            <div class="actionTabSearchSectionResult mixTobaccoOptions --actionTabSearchSectionResult-7vc2" id="{{ $rowId }}_options_alt"></div>
        </div>

        <div class="mixAltList --mixAltList-v03dw" id="{{ $rowId }}_alts">
            @foreach($ing?->alternatives ?? [] as $j => $alt)
                <p class="actionTabSearchSectionResultText mixAltTag" data-alt-index="{{ $j }}">
                    <span class="mixAltName"><b>{{ $alt->tobacco?->brand?->name }} -</b> {{ $alt->tobacco?->name }}@if($offText($alt->tobacco)) <em class="addItemOff">{{ $offText($alt->tobacco) }}</em>@endif<input type="hidden" name="ingredients[{{ $index }}][alternatives][{{ $j }}][tobacco_id]" value="{{ $alt->tobacco_id }}"></span>
                    <button class="--button-03f2d" type="button" onclick="removeMixAlt(this)"><img src="/assets/img/close-icon.svg" alt=""></button>
                </p>
            @endforeach
        </div>
    </div>
</div>