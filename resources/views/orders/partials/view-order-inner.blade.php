@php
    $visual = \App\Models\Order::statusVisualMap()[$order->status] ?? ['card' => 'statusWait', 'top' => 'orderCreated', 'label' => $order->status];
    $isActive = $order->isActive();
    $isAdmin = auth()->user()->hasRole('admin');
    $canEdit = $isActive || $isAdmin;

    $nextStatus = ['pending' => 'in_progress', 'in_progress' => 'ready', 'ready' => 'served', 'redo' => 'in_progress'][$order->status] ?? null;
    $nextLabel  = ['pending' => 'В работу', 'in_progress' => 'Заказ готов', 'ready' => 'Заказ у гостя', 'redo' => 'В работу'][$order->status] ?? '';

    $historyIconClass = function ($log) {
        if ($log->action === 'created') return '--orderCreated';
        if ($log->action === 'item_added') return '--orderAdd';
        if ($log->action === 'item_removed') return '--orderRemove';
        if ($log->action === 'serviced') return '--orderService';
        if (in_array($log->action, ['status_changed', 'rolled_back'])) {
            $s = $log->new_value['status'] ?? '';
            if (in_array($s, ['closed', 'cancelled'])) return '--orderClosed';
            if ($s === 'served') return '--orderActive';
            return '--orderDone';
        }
        return '--orderActive';
    };

    $statusChangeLabels = ['pending' => 'Ожидание', 'in_progress' => 'В работе', 'ready' => 'Готово', 'served' => 'У гостя', 'closed' => 'Завершён', 'cancelled' => 'Отменён', 'redo' => 'Переделка'];

    $statusLogs = $order->logs->whereIn('action', ['created', 'status_changed', 'serviced', 'rolled_back']);
    $readyLog = $order->logs
        ->whereIn('action', ['status_changed', 'rolled_back'])
        ->where(fn($log) => ($log->new_value['status'] ?? null) === 'ready')
        ->sortByDesc('id')
        ->first();
        
    $itemGuestTableLogs = $order->logs->whereIn('action', ['item_added', 'item_removed', 'guest_changed', 'table_changed', 'guest_comment_changed']);
    $masterLogs = $order->logs->whereIn('action', ['master_changed']);
    $logsById = $order->logs->keyBy('id');
    $activeItems = $order->items->filter(fn($i) => !$i->trashed());
    $tableNamesById = \App\Models\Table::withTrashed()->pluck('name', 'id');

    $lastStatusChangeLog = $order->logs
        ->whereIn('action', ['status_changed', 'rolled_back'])
        ->sortByDesc('id')
        ->first();

    $currentStatusStartedAt = $lastStatusChangeLog?->created_at ?? $order->created_at;

    $activeItemKeys = $activeItems->map(fn($i) => ($i->itemable_type === \App\Models\Mix::class ? 'mix' : 'tobacco') . '_' . $i->itemable_id)->unique();
@endphp

