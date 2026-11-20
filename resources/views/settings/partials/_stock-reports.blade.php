@php
    $g = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ' '), '0'), ',') ?: '0';
    $m = fn ($v) => number_format((float) $v, 0, ',', ' ');
@endphp

<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <p class="analyticsCardTitle">Закупка (срок поставки {{ $lead }} дн. + запас {{ $buffer }} дн.)</p>
    @forelse($purchase as $supplier => $list)
    <p class="stockSupplier">{{ $supplier }}</p>
    <table class="stockTable">
        <thead><tr><th>Табак</th><th>Доступно</th><th>Хватит</th><th>Купить</th></tr></thead>
        <tbody>
        @foreach($list as $r)
        <tr><td>{{ $r->name }}</td><td>{{ $g($r->stock->available) }} г</td><td>{{ $r->days === null ? '—' : $g($r->days) . ' дн.' }}</td><td>{{ $g($r->buy) }} г{{ $r->stock->pack_size > 0 ? ' (' . (int) ceil($r->buy / $r->stock->pack_size) . ' п.)' : '' }}</td></tr>
        @endforeach
        </tbody>
    </table>
    @empty
    <p class="stockEmpty">Закупать ничего не нужно</p>
    @endforelse
</div>

<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <p class="analyticsCardTitle">Расход, г</p>
    <table class="stockTable">
        <thead><tr><th>Табак</th><th>День</th><th>Неделя</th><th>Месяц</th></tr></thead>
        <tbody>
        @foreach($rows->filter(fn ($r) => $r->d30 > 0) as $r)
        <tr><td>{{ $r->name }}</td><td>{{ $g($r->d1) }}</td><td>{{ $g($r->d7) }}</td><td>{{ $g($r->d30) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    @if($rows->filter(fn ($r) => $r->d30 > 0)->isEmpty())<p class="stockEmpty">Расхода за месяц нет</p>@endif
</div>

<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <p class="analyticsCardTitle">Ходовые за месяц</p>
    @forelse($top as $r)<div class="itemList"><span>{{ $r->name }}</span><p>{{ $g($r->d30) }} г</p></div>@empty<p class="stockEmpty">Нет данных</p>@endforelse
    <p class="analyticsCardTitle">Залежавшиеся (нет расхода 30 дней)</p>
    @forelse($stale as $r)<div class="itemList"><span>{{ $r->name }}</span><p>{{ $g($r->stock->on_hand) }} г</p></div>@empty<p class="stockEmpty">Нет</p>@endforelse
</div>

<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <p class="analyticsCardTitle">Себестоимость и маржа миксов</p>
    <table class="stockTable">
        <thead><tr><th>Микс</th><th>Себест.</th><th>Цена</th><th>Маржа</th></tr></thead>
        <tbody>
        @foreach($mixes as $mix)
        <tr data-mix-id="{{ $mix->id }}">
            <td>{{ $mix->name }}</td>
            <td>{{ $mix->cost ? $m($mix->cost) : '—' }}</td>
            <td><input type="number" class="stockPrice" value="{{ $mix->price !== null ? (int) $mix->price : '' }}" placeholder="—" onchange="stockMixPrice({{ $mix->id }}, this.value)"></td>
            <td>{{ $mix->margin !== null ? $m($mix->margin) . ' (' . round($mix->margin / max((float) $mix->price, 1) * 100) . '%)' : '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
    <p class="analyticsCardTitle">Ревизии</p>
    @if($audits->isEmpty())<p class="stockEmpty">Ревизий ещё не было</p>@else
    <table class="stockTable">
        <thead><tr><th>Табак</th><th>Учёт</th><th>Факт</th><th>Разница</th></tr></thead>
        <tbody>
        @foreach($audits as $a)
        <tr><td>{{ trim(($a->tobacco->brand->name ?? '') . ' ' . $a->tobacco->name) }}<br><small>{{ $a->created_at->format('d.m.Y') }} · {{ $a->user?->name ?? '—' }}</small></td><td>{{ $g($a->expected) }}</td><td>{{ $g($a->actual) }}</td><td>{{ $a->diff > 0 ? '+' : '' }}{{ $g($a->diff) }} г{{ $a->diff_cost !== null ? ' (' . $m($a->diff_cost) . ')' : '' }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <p class="analyticsCardTitle">Расхождения по проводившим</p>
    <table class="stockTable">
        <thead><tr><th>Кто</th><th>Ревизий</th><th>Всего, г</th><th>Сумма</th></tr></thead>
        <tbody>
        @foreach($auditByUser as $u)<tr><td>{{ $u->name }}</td><td>{{ $u->count }}</td><td>{{ $g($u->abs) }}</td><td>{{ $m($u->cost) }}</td></tr>@endforeach
        </tbody>
    </table>
    @endif
    <p class="analyticsCardTitle">Расход по мастерам за месяц</p>
    @if(collect($byMaster)->isEmpty())<p class="stockEmpty">Нет данных</p>@else
    <table class="stockTable">
        <thead><tr><th>Мастер</th><th>Расход, г</th><th>Заказов</th></tr></thead>
        <tbody>
        @foreach($byMaster as $u)<tr><td>{{ $u->name }}</td><td>{{ $g($u->g) }}</td><td>{{ $u->orders }}</td></tr>@endforeach
        </tbody>
    </table>
    @endif
</div>
