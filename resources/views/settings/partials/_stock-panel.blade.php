<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <p class="analyticsCardTitle">Размер чаши по умолчанию</p>
    <div class="actionTabSection"><div class="hintRow"><label class="actionTabTitle" for="stock-bowl">Граммы в чаше</label><button type="button" class="hintBtn" onclick="stockHint(event,'bowl')">?</button></div><div class="defaultForm"><input id="stock-bowl" type="number" step="0.5" min="1" value="{{ rtrim(rtrim(number_format($bowl, 1, '.', ''), '0'), '.') }}" placeholder="Граммы"></div></div>
    <div class="actionTabActions --actionTabActions9f3d1 --actionTabActions9f3d1-2">
        <div class="actionTabActionsButtons">
            <button type="button" onclick="stockSaveBowl()" class="actionTabActionsButton --buttonPrimary">Сохранить</button>
        </div>
    </div>
</div>

<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <p class="analyticsCardTitle">Закупка и цены</p>
    <div class="actionTabSection"><div class="hintRow"><label class="actionTabTitle" for="stock-lead">Срок поставки, дней</label><button type="button" class="hintBtn" onclick="stockHint(event,'lead')">?</button></div><div class="defaultForm"><input id="stock-lead" type="number" min="0" value="{{ $lead }}" placeholder="Срок поставки, дней"></div></div>
    <div class="actionTabSection"><div class="hintRow"><label class="actionTabTitle" for="stock-buffer">Запас, дней</label><button type="button" class="hintBtn" onclick="stockHint(event,'buffer')">?</button></div><div class="defaultForm"><input id="stock-buffer" type="number" min="0" value="{{ $buffer }}" placeholder="Запас, дней"></div></div>
    <div class="actionTabSection"><div class="hintRow"><label class="actionTabTitle" for="stock-alert">Предупреждать при росте цены, %</label><button type="button" class="hintBtn" onclick="stockHint(event,'alert')">?</button></div><div class="defaultForm"><input id="stock-alert" type="number" min="0" step="1" value="{{ $alert }}" placeholder="Предупреждать при росте цены, %"></div></div>
    <div class="actionTabSection"><div class="hintRow"><label class="actionTabTitle" for="stock-min">Минимальный остаток по умолчанию, г</label><button type="button" class="hintBtn" onclick="stockHint(event,'min')">?</button></div><div class="defaultForm"><input id="stock-min" type="number" step="1" min="0" value="{{ rtrim(rtrim(number_format($minDefault, 1, '.', ''), '0'), '.') ?: '0' }}"></div></div>
    <div class="actionTabActions --actionTabActions9f3d1 --actionTabActions9f3d1-2">
        <div class="actionTabActionsButtons">
            <button type="button" onclick="stockSaveSettings()" class="actionTabActionsButton --buttonPrimary">Сохранить</button>
            <button type="button" onclick="openStockReports()" class="actionTabActionsButton --buttonSecondary">Отчёты</button>
        </div>
    </div>
</div>

<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <p class="analyticsCardTitle">Приход</p>
    <div class="actionTabSection"><div class="search"><img src="/assets/img/search-icon.svg" alt=""><input type="text" id="stock-search" placeholder="Бренд или название табака" autocomplete="off"></div></div>
    <div class="actionTabSearchSectionResult" id="stock-search-results"></div>
    <input type="hidden" id="stock-tobacco-id">
    <p class="analyticsCardTitle" id="stock-selected" style="display:none"></p>
    <div class="actionTabSection" style="margin-top:calc(10vw / var(--resizer) * 100)"><label class="actionTabTitle" for="stock-grams">Граммы</label><div class="defaultForm"><input id="stock-grams" type="number" step="0.1" min="0" placeholder="0"></div></div>
    <div class="actionTabSection"><label class="actionTabTitle" for="stock-packs">Или пачки</label><div class="defaultForm"><input id="stock-packs" type="number" step="1" min="0" placeholder="Размер пачки из параметров"></div></div>
    <div class="actionTabSection"><label class="actionTabTitle" for="stock-price">Цена за грамм (необязательно)</label><div class="defaultForm"><input id="stock-price" type="number" step="0.01" min="0" placeholder="0"></div></div>
    <div class="actionTabSection"><label class="actionTabTitle" for="stock-supplier">Поставщик</label><div class="defaultForm"><input id="stock-supplier" type="text" placeholder="Название"></div></div>
    <div class="actionTabActions --actionTabActions9f3d1 --actionTabActions9f3d1-2">
        <div class="actionTabActionsButtons">
            <button type="button" onclick="stockReceive()" class="actionTabActionsButton --buttonPrimary">Внести приход</button>
        </div>
    </div>
    <p class="formErrorBox" id="stock-error" style="display:none"></p>
</div>

<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <p class="analyticsCardTitle">Что закупать ({{ $buy->count() }})</p>
    @forelse($buy as $r)
    <div class="itemList"><span>{{ trim(($r->tobacco->brand->name ?? '') . ' ' . $r->tobacco->name) }}</span><p>доступно {{ rtrim(rtrim(number_format($r->available, 1, ',', ''), '0'), ',') ?: '0' }} г · порог {{ rtrim(rtrim(number_format(app(\App\Services\StockService::class)->minFor($r), 1, ',', ''), '0'), ',') ?: '0' }} г{{ $r->supplier ? ' · ' . $r->supplier : '' }}</p></div>
    @empty
    <p class="stockEmpty">Всё в достатке</p>
    @endforelse
