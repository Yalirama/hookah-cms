@php
    $visual = \App\Models\Order::statusVisualMap()[$order->status] ?? ['card' => 'statusWait', 'label' => $order->status];
    $primaryItem = $order->items->first();
    $isMix = $primaryItem && $primaryItem->itemable instanceof \App\Models\Mix;

    $compositionLines = $order->items->flatMap(function ($item) {
        if ($item->itemable instanceof \App\Models\Mix) {
            return collect(['MiX - ' . $item->itemable->name]);
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

<div class="ordersCard ocCard --{{ $visual['card'] }}"
     data-order-id="{{ $order->id }}"
     data-created="{{ $order->created_at->timestamp }}"
     data-status="{{ $order->status }}"
     data-since="{{ $order->created_at->timestamp }}"
     data-guest="{{ $order->guest_id ? '1' : '0' }}"
     data-guest-status="{{ $order->guest?->status ?? '' }}"
     data-search="{{ $searchData }}">

    <div class="ocTop">
        <div class="ocTopL">
            <span class="ocTable">{{ $order->table?->name ?? '—' }}</span>
            <div>
                <p class="ocTime">{{ ($order->started_at ?? $order->created_at)->format('H:i') }}</p>
                <p class="ocId">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</p>
                <p class="ocWait"></p>
            </div>
        </div>
        <span class="ocPill">{{ $visual['label'] }}</span>
    </div>

    @if($order->guest)
        @php
            $g = $order->guest;
            $statusClass = ['new' => '--questNew', 'regular' => '--questRegular', 'permanent' => '--questPermanent', 'vip' => '--questVip'][$g->status] ?? '';
            $initials = mb_strtoupper(collect(explode(' ', trim($g->name)))->map(fn($w) => mb_substr($w, 0, 1))->join(''));
            $n = $g->visits_count ?? 0;
        @endphp
        <div class="ocGuest">
            <div class="ocAvatar">{{ $initials }}</div>
            <div class="ocGuestBody">
                <p class="ocGuestName">{{ $g->name }}</p>
                <p class="ocGuestMeta">{{ '@' . $g->username }} · {{ $n }} {{ \App\Support\Plural::ru((int) ($n), 'заказ', 'заказа', 'заказов') }}</p>
            </div>
            <span class="ocTag {{ $statusClass }}">{{ $g->status_label }}</span>
        </div>
    @else
        <p class="ocNoGuest">Гость не указан</p>
    @endif

    <p class="ocName">{{ $order->name ?: ($primaryItem?->itemable?->name ?? 'Без названия') }}</p>

    @if($compositionLines->count())
        <div class="ocChips">
            @foreach($compositionLines->take(4) as $line)
                <span>{{ $line }}</span>
            @endforeach
        </div>
    @endif

    @if($order->guest_comment)
        <p class="ocComment">{{ $order->guest_comment }}</p>
    @endif

    <div class="ocBottom">
        <div class="ocWho">
            <p>Принял: {{ $order->creator?->name ?? '—' }}</p>
            @if($order->readyLog?->user && in_array($order->status, ['ready', 'served', 'closed']))
                <p>Готовил: {{ $order->readyLog->user->name }}</p>
            @endif
        </div>
        <button type="button" class="btnOpen" onclick="openOrderSheet({{ $order->id }})">Открыть</button>
    </div>
</div>
