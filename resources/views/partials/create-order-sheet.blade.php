{{-- Модалка "Новый заказ" — глобальная, подключается один раз в layouts/app.blade.php
     и открывается кнопкой .menuItemCreateOrder (onclick="openSheet('createOrder')") с любой страницы.
     $tables сюда прилетают через View::composer (см. AppServiceProvider). --}}
<div class="actionTab createOrder" id="createOrder">
    <div class="actionTabWrapper">
        <form class="createOrderForm" method="POST" action="{{ route('orders.store') }}" id="createOrderForm">
            @csrf

            <div class="actionTabHeader">
                <h3>Новый заказ</h3>

                <div class="actionTabHeaderBtns">
                    <button type="button" class="actionTabHeaderBtn --tabBtnClear" onclick="resetCreateOrderForm()">
                        <img src="/assets/img/clear-icon.svg" alt="">
                    </button>

                    <button type="button" onclick="closeSheet('createOrder')" class="actionTabHeaderBtn --tabBtnClose">
                        <img src="/assets/img/close-icon.svg" alt="">
                    </button>
                </div>
            </div>

            <div class="actionTabInner --flex1">
                <div class="actionTabSection">
                    <label class="actionTabTitle">НАЗВАНИЕ ЗАКАЗА</label>
                    <div class="defaultForm">
                        <input name="name" type="text" placeholder="Классика (по умолчанию)">
                    </div>
                </div>

                <div class="actionTabSectionRow _mt10">
                    <div class="actionTabSectionItem">
                        <div class="actionTabTitle">ВРЕМЯ</div>
                        <div class="defaultForm customTime" id="createOrderTimePicker">
                            <div class="customTimeValue">
                                <span class="customTimeHH">--</span><span>:</span><span class="customTimeMM">--</span>
                            </div>
                            <svg class="customTimeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 7v5l3 2"></path>
                            </svg>
                            <input type="hidden" name="started_at_time" id="createOrderTime" value="">

                            <div class="customTimeDropdown">
                                <div class="customTimeCols">
                                    <div class="customTimeCol" data-type="hh">
                                        @for ($h = 0; $h < 24; $h++)
                                            <div class="customTimeOption" data-value="{{ sprintf('%02d', $h) }}">{{ sprintf('%02d', $h) }}</div>
                                        @endfor
                                    </div>
                                    <div class="customTimeCol" data-type="mm">
                                        @for ($m = 0; $m < 60; $m++)
                                            <div class="customTimeOption" data-value="{{ sprintf('%02d', $m) }}">{{ sprintf('%02d', $m) }}</div>
                                        @endfor
                                    </div>
                                </div>
                                <button type="button" class="customTimeConfirm">Готово</button>
                            </div>
                        </div>
                    </div>

                    <div class="actionTabSectionItem">
                        <div class="actionTabTitle">Стол</div>
                        <div class="defaultForm customSelect" id="createOrderTableSelect">
                            <div class="customSelectValue" data-placeholder="Стол">Стол</div>
                            <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 1l4 4 4-4"></path>
                            </svg>
                            <input type="hidden" name="table_id" id="createOrderTable">

                            <div class="customSelectDropdown">
                                @foreach(($tables ?? collect())->groupBy(fn($t) => $t->type?->name ?? 'Без типа') as $typeName => $group)
                                    <div class="customSelectGroupLabel">{{ $typeName }}</div>
                                    @foreach($group as $table)
                                        <div class="customSelectOption" data-value="{{ $table->id }}">{{ $table->name }}</div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="actionTabSection _mt10">
                    <label class="actionTabTitle">КОММЕНТАРИЙ ГОСТЯ</label>
                    <div class="defaultForm">
                        <textarea name="guest_comment" id="createOrderComment" rows="4"></textarea>
                    </div>
                </div>

                <div class="actionTabSearchSection --flex1">
                    <div>
                        <label for="createOrderSearchGuests" class="actionTabTitle">ГОСТЬ</label>
                        <div class="search">
                            <img src="/assets/img/search-icon.svg" alt="">
                            <input id="createOrderSearchGuests" type="text" placeholder="Имя, номер телефона, username..." autocomplete="off">
                        </div>
                    </div>

                    <div class="actionTabSearchSectionResult" id="createOrderGuestResults"></div>

                    <input type="hidden" name="guest_id" id="createOrderGuestId">
                </div>

                {{-- Выбранный гость (краткая карточка) --}}
                <div class="actionTabSection _mt10 --createOrderSelectedGuestWrap" id="createOrderSelectedGuestWrap" style="display:none;">
                    <div class="ordersCardTop --actionTabSearchSectionResultActive" id="createOrderSelectedGuestCard"></div>
                </div>

                <div class="actionTabSection" id="createOrderNewGuestToggleWrap">
                    <button type="button" class="actionTabCreateQuest _mt10" onclick="toggleNewGuestForm()">
                        <img src="/assets/img/create-dash.svg" alt="">
                        <span>Создать нового гостя</span>
                    </button>
                </div>

                <div class="actionTabSection _mt10" id="createOrderNewGuestForm" style="display:none;">
                    <div class="actionTabSectionRow">
                        <div class="actionTabSectionItem">
                            <label class="actionTabTitle">ИМЯ</label>
                            <div class="defaultForm">
                                <input type="text" name="new_guest_name" id="createOrderNewGuestName" placeholder="Имя гостя">
                            </div>
                        </div>
                        <div class="actionTabSectionItem">
                            <label class="actionTabTitle">ТЕЛЕФОН</label>
                            <div class="defaultForm">
                                <input type="text" name="new_guest_phone" placeholder="+7 777 000 00 00">
                            </div>
                        </div>
                    </div>

                    <div class="actionTabSectionRow _mt10">
                        <div class="actionTabSectionItem">
                            <label class="actionTabTitle">USERNAME</label>
                            <div class="defaultForm">
                                <input type="text" name="new_guest_username" placeholder="Авто">
                            </div>
                        </div>
                        <div class="actionTabSectionItem">
                            <label class="actionTabTitle">EMAIL</label>
                            <div class="defaultForm">
                                <input type="email" name="new_guest_email" placeholder="email@example.com">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="actionTabActions">
                <div class="actionTabActionsButtons">
                    <button type="submit" class="actionTabActionsButton --buttonAccent">
                        Создать заказ
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    let createOrderSearchTimeout;
    let createOrderStatusColors = {
        new:       'ordersCardUserInfoStatus --questNew',
        regular:   'ordersCardUserInfoStatus --questRegular',
        permanent: 'ordersCardUserInfoStatus --questPermanent',
        vip:       'ordersCardUserInfoStatus --questVip',
    };

    function guestCardHtml(g, selected) {
        const statusClass = createOrderStatusColors[g.status] || 'ordersCardUserInfoStatus';
        const vipStar = g.status === 'vip' ? '<img src="/assets/img/star-gold.svg" alt="">' : '';
        const initials = (g.name || '').trim().split(/\s+/).map(w => w[0]).join('').toUpperCase().slice(0, 2);

        return `
            <div class="ordersCardUser">
                <div class="ordersCardUserImg"><span>${initials}</span></div>
                <div class="ordersCardUserInfo">
                    <div class="ordersCardUserInfoTop">
                        <p class="ordersCardUserInfoName">${g.name}</p>
                        <p class="ordersCardUserInfoUsername">${g.username || ''}</p>
                    </div>
                    <div class="ordersCardUserInfoBtm">
                        <div class="${statusClass}">
                            ${vipStar}
                            <span>${g.status_label}</span>
                        </div>
                        <p class="ordersCardUserInfoVisit">${g.visits_count} визита</p>
                    </div>
                </div>
            </div>
        `;
    }

    window.selectCreateOrderGuest = function (g) {
        document.getElementById('createOrderGuestId').value = g.id;

        // Подсвечиваем выбранную строку прямо в списке результатов
        document.querySelectorAll('#createOrderGuestResults .ordersCardTop').forEach(el => {
            el.classList.remove('--actionTabSearchSectionResultActive');
        });
        const clickedRow = event?.currentTarget;
        if (clickedRow) clickedRow.classList.add('--actionTabSearchSectionResultActive');

        document.getElementById('createOrderSearchGuests').value = '';
        document.getElementById('createOrderGuestResults').innerHTML = '';

        const wrap = document.getElementById('createOrderSelectedGuestWrap');
        const card = document.getElementById('createOrderSelectedGuestCard');
        card.innerHTML = guestCardHtml(g, true);
        wrap.style.display = 'block';

        document.getElementById('createOrderNewGuestForm').style.display = 'none';
    };

    window.clearCreateOrderGuest = function () {
        document.getElementById('createOrderGuestId').value = '';
        document.getElementById('createOrderSelectedGuestWrap').style.display = 'none';
        document.getElementById('createOrderSelectedGuestCard').innerHTML = '';
    };

    window.toggleNewGuestForm = function () {
        const form = document.getElementById('createOrderNewGuestForm');
        const isOpen = form.style.display === 'block';
        form.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) clearCreateOrderGuest();
    };

    window.resetCreateOrderForm = function () {
        const form = document.getElementById('createOrderForm');
        form.reset();
        clearCreateOrderGuest();
        document.getElementById('createOrderNewGuestForm').style.display = 'none';
        document.getElementById('createOrderGuestResults').innerHTML = '';

        if (tableSelectRoot?.resetCustom) tableSelectRoot.resetCustom();
        if (timePickerRoot?.resetCustom) timePickerRoot.resetCustom();
    };

    document.addEventListener('input', function (e) {
        if (e.target && e.target.id === 'createOrderSearchGuests') {
            clearTimeout(createOrderSearchTimeout);
            const q = e.target.value.trim();
            const results = document.getElementById('createOrderGuestResults');

            if (q.length < 1) {
                results.innerHTML = '';
                return;
            }

            createOrderSearchTimeout = setTimeout(() => {
                fetch(`/orders/guests/search?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(guests => {
                        const selectedId = document.getElementById('createOrderGuestId').value;

                        results.innerHTML = guests.map(g => {
                            const isActive = selectedId && String(g.id) === String(selectedId);
                            return `
                                <div class="ordersCardTop${isActive ? ' --actionTabSearchSectionResultActive' : ''}" onclick='selectCreateOrderGuest(${JSON.stringify(g)})'>
                                    ${guestCardHtml(g)}
                                </div>
                            `;
                        }).join('');
                    });
            }, 300);
        }
    });


    // ===== Кастомный time-picker "Время" =====
    function initCustomTime(root) {
        const hhEl = root.querySelector('.customTimeHH');
        const mmEl = root.querySelector('.customTimeMM');
        const hidden = root.querySelector('input[type="hidden"]');
        const dropdown = root.querySelector('.customTimeDropdown');
        const confirmBtn = root.querySelector('.customTimeConfirm');
        let hh = null, mm = null;

        root.querySelector('.customTimeValue').addEventListener('click', (e) => {
            e.stopPropagation();
            closeAllCustomDropdowns(root);
            root.classList.toggle('--open');
        });

        dropdown.addEventListener('click', (e) => {
            const opt = e.target.closest('.customTimeOption');
            if (!opt) return;
            const col = opt.closest('.customTimeCol');
            col.querySelectorAll('.customTimeOption').forEach(o => o.classList.remove('--active'));
            opt.classList.add('--active');

            if (col.dataset.type === 'hh') { hh = opt.dataset.value; hhEl.textContent = hh; }
            else { mm = opt.dataset.value; mmEl.textContent = mm; }

            if (hh !== null && mm !== null) {
                hidden.value = `${hh}:${mm}`;
                root.classList.add('--filled');
            }
        });

        confirmBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            root.classList.remove('--open');
        });

        root.resetCustom = function () {
            hh = null; mm = null;
            hhEl.textContent = '--';
            mmEl.textContent = '--';
            hidden.value = '';
            root.classList.remove('--filled', '--open');
            dropdown.querySelectorAll('.customTimeOption').forEach(o => o.classList.remove('--active'));
        };
    }

    const tableSelectRoot = document.getElementById('createOrderTableSelect');
    const timePickerRoot  = document.getElementById('createOrderTimePicker');
    if (tableSelectRoot) initCustomSelect(tableSelectRoot);
    if (timePickerRoot)  initCustomTime(timePickerRoot);

    // Комбинируем время + сегодняшняя дата в started_at, валидируем стол перед сабмитом
    document.addEventListener('submit', function (e) {
        if (e.target && e.target.id === 'createOrderForm') {
            const timeVal = document.getElementById('createOrderTime').value;
            const startedAtInput = document.getElementById('createOrderStartedAt');
            startedAtInput.value = timeVal ? `${new Date().toISOString().slice(0, 10)}T${timeVal}` : '';

            const tableInput = document.getElementById('createOrderTable');
            if (!tableInput.value) {
                e.preventDefault();
                tableSelectRoot.classList.add('--invalid');
                tableSelectRoot.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
})();
</script>
@endpush