</div>

@if($untracked->isNotEmpty())
<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <p class="analyticsCardTitle">Без учёта ({{ $untracked->count() }})</p>
    <p class="stockEmpty">Активные табаки без остатка. Введите, сколько грамм сейчас есть, и склад начнёт их учитывать.</p>
    <div class="search stockSearch"><img src="/assets/img/search-icon.svg" alt=""><input type="text" id="stock-filter-untracked" oninput="stockFilter('stock-list-untracked', this.value)" placeholder="Найти табак" autocomplete="off"></div>
    <div class="stockScroll" id="stock-list-untracked">
    @foreach($untracked as $t)
    <div class="ordersCard stockRow" data-tobacco-id="{{ $t->id }}">
        <div class="ordersCardTop">
            <div class="ordersCardUser"><div class="ordersCardUserInfo"><div class="ordersCardUserInfoTop">
                <p class="ordersCardUserInfoName">{{ trim(($t->brand->name ?? '') . ' ' . $t->name) }}</p>
            </div></div></div>
        </div>
        <div class="stockForm stockQuick" data-form="audit">
            <div class="defaultForm"><input type="number" step="0.1" min="0" data-f="actual" placeholder="Остаток, г"></div>
            <button type="button" class="btnConfirm" onclick="stockAudit(this)">Учитывать</button>
            <p class="formErrorBox" style="display:none"></p>
        </div>
    </div>
    @endforeach
    </div>
</div>
@endif

<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <div class="hintRow"><p class="analyticsCardTitle">Остатки</p><button type="button" class="hintBtn" onclick="stockHint(event,'stock')">?</button></div>
    <div class="search stockSearch"><img src="/assets/img/search-icon.svg" alt=""><input type="text" id="stock-filter-rows" oninput="stockFilter('stock-list-rows', this.value)" placeholder="Найти табак" autocomplete="off"></div>
    <div class="stockScroll" id="stock-list-rows">
    @forelse($rows as $r)
    @php $g = fn ($v) => rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',') ?: '0'; @endphp
    <div class="ordersCard stockRow" data-tobacco-id="{{ $r->tobacco_id }}">
        <div class="ordersCardTop">
            <div class="ordersCardUser"><div class="ordersCardUserInfo"><div class="ordersCardUserInfoTop">
                <p class="ordersCardUserInfoName">{{ trim(($r->tobacco->brand->name ?? '') . ' ' . $r->tobacco->name) }}</p>
                <span class="stockLevel --{{ $r->lvl }}">{{ ['none' => 'Нет в наличии', 'low' => 'Мало', 'ok' => 'Есть'][$r->lvl] }}</span>
                <p class="ordersCardUserInfoUsername">на складе {{ $g($r->on_hand) }} г · резерв {{ $g($r->reserved) }} г · доступно {{ $g($r->available) }} г{{ $r->avg_cost ? ' · ' . $g((float) $r->avg_cost) . " за\u{00A0}г" : '' }}</p>
            </div></div></div>
        </div>
        <div class="ordersCardBtm8vh42">
            <button type="button" class="btnOpen" onclick="stockToggle(this, 'params')">Параметры</button>
            <button type="button" class="btnOpen" onclick="stockToggle(this, 'writeoff')">Списать</button>
            <button type="button" class="btnOpen" onclick="stockToggle(this, 'audit')">Ревизия</button>
            <button type="button" class="btnOpen" onclick="stockJournal({{ $r->tobacco_id }})">Журнал</button>
        </div>
        <div class="stockForm" data-form="params" style="display:none">
            <div class="defaultForm"><input type="number" step="0.1" data-f="min_stock" value="{{ $r->min_stock > 0 ? $r->min_stock : '' }}" placeholder="Порог, г (по умолчанию {{ rtrim(rtrim(number_format($minDefault, 1, '.', ''), '0'), '.') ?: '0' }})"></div>
            <div class="defaultForm"><input type="number" step="0.1" data-f="pack_size" value="{{ $r->pack_size }}" placeholder="Пачка, г"></div>
            <div class="defaultForm"><input type="text" data-f="supplier" value="{{ $r->supplier }}" placeholder="Поставщик"></div>
            <button type="button" class="btnConfirm" onclick="stockSaveParams(this)">Сохранить</button>
            <p class="formErrorBox" style="display:none"></p>
        </div>
        <div class="stockForm" data-form="audit" style="display:none">
            <div class="defaultForm"><input type="number" step="0.1" data-f="actual" placeholder="Фактический остаток, г (учёт: {{ $g($r->on_hand) }})"></div>
            <button type="button" class="btnConfirm" onclick="stockAudit(this)">Провести ревизию</button>
            <p class="formErrorBox" style="display:none"></p>
        </div>
        <div class="stockForm" data-form="writeoff" style="display:none">
            <div class="defaultForm"><input type="number" step="0.1" data-f="grams" placeholder="Граммы"></div>
            <div class="defaultForm"><input type="text" data-f="reason" placeholder="Причина: брак, пролив, угощение"></div>
            <button type="button" class="btnConfirm" onclick="stockWriteOff(this)">Списать</button>
            <p class="formErrorBox" style="display:none"></p>
        </div>
    </div>
    @empty
    <p class="stockEmpty">Приходов ещё не было. Найдите табак выше и внесите первый приход.</p>
    @endforelse
    </div>
</div>
