@php $statusClassMap = ['pending' => '--statusWait', 'in_progress' => '--statusActive', 'ready' => '--statusActive', 'served' => '--statusActive', 'redo' => '--statusClose', 'closed' => '--statusClose', 'cancelled' => '--statusClose']; @endphp
@foreach($orders as $order)
<div class="item {{ $statusClassMap[$order->status] ?? '' }}">
    <div class="itemCount"><span>#{{ $order->id }}</span></div>
    <div class="itemInfo itemInfo0d3d2">
        <div class="itemInfoTop">
            <p class="itemDate">{{ $order->created_at->translatedFormat('d M Y') }}</p>
            <p class="itemPlace">{{ $order->name ?: 'Классика' }} • {{ $order->table?->name ?? '—' }}</p>
        </div>
        <div class="itemInfoBtm">
            <div class="ordersCardStatus"><span></span><p>{{ \App\Models\Order::statusVisualMap()[$order->status]['label'] ?? $order->status }}</p></div>
            <div class="ordersCardDetail"><img src="/assets/img/master-done.svg" alt=""><p>{{ $order->creator?->name ?? '—' }}</p></div>
        </div>
    </div>
    <button type="button" onclick="openOrderSheet({{ $order->id }})" class="btnOpen">Открыть</button>
</div>
@endforeach

@if($hasMore)
<button type="button" class="btnOpen" style="margin-top:10px;" onclick="loadMoreGuestVisitHistory({{ $orders->last()->id }}, this)" data-guest-id="{{ request()->route('guest') }}">Загрузить ещё</button>
@endif