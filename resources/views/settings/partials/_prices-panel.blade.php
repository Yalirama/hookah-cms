<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <div class="hintRow"><p class="analyticsCardTitle">Цена по умолчанию, ₸</p><button type="button" class="hintBtn" data-hint="Подставляется, если у табака или микса не указана своя цена. Можно оставить пустым: тогда позиции без цены не попадут в выручку.">?</button></div>
    <div class="defaultForm"><input type="number" step="1" min="0" id="price-default" value="{{ $default }}" placeholder="Например, 7000"></div>
</div>

<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <div class="hintRow"><p class="analyticsCardTitle">Цены позиций, ₸</p><button type="button" class="hintBtn" data-hint="Цена, по которой позиция продаётся гостю. В заказ она записывается в момент добавления, поэтому старые заказы не меняются, когда вы меняете цену. По этим ценам считается выручка в статистике.">?</button></div>
    <div class="search stockSearch"><img src="/assets/img/search-icon.svg" alt=""><input type="text" oninput="priceFilter(this.value)" placeholder="Найти табак или микс" autocomplete="off"></div>
    <div class="stockScroll" id="price-list">
        @foreach($mixes as $m)
        <div class="priceRow" data-name="{{ mb_strtolower($m->name) }}">
            <div class="priceRowName"><p>{{ $m->name }}</p><span>Микс</span></div>
            <div class="defaultForm"><input type="number" step="1" min="0" data-kind="mixes" data-id="{{ $m->id }}" value="{{ $m->price !== null ? (float) $m->price : '' }}" placeholder="—"></div>
        </div>
        @endforeach
        @foreach($tobaccos as $t)
        <div class="priceRow" data-name="{{ mb_strtolower(($t->brand->name ?? '') . ' ' . $t->name) }}">
            <div class="priceRowName"><p>{{ $t->name }}</p><span>{{ $t->brand->name ?? '' }}</span></div>
            <div class="defaultForm"><input type="number" step="1" min="0" data-kind="tobaccos" data-id="{{ $t->id }}" value="{{ $t->venueTobacco?->price !== null ? (float) $t->venueTobacco->price : '' }}" placeholder="—"></div>
        </div>
        @endforeach
    </div>
    <p class="formErrorBox" id="price-error" style="display:none"></p>
</div>
<div class="actionTabActions"><div class="actionTabActionsButtons"><button type="button" class="actionTabActionsButton --buttonAccent" onclick="priceSave()">Сохранить цены</button></div></div>
