@php $statusClassMap = ['new' => '--questNew', 'regular' => '--questRegular', 'permanent' => '--questPermanent', 'vip' => '--questVip']; @endphp
@forelse($guests as $guest)
    @php
        $initials = mb_strtoupper(collect(explode(' ', trim($guest->name)))->map(fn($w) => mb_substr($w, 0, 1))->join(''), 'UTF-8');
        $searchData = mb_strtolower(implode(' ', array_filter([$guest->name, $guest->username, $guest->phone, $guest->email, (string) $guest->id])), 'UTF-8');
    @endphp
    <div class="ordersCard" data-guest-id="{{ $guest->id }}" data-status="{{ $guest->status }}" data-search="{{ $searchData }}">
        <div class="ordersCardTop">
            <div class="ordersCardUser">
                <div class="ordersCardUserImg"><span>{{ $initials }}</span></div>
                <div class="ordersCardUserInfo">
                    <div class="ordersCardUserInfoTop">
                        <p class="ordersCardUserInfoName">{{ $guest->name }}</p>
                        <p class="ordersCardUserInfoUsername">{{ '@' . $guest->username }}</p>
                    </div>
                    <div class="ordersCardUserInfoBtm">
                        <div class="ordersCardUserInfoStatus {{ $statusClassMap[$guest->status] ?? '' }}">
                            @if($guest->status === 'vip')<img src="/assets/img/star-gold.svg" alt="">@endif
                            <span>{{ $guest->status_label }}</span>
                        </div>
                        <p class="ordersCardUserInfoVisit">{{ $guest->visits_count }} визита</p>
                    </div>
                </div>
            </div>

            <div class="ordersCardInfo">
                <div class="ordersCardId">#{{ str_pad($guest->id, 4, '0', STR_PAD_LEFT) }}</div>
                <button type="button" onclick="openGuestSheet({{ $guest->id }})" class="btnOpen">Открыть</button>
            </div>
        </div>
    </div>
@empty
    <p id="guestNoResults" style="color:var(--info); text-align:center; padding:20px;">Гостей не найдено</p>
@endforelse
<p id="guestNoResults" style="display:none; color:var(--info); text-align:center; padding:20px;">Ничего не найдено</p>