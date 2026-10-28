@php
    $visual = \App\Models\Order::statusVisualMap()[$order->status] ?? ['card' => 'statusWait', 'label' => $order->status];
    $primaryItem = $order->items->first();
    $isMix = $primaryItem && $primaryItem->itemable instanceof \App\Models\Mix;

    $compositionLines = $order->items->flatMap(function ($item) {
        if ($item->itemable instanceof \App\Models\Mix) {
            return $item->itemable->ingredients->map(fn($ing) => ($ing->tobacco?->brand?->name ?? '—') . ' - ' . ($ing->tobacco?->name ?? '—'));
        }
        if ($item->itemable instanceof \App\Models\Tobacco) {
            return collect([($item->itemable->brand?->name ?? '—') . ' - ' . $item->itemable->name]);
        }
        return collect();
    });

    $searchData = mb_strtolower(implode(' ', array_filter([
        $order->name, (string) $order->id, $order->guest_comment,
        $order->creator?->name, $order->table?->name,
        $order->guest?->name, $order->guest?->username, $order->guest?->phone, $order->guest?->email,
        ...$order->items->map(fn($i) => $i->itemable?->name ?? '')->toArray(),
    ])), 'UTF-8');
@endphp

<div class="ordersCard --{{ $visual['card'] }}"
     data-order-id="{{ $order->id }}"
     data-status="{{ $order->status }}"
     data-guest="{{ $order->guest_id ? '1' : '0' }}"
     data-guest-status="{{ $order->guest?->status ?? '' }}"
     data-search="{{ $searchData }}">

    <div class="ordersCardTop">
        @if($order->guest)
            @php
                $g = $order->guest;
                $statusClass = ['new' => '--questNew', 'regular' => '--questRegular', 'permanent' => '--questPermanent', 'vip' => '--questVip'][$g->status] ?? '';
                $initials = mb_strtoupper(collect(explode(' ', trim($g->name)))->map(fn($w) => mb_substr($w, 0, 1))->join(''));
            @endphp
            <div class="ordersCardUser">
                <div class="ordersCardUserImg"><span>{{ $initials }}</span></div>
                <div class="ordersCardUserInfo">
                    <div class="ordersCardUserInfoTop">
                        <p class="ordersCardUserInfoName">{{ $g->name }}</p>
                        <p class="ordersCardUserInfoUsername">{{ '@' . $g->username }}</p>
                    </div>
                    <div class="ordersCardUserInfoBtm">
                        <div class="ordersCardUserInfoStatus {{ $statusClass }}">
                            @if($g->status === 'vip')<img src="/assets/img/star-gold.svg" alt="">@endif
                            <span>{{ $g->status_label }}</span>
                        </div>
                        <p class="ordersCardUserInfoVisit">{{ $g->visits_count ?? 0 }} визита</p>
                    </div>
                </div>
            </div>

            <div class="ordersCardInfo">
                <div class="ordersCardStatus"><span></span><p>{{ $visual['label'] }}</p></div>
                <div class="ordersCardId">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</div>
            </div>
        @else
            <div class="ordersCardInfo --orderWithoutGuest">
                <div class="ordersCardStatus"><span></span><p>{{ $visual['label'] }}</p></div>
                <div class="ordersCardId">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</div>
            </div>
        @endif
    </div>

    <div class="ordersCardView">
        <div class="ordersCardViewTitle{{ $isMix ? ' --typeMix' : '' }}">
            <span></span>
            <p>{{ $order->name ?: ($primaryItem?->itemable?->name ?? 'Без названия') }}</p>
        </div>
        @if($compositionLines->count())
            <ul class="ordersCardViewInfo">
                @foreach($compositionLines->take(4) as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    @if($order->guest_comment)
        <p class="ordersCardComment">{{ $order->guest_comment }}</p>
    @endif

    <div class="ordersCardDetails">
        <div class="ordersCardDetailsl">
            <div class="ordersCardDetail">
                <img src="/assets/img/time-icon.svg" alt="">
                <p>{{ ($order->started_at ?? $order->created_at)->format('H:i') }}</p>
            </div>
            <div class="ordersCardDetail">
                <img src="/assets/img/table-icon.svg" alt="">
                <p>{{ $order->table?->name ?? '—' }}</p>
            </div>
            <div class="ordersCardDetail">
                <img src="/assets/img/master-create.svg" alt="">
                <p>{{ $order->creator?->name ?? '—' }}</p>
            </div>
            @if($order->readyLog?->user && in_array($order->status, ['ready', 'served', 'closed']))
                <div class="ordersCardDetail">
                    <img src="/assets/img/master-done.svg" alt="">
                    <p>{{ $order->readyLog->user->name }}</p>
                </div>
            @endif
        </div>
        <button type="button" class="btnOpen" onclick="openOrderSheet({{ $order->id }})">Открыть</button>
    </div>
</div>