<div class="actionTabViewOrderTop">
    <div class="actionTabViewOrderTopBody --{{ $visual['top'] }}">
        <div class="actionTabViewOrderTopLeft">
            <div class="actionTabViewOrderTopStatus">
                <div class="actionTabViewOrderTopStatusCircle"><span></span></div>
                <p>{{ $visual['label'] }}</p>
            </div>
            <div class="actionTabViewOrderTopBodyItem">
                <img src="/assets/img/edit-icon.svg" alt="">
                <p>Создан в {{ $order->created_at->format('H:i') }}</p>
            </div>
            <div class="actionTabViewOrderTopBodyItem">
                <img src="/assets/img/table-icon.svg" alt="">
                <p>{{ $order->table?->name ?? '—' }}</p>
            </div>
            <div class="actionTabViewOrderTopBodyItem">
                <img src="/assets/img/master-create.svg" alt="">
                <p>{{ $order->creator?->name ?? '—' }}</p>
            </div>
            @if($readyLog)
            <div class="actionTabViewOrderTopBodyItem">
                <img src="/assets/img/master-done.svg" alt="">
                <p>{{ $readyLog->user?->name ?? '—' }}</p>
            </div>
            @endif
        </div>

        @if($isActive)
            <div class="x8s0s">
                <span class="actionTabViewOrderTopTimer timer-counter" data-start="{{ $currentStatusStartedAt->timestamp }}">00:00:00</span>
                <span class="actionTabViewOrderTopTimer actionTabViewOrderTopTimerTotal timer-counter" data-start="{{ $order->created_at->timestamp }}">00:00:00</span>
            </div>
        @else
            <span class="actionTabViewOrderTopTimer">{{ $order->closed_at ? $order->created_at->diff($order->closed_at)->format('%H:%I:%S') : '—' }}</span>
        @endif
    </div>

   @if($isActive)
    <div class="actionTabViewOrderCard actionTabViewOrderCardActionWrapper">

        <form method="POST" action="{{ route('orders.status', $order) }}" class="actionTabViewOrderCardAction">
            @csrf
            <input type="hidden" name="from" value="{{ $order->status }}">
            <div class="defaultForm"><input type="text" name="comment" placeholder="Добавить комментарий"></div>
            <div class="actionTabViewOrderCardActionButtons">
                @if($nextStatus)
                <button type="submit" name="status" value="{{ $nextStatus }}" class="actionTabViewOrderCardButton --orderDone" {{ $activeItems->isEmpty() ? 'disabled title="Добавьте состав перед сменой статуса"' : '' }}>{{ $nextLabel }}</button>
                @endif

                @if($order->status === 'served')
                <button type="submit" formaction="{{ route('orders.service', $order) }}" class="actionTabViewOrderCardButton --orderService">Сервис</button>
                <button type="submit" name="status" value="closed" class="actionTabViewOrderCardButton --orderDone">Завершить</button>
                <button type="submit" name="status" value="redo" class="actionTabViewOrderCardButton --orderCancel">Переделка</button>
                @endif

                @if($order->status !== 'served')
                <button type="submit" name="status" value="cancelled" class="actionTabViewOrderCardButton --orderCancel" onclick="return confirm('Отменить заказ?')">Отменить заказ</button>
                @endif
            </div>
        </form>

    </div>
    @endif
</div>

{{-- Гость --}}
<div class="actionTabViewOrderCard">
    <div class="actionTabViewOrderCardTop">
        <p class="actionTabTitle">гость</p>
        @if($order->guest)
        <button type="button" onclick="toggleDetachGuestConfirm()"><img src="/assets/img/edit-icon-gold.svg" alt=""><span>Открепить</span></button>
        @endif
    </div>

    @if($order->guest)
        @php
            $g = $order->guest;
            $statusClass = ['new' => '--questNew', 'regular' => '--questRegular', 'permanent' => '--questPermanent', 'vip' => '--questVip'][$g->status] ?? '';
            $initials = mb_strtoupper(collect(explode(' ', trim($g->name)))->map(fn($w) => mb_substr($w, 0, 1))->join(''));
        @endphp
        <div class="actionTabViewOrderCardBtm">
            <div class="ordersCardTop">
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
            </div>
            <button type="button" class="btnOpen" onclick="openGuestSheet({{ $g->id }})">Открыть</button>
        </div>

        {{-- Панель подтверждения открепления --}}
        <div class="detachGuestConfirm" id="detachGuestConfirm">
            <form method="POST" action="{{ route('orders.detach-guest', $order) }}" class="defaultForm o042f">
                @csrf @method('DELETE')
                <span>Открепить гостя?</span>
                <div>
                    <button type="button" onclick="toggleDetachGuestConfirm()" class="btnCancel">Отмена</button>
                    <button type="submit" class="btnConfirm">Открепить</button>
                </div>
            </form>
        </div>
    @else
        <div class="r0d21">
            <div class="search">
                <img src="/assets/img/search-icon.svg" alt="">
                <input type="text" id="viewOrderGuestSearch" placeholder="Имя, номер телефона, username..." autocomplete="off">
            </div>
        </div>    
        <div class="actionTabSearchSectionResult" id="viewOrderGuestResults"></div>

        <form method="POST" action="{{ route('orders.attach-guest', $order) }}" id="viewOrderAttachGuestForm">
            @csrf
            <input type="hidden" name="guest_id" id="viewOrderGuestIdInput">
        </form>
    @endif
