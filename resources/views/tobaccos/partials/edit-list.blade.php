<form id="editTobaccoListForm">
    <div class="vs9d3">
        <div class="search">
            <img src="/assets/img/search-icon.svg" alt="">
            <input type="text" id="editTobaccoListSearch" placeholder="Бренд, название, вкус, тег..." autocomplete="off">
        </div>
    </div>

    <div class="actionTabViewTabaccosList" id="editTobaccoListWrap">
        <div id="editTobaccoBrandList">
            @foreach($brands as $brand)
                @include('tobaccos.partials.edit-brand-card', ['brand' => $brand, 'lazy' => true])
            @endforeach
        </div>
        <div id="editTobaccoSearchResults" style="display:none;"></div>
    </div>

    <p id="editTobaccoSaveError" style="display:none; color:var(--info); text-align:center; padding:8px 0;"></p>

    <div class="actionTabActions">
        <div class="actionTabActionsButtons">
            <button type="button" onclick="saveTobaccoListChanges()" class="actionTabActionsButton --buttonAccent">Сохранить</button>
        </div>
    </div>
</form>