<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <div class="hintRow"><p class="analyticsCardTitle">Найти бренд в каталоге</p><button type="button" class="hintBtn" data-hint="Каталог берётся с сайта htreviews.org. Введите название бренда, выберите его, и мы покажем, какие позиции там есть, а каких у вас ещё нет. Загружаются только те, что вы отметите.">?</button></div>
    <div class="search stockSearch"><img src="/assets/img/search-icon.svg" alt=""><input type="text" id="catalog-q" oninput="catalogSearch(this.value)" placeholder="Например, Darkside" autocomplete="off"></div>
    <div id="catalog-results" class="catList"></div>
</div>

<div id="catalog-brand"></div>

<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <div class="hintRow"><p class="analyticsCardTitle">Ваши бренды</p><button type="button" class="hintBtn" data-hint="Бренды, которые уже есть в базе. Кнопка «Проверить» сравнивает ваш список с каталогом и показывает новые позиции, например новые вкусы.">?</button></div>
    <div class="search stockSearch"><img src="/assets/img/search-icon.svg" alt=""><input type="text" oninput="catalogFilter(this.value)" placeholder="Найти среди своих брендов" autocomplete="off"></div>
    <div class="stockScroll catList" id="catalog-brands">
        @foreach($brands as $b)
        <div class="catRow" data-name="{{ mb_strtolower($b->name) }}">
            <div class="catRowName"><p>{{ $b->name }}</p><span>{{ $b->tobaccos_count }} поз.@if($b->synced_at) · проверен {{ $b->synced_at->format('d.m.Y') }}@endif</span></div>
            <button type="button" class="catBtn" onclick="catalogOpen({brand_id: {{ $b->id }}})">Проверить</button>
        </div>
        @endforeach
    </div>
</div>

<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <div class="hintRow"><p class="analyticsCardTitle">Табак, которого нет в каталоге</p><button type="button" class="hintBtn" data-hint="Если вкус новый или тестовый и в каталоге его ещё нет, создайте его вручную и пользуйтесь как обычным: он попадёт в заказы, миксы и склад. Когда позиция появится в каталоге, откройте бренд и нажмите «Привязать»: все заказы, миксы и остатки перейдут на каталожный табак.">?</button></div>
    <button type="button" class="catBtn catBtnWide" onclick="document.getElementById('catalog-manual').classList.toggle('open')">Создать табак вручную</button>
    <div class="catForm" id="catalog-manual">
        <div class="defaultForm"><select id="cm-brand"><option value="">Бренд: выберите из списка</option>@foreach($brands as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
        <div class="defaultForm"><input type="text" id="cm-brand-name" placeholder="или впишите новый бренд"></div>
        <div class="defaultForm"><input type="text" id="cm-name" placeholder="Название вкуса"></div>
        <div class="defaultForm"><input type="text" id="cm-alt" placeholder="Название по-русски (необязательно)"></div>
        <div class="defaultForm"><select id="cm-strength"><option value="medium">Крепость: средняя</option><option value="light">Крепость: лёгкая</option><option value="strong">Крепость: крепкая</option></select></div>
        <div class="defaultForm"><textarea id="cm-desc" rows="3" placeholder="Описание (необязательно)"></textarea></div>
        <p class="formErrorBox" id="cm-error" style="display:none"></p>
        <button type="button" class="btnConfirm" onclick="catalogManualSave()">Создать и добавить в список</button>
    </div>
    @if($manual->isNotEmpty())
    <p class="statSub">Созданы вручную, ждут каталога</p>
    @foreach($manual as $t)
    <div class="catRow"><div class="catRowName"><p>{{ $t->name }}</p><span>{{ $t->brand->name ?? '' }}</span></div><button type="button" class="catBtn" onclick="catalogOpen({brand_id: {{ $t->brand_id }}})">Найти в каталоге</button></div>
    @endforeach
    @endif
</div>