</div>

{{-- Комментарий гостя --}}
<div class="actionTabViewOrderCard">
    <form method="POST" action="{{ route('orders.guest-comment', $order) }}">
        @csrf @method('PATCH')
        <div class="actionTabViewOrderCardTop">
            <p class="actionTabTitle">комментарий гостя</p>
            @if($canEdit)
            <button type="submit"><img src="/assets/img/edit-icon-gold.svg" alt=""><span>{{ $order->guest_comment ? 'Изменить' : 'Добавить' }}</span></button>
            @endif
        </div>
        <div class="actionTabViewOrderCardBtm">
            <div class="defaultForm">
                <input type="text" name="guest_comment" placeholder="Комментарий гостя" value="{{ $order->guest_comment }}" {{ $canEdit ? '' : 'disabled' }}>
            </div>
        </div>
    </form>
</div>

{{-- Состав --}}
<div class="actionTabViewOrderCard" data-section="items">
    <div class="actionTabViewOrderCardTop">
        <p class="actionTabTitle">состав</p>
        @if($canEdit)
        <button type="button" onclick="toggleAddItemForm(this)"><img src="/assets/img/edit-icon-gold.svg" alt=""><span>Добавить</span></button>
        @endif
    </div>

    <div class="actionTabViewOrderCardBtm actionTabViewOrderCardBtmColumn">
        @forelse($activeItems as $item)
            <div class="actionTabViewOrderProductList">
                <div class="actionTabViewOrderProductListLeft">
                    @php
                        $itemImage = $item->itemable_type === \App\Models\Mix::class
                            ? '/assets/img/brands/mix.svg'
                            : ($item->itemable?->brand?->image_url ?? '/assets/img/brands/product-img.svg');
                    @endphp
                    <img src="{{ $itemImage }}" alt="">
                    <div class="actionTabViewOrderProductListBody">
                        @php
                            $itemName = $item->itemable?->name ?? 'Удалённая позиция';
                            if ($item->itemable_type === \App\Models\Tobacco::class && $item->itemable?->brand) {
                                $itemName = $item->itemable->brand->name . ' - ' . $itemName;
                            }
                        @endphp
                        <p>{{ $itemName }}</p>
                        <span>{{ $item->addedBy?->name ?? '—' }} · {{ $item->created_at->format('d.m H:i') }}{{ $item->grams ? ' · ' . $item->grams : '' }}</span>
                    </div>
                </div>
                @if($canEdit)
                <button type="button" onclick="toggleRemoveItemForm({{ $item->id }})" class="actionTabViewOrderProductListRight v042as"><img src="/assets/img/close-icon.svg" alt=""></button>
                @endif
            </div>
            @if($canEdit)
            <div class="removeItemForm" id="removeItemForm{{ $item->id }}">
                <form method="POST" action="{{ route('orders.items.remove', [$order, $item]) }}" class="defaultForm">
                    @csrf @method('DELETE')
                    <input class="t53d2" type="text" name="comment" placeholder="Причина удаления...">
                    <button type="submit" class="btnConfirm">Удалить</button>
                </form>
            </div>
            @endif
        @empty
            <p style="color:var(--info); font-size:calc(12vw / var(--resizer) * 100); padding: calc(6vw / var(--resizer) * 100) 0;">Нет позиций</p>
        @endforelse
    </div>

    @if($canEdit)
    <div id="addItemFormWrap" style="display:none;">
        <form method="POST" action="{{ route('orders.items.add', $order) }}" id="addItemsForm">
            @csrf
            <div class="v8ds">
                <div class="search" style="margin-top: calc(8vw / var(--resizer) * 100);">
                    <img src="/assets/img/search-icon.svg" alt="">
                    <input type="text" id="addItemSearch" placeholder="Бренд, название, вкус, тег...">
                </div>
            </div>

            <div class="actionTabSearchSectionResult" id="addItemResults">
                @foreach($mixes as $mix)
                    @php $mixAlreadyAdded = $activeItemKeys->contains('mix_' . $mix->id); @endphp
                    <div class="ordersCardTop addItemOption {{ $mixAlreadyAdded ? '--alreadyAdded' : '' }}" data-name="{{ mb_strtolower($mix->name . ' ' . $mix->ingredients->pluck('tobacco.name')->filter()->join(' '), 'UTF-8') }}" @if(!$mixAlreadyAdded) onclick="toggleItemSelect(this, 'mix', {{ $mix->id }}, '{{ addslashes($mix->name) }}')" @endif>
                        <div class="ordersCardUser">
                            <div class="ordersCardUserImg"><img src="/assets/img/brands/mix.svg" alt=""></div>
                            <div class="ordersCardUserInfo">
                                <div class="actionTabViewOrderProductListBody">
                                    <p>{{ $mix->name }}</p>
                                    <span>{{ $mix->ingredients->pluck('tobacco.name')->filter()->join(', ') }}</span>
                                </div>
                            </div>
                        </div>
                        @if($mixAlreadyAdded)
                        <span class="addItemAlreadyBadge">Уже в составе</span>
                        @endif
                    </div>
                @endforeach
                @foreach($tobaccos as $tobacco)
                    @php $tobaccoAlreadyAdded = $activeItemKeys->contains('tobacco_' . $tobacco->id); @endphp
                    <div class="ordersCardTop addItemOption {{ $tobaccoAlreadyAdded ? '--alreadyAdded' : '' }}" data-name="{{ mb_strtolower(($tobacco->brand?->name ?? '') . ' ' . $tobacco->name, 'UTF-8') }}" @if(!$tobaccoAlreadyAdded) onclick="toggleItemSelect(this, 'tobacco', {{ $tobacco->id }}, '{{ addslashes(($tobacco->brand?->name ?? '') . ' ' . $tobacco->name) }}')" @endif>
                        <div class="ordersCardUser">
                            <div class="ordersCardUserImg"><img src="{{ $tobacco->brand?->image_url ?? '/assets/img/brands/product-img.svg' }}" alt=""></div>
                            <div class="ordersCardUserInfo">
                                <div class="actionTabViewOrderProductListBody">
                                    <p>{{ trim(($tobacco->brand?->name ?? '') . ' ' . $tobacco->name) }}</p>
                                </div>
                            </div>
                        </div>
                        @if($tobaccoAlreadyAdded)
                        <span class="addItemAlreadyBadge">Уже в составе</span>
                        @endif
                    </div>
                @endforeach
            </div>

            <p class="actionTabSearchSectionResultLists" id="addItemSelectedLabel" style="display:none;"><span>Выбрано:</span> <span id="addItemSelectedNames"></span></p>

            <div id="addItemHiddenInputs"></div>

            <div class="v9ds">
                <div class="defaultForm" style="margin-top: calc(10vw / var(--resizer) * 100);">
                    <input type="text" name="comment" placeholder="Комментарий к добавлению (необязательно)">
                </div>
            </div>
           
            <div class="actionTabActions actionTabActions--0v31s">
                <div class="actionTabActionsButtons">
                    <button type="submit" class="actionTabActionsButton --buttonAccent">Добавить выбранные</button>
                </div>
            </div>
        </form>
    </div>
    @endif
