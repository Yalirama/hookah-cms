<form id="editTobaccoListForm">
    <div class="vs9d3">
        <div class="search">
            <img src="/assets/img/search-icon.svg" alt="">
            <input type="text" id="editTobaccoListSearch" placeholder="Бренд, название, вкус, тег..." autocomplete="off">
        </div>
    </div>

    <div class="actionTabViewTabaccosList" id="editTobaccoListWrap">
        @foreach($brands as $brand)
            @php
                $activeCount = $brand->tobaccos->filter(fn($t) => $t->venueTobacco?->is_active)->count();
                $totalCount = $brand->tobaccos->count();
            @endphp
            <div class="actionTabViewOrderCard actionTabViewOrderCardHide editTobaccoBrandCard {{ $loop->first ? 'actionTabViewOrderCardShow' : '' }}" data-brand-name="{{ mb_strtolower($brand->name, 'UTF-8') }}">
                <div class="actionTabViewOrderCardTop" onclick="toggleCollapse(this)">
                    <p class="actionTabTitle">{{ $brand->name }} <span style="color:var(--info); font-weight:400;">({{ $activeCount }}/{{ $totalCount }})</span></p>
                    <button type="button" class="arrowDown"><img src="/assets/img/arrow-down.svg" alt=""></button>
                </div>

                <div class="actionTabSearchSectionResult">
                    <div class="editTobaccoRowsInner">
                        @foreach($brand->tobaccos as $tobacco)
                            @php
                                $isActive = (bool) $tobacco->venueTobacco?->is_active;
                                $searchData = mb_strtolower($brand->name . ' ' . $tobacco->name . ' ' . ($tobacco->alt_name ?? ''), 'UTF-8');
                            @endphp
                        <div class="editTobaccoRow {{ $isActive ? '--actionTabSearchSectionResultActive' : '' }}" data-search="{{ $searchData }}" data-tobacco-id="{{ $tobacco->id }}" data-original-active="{{ $isActive ? '1' : '0' }}">
                                <div class="ordersCardTop" id="editTobaccoCheckbox{{ $tobacco->id }}">
                                    <div class="ordersCardUser">
                                        <div class="ordersCardUserInfo">
                                            <div class="actionTabViewOrderProductListBody">
                                                <p>{{ $tobacco->name }}{{ $tobacco->alt_name ? ' - ' . $tobacco->alt_name : '' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="editTobaccoCheckboxHit" onclick="toggleEditTobaccoCheckbox({{ $tobacco->id }})" aria-label="Переключить"></button>
                                </div>

                                <div class="editTobaccoCommentWrap" id="editTobaccoCommentWrap{{ $tobacco->id }}" style="display:none;">
                                    <div class="defaultForm">
                                        <input type="text" id="editTobaccoComment{{ $tobacco->id }}" placeholder="Комментарий (необязательно)">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="actionTabActions">
        <div class="actionTabActionsButtons">
            <button type="button" onclick="saveTobaccoListChanges()" class="actionTabActionsButton --buttonAccent">Сохранить</button>
        </div>
    </div>
</form>