<div class="actionTabViewOrderCard actionTabViewOrderCardShow">
    <div class="actionTabHistorySUV">
        <span class="--isBlue">Активирован </span> • <span class="--isRed"> Деактивирован</span>
    </div>

    <div class="actionTabViewOrderCardBtm actionTabViewOrderCardBtmColumn" id="deactivationLogRows">
        @include('tobaccos.partials.deactivation-log-rows', ['logs' => $logs, 'hasMore' => $hasMore])
    </div>
</div>