</div>

{{-- История статуса --}}
<div class="actionTabViewOrderCard">
    <div class="actionTabViewOrderCardTop"><p class="actionTabTitle">история статуса</p></div>
    <div class="actionTabViewOrderCardBtm actionTabViewOrderCardBtmColumn">
        @php $statusTitleCounters = []; @endphp
        @forelse($statusLogs as $log)
            @php
                if (in_array($log->action, ['status_changed', 'rolled_back'])) $baseTitle = $statusChangeLabels[$log->new_value['status'] ?? ''] ?? $log->action;
                elseif ($log->action === 'serviced') $baseTitle = 'Обслуживание';
                elseif ($log->action === 'created') $baseTitle = 'Заказ создан';
                else $baseTitle = $log->action;

                $statusTitleCounters[$baseTitle] = ($statusTitleCounters[$baseTitle] ?? 0) + 1;
                $title = $statusTitleCounters[$baseTitle] > 1 ? "{$baseTitle} #{$statusTitleCounters[$baseTitle]}" : $baseTitle;
            @endphp
            <div class="actionTabHistoryItem {{ $historyIconClass($log) }}">
                <div class="actionTabHistoryItemLeft">
                    <div class="actionTabHistoryItemCircle"><span></span></div>
                    <div class="actionTabHistoryItemInfo">
                        <p class="actionTabHistoryItemTitle">{{ $title }}</p>
                        <div class="actionTabHistoryItemRow">
                            <img src="/assets/img/master-done.svg" alt="">
                            <span>{{ $log->user?->name ?? '—' }}</span>
                        </div>
                        @if($log->comment)
                        <div class="actionTabHistoryItemComment"><p>{{ $log->comment }}</p></div>
                        @endif
                    </div>
                </div>
                <div class="actionTabHistoryItemTime">
                    <span>{{ $log->created_at->format('H:i') }}</span>
                    <span>{{ $log->created_at->format('d.m.Y') }}</span>
                </div>
            </div>
        @empty
            <p style="color:var(--info); font-size:calc(12vw / var(--resizer) * 100);">Нет действий</p>
        @endforelse
    </div>
