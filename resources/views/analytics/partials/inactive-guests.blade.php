{{-- ДАВНО НЕ ПРИХОДИЛИ --}}
<div class="analyticsCard analyticsCardLongGuest" id="inactiveGuestsCard">
    <p class="analyticsCardTitle">ДАВНО НЕ ПРИХОДИЛИ ({{ $inactiveDays }}+ дней)</p>

    <div class="filters">
        <div class="filtersRow">
            @foreach(['all'=>'Все гости','vip'=>'VIP','permanent'=>'Постоянный','regular'=>'Регулярный','new'=>'Новый'] as $key => $label)
                <a href="?period={{ $period }}&inactive_status={{ $key }}" class="filtersBtn inactiveStatusFilter {{ $inactiveStatus === $key ? 'active' : '' }}" data-status="{{ $key }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @if($inactiveGuests->count() > 0)
        <div class="analyticsCardLongGuestRow">
            @foreach($inactiveGuests as $guest)
                @php
                    $days = (int) \Carbon\Carbon::parse($guest->last_visit)->diffInDays(now());
                    $initials = mb_strtoupper(mb_substr($guest->name, 0, 1, 'UTF-8'), 'UTF-8')
                        . mb_strtoupper(mb_substr(explode(' ', $guest->name)[1] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
                    $statusClass = ['new' => '--questNew', 'regular' => '--questRegular', 'permanent' => '--questPermanent', 'vip' => '--questVip'][$guest->status] ?? '';
                @endphp
                <div class="ordersCard">
                    <div class="ordersCardTop">
                        <div class="ordersCardUser">
                            <div class="ordersCardUserImg"><span>{{ $initials }}</span></div>
                            <div class="ordersCardUserInfo">
                                <div class="ordersCardUserInfoTop">
                                    <p class="ordersCardUserInfoName">{{ $guest->name }}</p>
                                    <p class="ordersCardUserInfoUsername">{{ $guest->username ? '@'.$guest->username : '' }}</p>
                                </div>
                                <div class="ordersCardUserInfoBtm">
                                    <div class="ordersCardUserInfoStatus {{ $statusClass }}">
                                        @if($guest->status === 'vip')
                                            <img src="/assets/img/star-gold.svg" alt="">
                                        @endif
                                        <span>{{ $guest->status_label }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ordersCardInfo">
                            <button type="button" onclick="openGuestSheet({{ $guest->id }})" class="btnOpen">Открыть</button>
                        </div>
                    </div>

                    <div class="analyticsCardLongGuestRowSUV">
                        <p>Последний визит {{ \Carbon\Carbon::parse($guest->last_visit)->format('d.m.Y') }}</p>
                        <p>{{ $days }} дн.</p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p style="color:#666; font-size:14px; padding:12px 0;">Нет гостей по выбранному фильтру</p>
    @endif
</div>