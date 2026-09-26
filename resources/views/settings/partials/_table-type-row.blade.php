<div class="ordersCard" data-type-id="{{ $type->id }}">
    <div class="ordersCardTop">
        <div class="ordersCardUser">
            <div class="ordersCardUserInfo">
                <div class="ordersCardUserInfoTop">
                    <p class="ordersCardUserInfoName">{{ $type->name }}</p>
                    <p class="ordersCardUserInfoUsername">{{ $type->description ?? '—' }}</p>
                </div>
            </div>
        </div>
        <div class="ordersCardInfo">
            <div class="ordersCardId">{{ $type->tables_count }} столов</div>
        </div>
    </div>
    <div class="ordersCardBtm8vh42">
        <button type="button" onclick="openEditTableType({{ $type->id }}, '{{ addslashes($type->name) }}', '{{ addslashes($type->description ?? '') }}')" class="btnOpen">Редактировать</button>
        <button type="button" onclick="openTableTypeToggleConfirm({{ $type->id }}, '{{ addslashes($type->name) }}', {{ $type->trashed() ? 'true' : 'false' }})"
            class="btnOpen {{ $type->trashed() ? 'btnBlue' : 'btnCancel' }}">
            {{ $type->trashed() ? 'Активировать' : 'Деактивировать' }}
        </button>
    </div>
</div>