</div>

{{-- Длительность по статусам --}}
@php
    $statusHistories = $order->statusHistories->values();
    $statusDurationLabels = ['pending' => 'Ожидание', 'in_progress' => 'В работе', 'ready' => 'Готово', 'served' => 'У гостя', 'closed' => 'Завершён', 'cancelled' => 'Отменён', 'redo' => 'Переделка'];
    $statusDotClass = function ($status) {
        if (in_array($status, ['closed', 'cancelled'])) return '--orderClosed';
        if ($status === 'served') return '--orderActive';
        if ($status === 'pending') return '--orderCreated';
        return '--orderDone';
    };
    $durationTitleCounters = [];
@endphp
<div class="actionTabViewOrderCard actionTabViewOrderCardHide">
    <div class="actionTabViewOrderCardTop" onclick="toggleCollapse(this)">
        <p class="actionTabTitle">длительность по статусам</p>
        <button type="button" class="arrowDown"><img src="/assets/img/arrow-down.svg" alt=""></button>
    </div>
    <div class="actionTabViewOrderCardBtm actionTabViewOrderCardBtmColumn">
        @forelse($statusHistories as $i => $history)
            @php
                $isLast = $i === $statusHistories->count() - 1;
                $isFinal = in_array($history->status, ['closed', 'cancelled']);
                $endTime = $isLast
                    ? ($order->isActive() ? now() : ($order->closed_at ?? now()))
                    : $statusHistories[$i + 1]->started_at;
                $duration = $history->started_at->diff($endTime);
                $durationStr = sprintf('%02d:%02d:%02d', $duration->h + $duration->days * 24, $duration->i, $duration->s);

                $baseTitle = $statusDurationLabels[$history->status] ?? $history->status;
                $durationTitleCounters[$baseTitle] = ($durationTitleCounters[$baseTitle] ?? 0) + 1;
                $title = $durationTitleCounters[$baseTitle] > 1 ? "{$baseTitle} #{$durationTitleCounters[$baseTitle]}" : $baseTitle;

                if ($isFinal) {
                    $totalDuration = $order->created_at->diff($history->started_at);
                    $totalStr = sprintf('%02d:%02d:%02d', $totalDuration->h + $totalDuration->days * 24, $totalDuration->i, $totalDuration->s);
                }
            @endphp
            <div class="actionTabHistoryItem {{ $statusDotClass($history->status) }}">
                <div class="actionTabHistoryItemLeft">
                    <div class="actionTabHistoryItemCircle"><span></span></div>
                    <div class="actionTabHistoryItemInfo">
                        <p class="actionTabHistoryItemTitle">{{ $title }}</p>
                        <div class="actionTabHistoryItemRow">
                            <span>{{ $history->started_at->format('H:i:s') }}</span>
                        </div>
                    </div>
                </div>
                <div class="actionTabHistoryItemTime">
                    @if($isFinal)
                        <span>{{ $totalStr }}</span>
                    @elseif($isLast && $order->isActive())
                        <span class="timer-counter" data-start="{{ $history->started_at->timestamp }}">00:00:00</span>
                    @else
                        <span>{{ $durationStr }}</span>
                    @endif
                </div>
            </div>
        @empty
            <p style="color:var(--info); font-size:calc(12vw / var(--resizer) * 100);">Нет данных</p>
        @endforelse
    </div>
