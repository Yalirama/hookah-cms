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
    @include('orders.partials.day-groups')
</section>

@endsection

@push('scripts')
@include('partials.guest-sheets')
<script src="/assets/js/guest-sheet.js?v={{ filemtime(public_path('/assets/js/guest-sheet.js')) }}"></script>
<script>
window.ORDER_WAIT = {warn: {{ (int) \App\Models\Setting::get('order_wait_warn', 5) }}, alert: {{ (int) \App\Models\Setting::get('order_wait_alert', 10) }}};
(function () {
    function tick() {
        var now = Date.now() / 1000, W = window.ORDER_WAIT;
        document.querySelectorAll('.ocCard[data-wait]:not([data-status="pending"])').forEach(function (c) {
            delete c.dataset.wait;
            var e = c.querySelector('.ocWait'); if (e) e.textContent = '';
        });
        document.querySelectorAll('.ocCard[data-status="pending"][data-since]').forEach(function (c) {
            var m = Math.floor((now - Number(c.dataset.since)) / 60);
            if (m >= W.alert) c.dataset.wait = 'alert'; else if (m >= W.warn) c.dataset.wait = 'warn'; else delete c.dataset.wait;
            var e = c.querySelector('.ocWait'); if (e) e.textContent = m >= 60 ? 'ждёт ' + Math.floor(m / 60) + ' ч ' + (m % 60) + ' мин' : (m >= 1 ? 'ждёт ' + m + ' мин' : '');
        });
    }
    tick();
    setInterval(tick, 15000);
    document.addEventListener('visibilitychange', tick);
})();
</script>
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

    function filterLocal() {
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
    }

    function queryParams(offset) {
        const p = new URLSearchParams();
        const q = document.getElementById('ordersSearchInput').value.trim();
        if (q) p.set('search', q);
        if (activeStatus) p.set('status', activeStatus);
        if (activeGuest !== '') p.set('with_guest', activeGuest);
        if (activeGuestStatus) p.set('guest_status', activeGuestStatus);
        if (offset) p.set('offset', offset);
        return p;
    }

    let reqSeq = 0;
    function reloadList() {
        const seq = ++reqSeq;
        fetch('/orders?' + queryParams(0), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(r => r.text()).then(html => {
                if (seq !== reqSeq) return;
                document.getElementById('ordersSection').innerHTML = html;
            });
    }

    window.filterOrders = function () {
        filterLocal();
        reloadList();
    };

    document.getElementById('ordersSection').addEventListener('click', function (e) {
        const btn = e.target.closest('.ordersLoadMore');
        if (!btn) return;
        btn.disabled = true;
        fetch('/orders?' + queryParams(btn.dataset.offset), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(r => r.text()).then(html => {
                const box = document.createElement('div');
                box.innerHTML = html;
                const section = document.getElementById('ordersSection');
                btn.remove();
                const last = [...section.querySelectorAll('.ordersDayGroup')].pop();
                const first = box.querySelector('.ordersDayGroup');
                if (last && first && last.querySelector('.ordersSectionDay').textContent.trim() === first.querySelector('.ordersSectionDay').textContent.trim()) {
                    first.querySelectorAll('.ordersCard').forEach(c => last.appendChild(c));
                    first.remove();
                }
                while (box.firstChild) section.appendChild(box.firstChild);
            });
    });

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
            filterLocal();
        });
    }

    function initOrdersEcho() {
        window.Echo.channel('orders').listen('.order.updated', (data) => upsertOrderCard(data.id));
        let wasDown = false;
        window.Echo.connector.pusher.connection.bind('state_change', ({current}) => {
            if (current === 'connected' && wasDown) { wasDown = false; reloadList(); }
            else if (current !== 'connected') wasDown = true;
        });
        document.addEventListener('visibilitychange', () => { if (!document.hidden) reloadList(); });
    }
    if (window.Echo) initOrdersEcho();
    else window.addEventListener('echo-ready', initOrdersEcho);
})();
</script>
@endpush