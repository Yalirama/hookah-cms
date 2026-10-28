@extends('layouts.app')

@section('main-class', '_ordersShow')

@section('content')

<header>
    <div class="headerTop">
        <h1>Заказы</h1>

        @include('partials.shift-toggle')

        @role('admin')
        <a href="{{ route('settings.index') }}" class="headerUser">
            <img src="/assets/img/brands/mix.svg" alt="">
        </a>
        @else
        <a href="{{ route('wiki.index') }}" class="headerUser">
            <img src="/assets/img/brands/mix.svg" alt="">
        </a>
        @endrole
    </div>

    <div class="headerStats">
        <div class="headerStat"><p>{{ $stats['today'] }}</p><span>За сегодня</span></div>
        <div class="headerStat"><p>{{ $stats['week'] }}</p><span>За неделю</span></div>
        <div class="headerStat"><p>{{ $stats['month'] }}</p><span>За месяц</span></div>
        <div class="headerStat"><p>{{ $stats['total'] }}</p><span>Всего</span></div>
    </div>
</header>

<div class="topNav">
    <div class="search">
        <img src="/assets/img/search-icon.svg" alt="">
        <input type="text" id="ordersSearchInput" placeholder="Гость, мастер, микс, id, заказ...">
    </div>

    <div class="filters">
        <div class="filtersRow" style="display:grid; grid-template-columns:repeat(3, 1fr);">
            <div class="defaultForm customSelect" id="ordersStatusSelect">
                <div class="customSelectValue" data-placeholder="Все статусы">Все статусы</div>
                <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 1l4 4 4-4"></path>
                </svg>
                <input type="hidden" id="ordersStatusValue" value="">
                <div class="customSelectDropdown">
                    <div class="customSelectOption --active" data-value="">Все статусы</div>
                    @foreach(['pending'=>'Новый','in_progress'=>'В работе','ready'=>'Готов','served'=>'У гостя','redo'=>'Переделка','closed'=>'Завершён','cancelled'=>'Отменён'] as $val => $label)
                        <div class="customSelectOption" data-value="{{ $val }}">{{ $label }}</div>
                    @endforeach
                </div>
            </div>

            <div class="defaultForm customSelect" id="ordersGuestSelect">
                <div class="customSelectValue" data-placeholder="Все заказы">Все заказы</div>
                <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 1l4 4 4-4"></path>
                </svg>
                <input type="hidden" id="ordersGuestValue" value="">
                <div class="customSelectDropdown">
                    <div class="customSelectOption --active" data-value="">Все заказы</div>
                    <div class="customSelectOption" data-value="1">С гостем</div>
                    <div class="customSelectOption" data-value="0">Без гостя</div>
                </div>
            </div>

            <div class="defaultForm customSelect" id="ordersGuestStatusSelect">
                <div class="customSelectValue" data-placeholder="Все гости">Все гости</div>
                <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 1l4 4 4-4"></path>
                </svg>
                <input type="hidden" id="ordersGuestStatusValue" value="">
                <div class="customSelectDropdown">
                    <div class="customSelectOption --active" data-value="">Все гости</div>
                    <div class="customSelectOption" data-value="vip">VIP</div>
                    <div class="customSelectOption" data-value="permanent">Постоянный</div>
                    <div class="customSelectOption" data-value="regular">Регулярный</div>
                    <div class="customSelectOption" data-value="new">Новый</div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="ordersSection" id="ordersSection">
    @forelse($ordersByDay as $dayLabel => $dayOrders)
        <div class="ordersDayGroup">
            <h2 class="ordersSectionDay {{ $loop->first ? 'active' : '' }}">{{ $dayLabel }}</h2>
            @foreach($dayOrders as $order)
                @include('orders.partials.order-card', ['order' => $order])
            @endforeach
        </div>
    @empty
        <p id="ordersEmptyState" style="color:var(--info); text-align:center; padding:30px 0;">Заказов не найдено</p>
    @endforelse
</section>

@endsection

@push('scripts')
@include('partials.guest-sheets')
<script src="/assets/js/guest-sheet.js?v={{ filemtime(public_path('/assets/js/guest-sheet.js')) }}"></script>
<script>
(function () {
    let activeStatus = '', activeGuest = '', activeGuestStatus = '';

    document.addEventListener('customselect:change', function (e) {
        if (e.target.id === 'ordersStatusSelect') activeStatus = e.detail.value;
        else if (e.target.id === 'ordersGuestSelect') activeGuest = e.detail.value;
        else if (e.target.id === 'ordersGuestStatusSelect') activeGuestStatus = e.detail.value;
        else return;
        filterOrders();
    });

    let searchTimeout;
    document.getElementById('ordersSearchInput').addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterOrders, 200);
    });

    window.filterOrders = function () {
        const q = document.getElementById('ordersSearchInput').value.trim().toLowerCase();
        document.querySelectorAll('.ordersDayGroup').forEach(group => {
            let visible = 0;
            group.querySelectorAll('.ordersCard').forEach(card => {
                const show = (!q || card.dataset.search.includes(q))
                    && (!activeStatus || card.dataset.status === activeStatus)
                    && (activeGuest === '' || card.dataset.guest === activeGuest)
                    && (!activeGuestStatus || card.dataset.guestStatus === activeGuestStatus);
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            group.style.display = visible ? '' : 'none';
        });
    };

    // Real-time
    function upsertOrderCard(orderId) {
        fetch(`/orders/${orderId}/card`).then(r => r.text()).then(html => {
            const existing = document.querySelector(`.ordersCard[data-order-id="${orderId}"]`);
            if (existing) {
                existing.outerHTML = html;
            } else {
                const emptyState = document.getElementById('ordersEmptyState');
                if (emptyState) emptyState.remove();
                const isToday = g => g.querySelector('.ordersSectionDay')?.textContent.trim().startsWith('Сегодня');
                let group = [...document.querySelectorAll('.ordersDayGroup')].find(isToday);
                if (!group) {
                    document.querySelectorAll('.ordersSectionDay.active').forEach(h => h.classList.remove('active'));
                    group = document.createElement('div');
                    group.className = 'ordersDayGroup';
                    group.innerHTML = '<h2 class="ordersSectionDay active">Сегодня</h2>';
                    document.getElementById('ordersSection').prepend(group);
                }
                group.querySelector('.ordersSectionDay').insertAdjacentHTML('afterend', html);
            }
            filterOrders();
        });
    }

    function initOrdersEcho() {
        window.Echo.channel('orders').listen('.order.updated', (data) => upsertOrderCard(data.id));
    }
    if (window.Echo) initOrdersEcho();
    else window.addEventListener('echo-ready', initOrdersEcho);
})();
</script>
@endpush