</div>

{{-- Сменить стол --}}
<div class="actionTabViewOrderCard actionTabViewOrderCardHide">
    <div class="actionTabViewOrderCardTop" onclick="toggleCollapse(this)">
        <p class="actionTabTitle">сменить стол</p>
        <button type="button" class="arrowDown"><img src="/assets/img/arrow-down.svg" alt=""></button>
    </div>
    <form class="masterChangeForm--c0d2q" method="POST" action="{{ route('orders.table', $order) }}">
        @csrf
        <div class="actionTabViewOrderCardBtm actionTabViewOrderCardBtmColumn2">
            <div class="defaultForm customSelect" id="changeTableSelect">
                <div class="customSelectValue" data-placeholder="Сменить стол">{{ $order->table?->name ?? 'Сменить стол' }}</div>
                <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 1l4 4 4-4"></path>
                </svg>
                <input type="hidden" name="table_id" id="changeTableInput" value="{{ $order->table_id }}">

                <div class="customSelectDropdown customSelectDropdown--b0vd4">
                    @foreach($tables as $table)
                        <div class="customSelectOption {{ $order->table_id == $table->id ? '--active' : '' }}" data-value="{{ $table->id }}">{{ $table->name }}</div>
                    @endforeach
                </div>
            </div>
            <div class="defaultForm"><input type="text" name="comment" placeholder="Добавить комментарий"></div>
        </div>
        <div class="actionTabHistorySUV">
            <button type="submit"><img src="/assets/img/edit-icon-gold.svg" alt=""><span>Сменить стол</span></button>
        </div>
    </form>
</div>

