<div class="viewProductSameTabHead">
    <div class="viewProductSameTabHeadUserImg"><span>{{ mb_strtoupper(mb_substr($guest->name, 0, 1, 'UTF-8'), 'UTF-8') }}</span></div>
    <div class="viewProductSameTabHeadBody">
        <p class="viewProductSameTabHeadTitle">{{ $guest->name }}</p>
        <p class="viewProductSameTabHeadText">{{ '@' . $guest->username }}</p>
    </div>
</div>

<div class="actionTabCnt">
    <p class="analyticsCardTitle">ИСТОРИЯ ВИЗИТОВ</p>
    <div class="actionTabCntRow8dn21" id="guestVisitHistoryFullRows" data-guest-id="{{ $guest->id }}">
        @include('guests.partials.visit-history-rows', ['orders' => $orders, 'hasMore' => $hasMore])
    </div>
</div>