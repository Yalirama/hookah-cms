<div class="ordersCard" data-master-id="{{ $master->id }}" data-active="{{ $master->is_active ? '1' : '0' }}">
    <div class="ordersCardTop">
        <div class="ordersCardUser">
            <div class="ordersCardUserImg"><span>{{ mb_substr($master->name, 0, 2) }}</span></div>
            <div class="ordersCardUserInfo">
                <div class="ordersCardUserInfoTop">
                    <p class="ordersCardUserInfoName">{{ $master->name }}</p>
                    <p class="ordersCardUserInfoUsername">{{ $master->login }}</p>
                </div>
            </div>
        </div>
        <div class="ordersCardInfo">
            <div class="ordersCardId">#{{ str_pad($master->id, 4, '0', STR_PAD_LEFT) }}</div>
        </div>
    </div>
    <div class="ordersCardBtm8vh42">
        <button type="button" onclick="openEditMaster({{ $master->id }}, '{{ addslashes($master->name) }}', '{{ addslashes($master->login) }}')" class="btnOpen">Редактировать</button>
        @if($master->id !== auth()->id())
        <button type="button" onclick="openMasterToggleConfirm({{ $master->id }}, '{{ addslashes($master->name) }}', '{{ addslashes($master->login) }}', {{ $master->is_active ? 'true' : 'false' }})"
            class="btnOpen {{ $master->is_active ? 'btnCancel' : 'btnBlue' }}">
            {{ $master->is_active ? 'Деактивировать' : 'Активировать' }}
        </button>
        @endif
    </div>
</div>