{{-- Изменить мастера действия --}}
<div class="actionTabViewOrderCard actionTabViewOrderCardHide">
    <div class="actionTabViewOrderCardTop" onclick="toggleCollapse(this)">
        <p class="actionTabTitle">изменить мастера действия</p>
        <button type="button" class="arrowDown"><img src="/assets/img/arrow-down.svg" alt=""></button>
    </div>
    <form class="masterChangeForm--c0d1q" method="POST" id="masterChangeForm" action="">
        @csrf @method('PATCH')
        <div class="actionTabViewOrderCardBtm actionTabViewOrderCardBtmColumn2">
            <div class="defaultForm customSelect" id="masterLogSelect">
                <div class="customSelectValue" data-placeholder="Выбрать действие">Выбрать действие</div>
                <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 1l4 4 4-4"></path>
                </svg>
                <input type="hidden" name="log_id" id="masterLogInput" required>

                <div class="customSelectDropdown customSelectDropdown--b0vd3">
                    @php $logCounters = [];
                    $selectActionLabels = ['created'=>'Создан','status_changed'=>'Статус изменён','serviced'=>'Обслуживание','table_changed'=>'Стол изменён','item_added'=>'Позиция добавлена','item_removed'=>'Позиция удалена','rolled_back'=>'Откат статуса','guest_changed'=>'Гость изменён','guest_comment_changed'=>'Комментарий гостя изменён'];
                    @endphp
                    @foreach($order->logs->whereNotIn('action', ['master_changed']) as $log)
                        @php
                            if (in_array($log->action, ['status_changed', 'rolled_back'])) $base = $statusChangeLabels[$log->new_value['status'] ?? ''] ?? $log->action;
                            elseif ($log->action === 'serviced') $base = 'Обслуживание';
                            else $base = $selectActionLabels[$log->action] ?? $log->action;
                            $logCounters[$base] = ($logCounters[$base] ?? 0) + 1;
                            $lbl = ($logCounters[$base] > 1 ? "{$base} #{$logCounters[$base]}" : $base) . ' — ' . ($log->user?->name ?? '—') . ' (' . $log->created_at->format('H:i:s') . ')';
                        @endphp
                        <div class="customSelectOption" data-value="{{ $log->id }}" data-url="{{ route('orders.logs.master', [$order, $log]) }}">{{ $lbl }}</div>
                    @endforeach
                </div>
            </div>

            <div class="defaultForm customSelect" id="masterUserSelect">
                <div class="customSelectValue" data-placeholder="Выбрать мастера">Выбрать мастера</div>
                    <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 1l4 4 4-4"></path>
                    </svg>
                    <input type="hidden" name="user_id" id="masterUserInput" required>

                    <div class="customSelectDropdown customSelectDropdown--b0vd5">
                        @foreach($masters as $master)
                            <div class="customSelectOption" data-value="{{ $master->id }}">{{ $master->name }}</div>
                        @endforeach
                    </div>
                </div>
                <div class="defaultForm"><input type="text" name="comment" placeholder="Комментарий (необязательно)"></div>
            </div>
        <div class="actionTabHistorySUV">
            <button type="submit"><img src="/assets/img/edit-icon-gold.svg" alt=""><span>Сохранить</span></button>
        </div>
    </form>
</div>

