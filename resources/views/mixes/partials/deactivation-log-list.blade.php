<div class="actionTabViewOrderCard actionTabViewOrderCardShow">
    <div class="actionTabHistorySUV">
        <span class="--isBlue">Активирован </span> • <span class="--isRed"> Деактивирован</span>
    </div>

    <div class="actionTabViewOrderCardBtm actionTabViewOrderCardBtmColumn" id="mixDeactivationLogRows">
        @include('mixes.partials.deactivation-log-rows', ['logs' => $logs, 'hasMore' => $hasMore])
    </div>
</div>