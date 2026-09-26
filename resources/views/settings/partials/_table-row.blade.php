<div class="ordersCard" data-table-id="{{ $table->id }}">
    <div class="ordersCardTop">
        <div class="ordersCardUser">
            <div class="ordersCardUserInfo">
                <div class="ordersCardUserInfoTop">
                    <p class="ordersCardUserInfoName">{{ $table->name }}</p>
                    <p class="ordersCardUserInfoUsername">{{ $table->type?->name ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>
    <div class="ordersCardBtm8vh42">
        <button type="button" onclick="openEditTable({{ $table->id }}, '{{ addslashes($table->name) }}', {{ $table->type_id ?? 'null' }})" class="btnOpen">Редактировать</button>
        <button type="button" onclick="openTableToggleConfirm({{ $table->id }}, '{{ addslashes($table->name) }}', {{ $table->is_active ? 'true' : 'false' }})"
            class="btnOpen {{ $table->is_active ? 'btnCancel' : 'btnBlue' }}">
            {{ $table->is_active ? 'Деактивировать' : 'Активировать' }}
        </button>
    </div>
</div>