{{-- История состава/гостя/стола --}}
@if($itemGuestTableLogs->count())
<div class="actionTabViewOrderCard actionTabViewOrderCardHide">
    <div class="actionTabViewOrderCardTop" onclick="toggleCollapse(this)">
        <p class="actionTabTitle">история состава/гостя/стола</p>
        <button type="button" class="arrowDown"><img src="/assets/img/arrow-down.svg" alt=""></button>
    </div>
    <div class="actionTabViewOrderCardBtm actionTabViewOrderCardBtmColumn actionTabHistoryItemLogsBlock">
        @foreach($itemGuestTableLogs as $log)
            @php
                if ($log->action === 'item_added') { $subtitle = $log->new_value['item_name'] ?? '—'; $dotClass = '--logAdd'; }
                elseif ($log->action === 'item_removed') { $subtitle = $log->old_value['item_name'] ?? '—'; $dotClass = '--logRemove'; }
                elseif ($log->action === 'guest_changed') {
                    $subtitle = $log->new_value['guest_name'] ?? ($log->old_value['guest_name'] ?? '—') . ' (откреплён)';
                    $dotClass = $log->new_value ? '--logAdd' : '--logRemove';
                } elseif ($log->action === 'guest_comment_changed') {
                    $oldComment = $log->old_value['comment'] ?? null;
                    $newComment = $log->new_value['comment'] ?? null;
                    if (!$oldComment && $newComment) { $subtitle = 'Комментарий: ' . $newComment; $dotClass = '--logAdd'; }
                    elseif ($oldComment && !$newComment) { $subtitle = 'Комментарий убран'; $dotClass = '--logRemove'; }
                    else { $subtitle = 'Комментарий: ' . $newComment; $dotClass = '--logChanged'; }
                } else {
                    $oldTableName = $tableNamesById[$log->old_value['table_id'] ?? null] ?? '—';
                    $newTableName = $tableNamesById[$log->new_value['table_id'] ?? null] ?? '—';
                    $subtitle = $oldTableName . ' → ' . $newTableName;
                    $dotClass = '--logChanged';
                }
            @endphp
            <div class="actionTabHistoryItem {{ $dotClass }}">
                <div class="actionTabHistoryItemLeft">
                    <div class="actionTabHistoryItemCircle"><span></span></div>
                        <div class="actionTabHistoryItemInfo">
                            <p class="actionTabHistoryItemSubtitle">{{ $subtitle }}</p>
                            <div class="actionTabHistoryItemRow">
                                <img src="/assets/img/master-done.svg" alt="">
                                <span>{{ $log->user?->name ?? '—' }}</span>
                            </div>
                            @if($log->comment)<div class="actionTabHistoryItemComment"><p>{{ $log->comment }}</p></div>@endif
                        </div>
                    </div>
                    <div class="actionTabHistoryItemTime">
                        <span>{{ $log->created_at->format('H:i') }}</span>
                        <span>{{ $log->created_at->format('d.m.Y') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    <div class="actionTabHistorySUV actionTabHistorySUV--0vd31"><span class="--isBlue">Добавил</span> - <span class="--isRed">Убрал</span> - <span class="--isGold">Сменил</span> </div>
</div>
@endif

{{-- История действий мастеров --}}
@if($masterLogs->count())
<div class="actionTabViewOrderCard actionTabViewOrderCardHide">
    <div class="actionTabViewOrderCardTop" onclick="toggleCollapse(this)">
        <p class="actionTabTitle">история действий мастеров</p>
        <button type="button" class="arrowDown"><img src="/assets/img/arrow-down.svg" alt=""></button>
    </div>
    <div class="actionTabViewOrderCardBtm actionTabViewOrderCardBtmColumn">
        @foreach($masterLogs as $log)
            @php $changedLog = $logsById[$log->new_value['log_id'] ?? null] ?? null; @endphp
            <div class="actionTabHistoryItem --orderClosed">
                <div class="actionTabHistoryItemLeft">
                    <div class="actionTabHistoryItemCircle"><span></span></div>
                    <div class="actionTabHistoryItemInfo">
                        <p class="actionTabHistoryItemSubtitle">
                            Смена мастера{{ $changedLog ? ': ' . ($selectActionLabels[$changedLog->action] ?? $changedLog->action) : '' }} <br>
                            <span>{{ $log->old_value['user_name'] ?? '—' }}</span> &#8594; <span>{{ $log->new_value['user_name'] ?? '—' }}</span>
                        </p>
                        <div class="actionTabHistoryItemRow">
                            <img src="/assets/img/master-done.svg" alt="">
                            <span>{{ $log->user?->name ?? '—' }}</span>
                        </div>
                        @if($log->comment)<div class="actionTabHistoryItemComment"><p>{{ $log->comment }}</p></div>@endif
                    </div>
                </div>
                <div class="actionTabHistoryItemTime">
                    <span>{{ $log->created_at->format('H:i') }}</span>
                    <span>{{ $log->created_at->format('d.m.Y') }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- Откатить статус --}}
@if($order->status !== 'pending')
<div class="actionTabViewOrderCard actionTabViewOrderCardHide">
    <div class="actionTabViewOrderCardTop" onclick="toggleCollapse(this)">
        <p class="actionTabTitle">откатить статус</p>
        <button type="button" class="arrowDown"><img src="/assets/img/arrow-down.svg" alt=""></button>
    </div>
    <form method="POST" action="{{ route('orders.rollback', $order) }}">
        @csrf
        <div class="actionTabViewOrderCardBtm actionTabViewOrderCardBtmColumn2">
            <div class="defaultForm"><input type="text" name="comment" placeholder="Причина отката..." required></div>
        </div>
        <div class="actionTabHistorySUV">
            <button type="submit"><img src="/assets/img/edit-icon-gold.svg" alt=""><span>Откатить статус</span></button>
        </div>
    </form>
</div>
@endif