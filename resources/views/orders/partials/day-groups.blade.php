@forelse($ordersByDay as $dayLabel => $dayOrders)
    <div class="ordersDayGroup">
        <h2 class="ordersSectionDay {{ $loop->first && !request()->filled('offset') ? 'active' : '' }}">{{ $dayLabel }}</h2>
        @foreach($dayOrders as $order)
            @include('orders.partials.order-card', ['order' => $order])
        @endforeach
    </div>
@empty
    @if(!request()->filled('offset'))
        <p id="ordersEmptyState" style="color:var(--info); text-align:center; padding:30px 0;">Заказов не найдено</p>
    @endif
@endforelse
@if($hasMore)
    <button type="button" class="ordersLoadMore" data-offset="{{ $nextOffset }}" style="display:block; margin:16px auto; padding:12px 22px; border-radius:20px; border:1px solid var(--line, #eadbd0); background:#fff; font:inherit; cursor:pointer;">Показать ещё</button>
@endif
