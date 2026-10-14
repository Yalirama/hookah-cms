@foreach($tobaccos as $tobacco)
    @php $isActive = (bool) $tobacco->venueTobacco?->is_active; @endphp
    <div class="editTobaccoRow {{ $isActive ? '--actionTabSearchSectionResultActive' : '' }}" data-tobacco-id="{{ $tobacco->id }}" data-original-active="{{ $isActive ? '1' : '0' }}">
        <div class="ordersCardTop">
            <div class="ordersCardUser">
                <div class="ordersCardUserInfo">
                    <div class="actionTabViewOrderProductListBody">
                        <p>{{ $tobacco->name }}{{ $tobacco->alt_name ? ' - ' . $tobacco->alt_name : '' }}</p>
                    </div>
                </div>
            </div>
            <button type="button" class="editTobaccoCheckboxHit" onclick="toggleEditTobaccoCheckbox(this)" aria-label="Переключить"></button>
        </div>
        <div class="editTobaccoCommentWrap" style="display:none;">
            <div class="defaultForm">
                <input type="text" class="editTobaccoComment" placeholder="Комментарий (необязательно)">
            </div>
        </div>
    </div>
@endforeach