let currentOrderSheetId = null;

// ===== Кастомный select (общий, используется и в createOrder, и в карточке заказа) =====
function initCustomSelect(root) {
    if (root.dataset.customSelectInit) return;
    root.dataset.customSelectInit = '1';

    const valueEl = root.querySelector('.customSelectValue');
    const hidden = root.querySelector('input[type="hidden"]');
    const dropdown = root.querySelector('.customSelectDropdown');
    const placeholder = valueEl.dataset.placeholder;

    root.addEventListener('click', (e) => {
        if (e.target.closest('.customSelectDropdown')) return;
        e.stopPropagation();
        closeAllCustomDropdowns(root);
        root.classList.toggle('--open');
        root.classList.remove('--invalid');
    });

    dropdown.addEventListener('click', (e) => {
        const opt = e.target.closest('.customSelectOption');
        if (!opt) return;
        dropdown.querySelectorAll('.customSelectOption').forEach(o => o.classList.remove('--active'));
        opt.classList.add('--active');
        hidden.value = opt.dataset.value;
        valueEl.textContent = opt.textContent;
        root.classList.add('--filled');
        root.classList.remove('--open', '--invalid');
        root.dispatchEvent(new CustomEvent('customselect:change', { detail: { value: opt.dataset.value, option: opt }, bubbles: true }));
    });

    root.resetCustom = function () {
        hidden.value = '';
        valueEl.textContent = placeholder;
        root.classList.remove('--filled', '--open', '--invalid');
        dropdown.querySelectorAll('.customSelectOption').forEach(o => o.classList.remove('--active'));
    };
}

function initAllCustomSelects(scope = document) {
    scope.querySelectorAll('.customSelect').forEach(initCustomSelect);
}

// "Изменить мастера" — action формы зависит от выбранного лога (через кастомный select)
document.addEventListener('customselect:change', function (e) {
    if (e.target.id === 'masterLogSelect') {
        const form = document.getElementById('masterChangeForm');
        if (form) form.action = e.detail.option.dataset.url || '';
    }
});

function closeAllCustomDropdowns(except) {
    document.querySelectorAll('.customSelect.--open, .customTime.--open').forEach(el => {
        if (el !== except) el.classList.remove('--open');
    });
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.customSelect') && !e.target.closest('.customTime')) {
        closeAllCustomDropdowns();
    }
});

document.addEventListener('DOMContentLoaded', () => initAllCustomSelects());

function openOrderSheet(orderId) {
    currentOrderSheetId = orderId;
    document.getElementById('viewOrderIdLabel').textContent = '#' + String(orderId).padStart(4, '0');
    document.getElementById('viewOrderInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>';
    openSheet('viewOrder');
    loadOrderSheet(orderId);
    initOrderSheetEcho(orderId);
}

function renderOrderSheet(html) {
    const el = document.getElementById('viewOrderInner');
    el.innerHTML = html;
    initAllCustomSelects(el);
    initAllCustomTimes(el);
    tickTimers(el);
}

function loadOrderSheet(orderId) {
    fetch(`/orders/${orderId}/sheet`)
        .then(r => r.text())
        .then(renderOrderSheet)
        .catch(() => { document.getElementById('viewOrderInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Не удалось загрузить заказ</p>'; });
}

let orderSheetEchoBound = null;
function initOrderSheetEcho(orderId) {
    if (orderSheetEchoBound) window.Echo.leave('order.' + orderSheetEchoBound);
    orderSheetEchoBound = orderId;
    window.Echo.channel('order.' + orderId).listen('.order.updated', () => {
        if (Date.now() < (window._orderSheetSkipUntil || 0)) return;
            loadOrderSheet(orderId);
    });
}

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!(form.closest('#viewOrderInner') || form.id === 'viewOrderAttachGuestForm')) return;
    e.preventDefault();
    if (form.dataset.busy) return;
    const submitter = e.submitter;
    const fd = new FormData(form);
    if (submitter && submitter.name) fd.set(submitter.name, submitter.value);
    const url = (submitter && submitter.hasAttribute('formaction')) ? submitter.formAction : form.action;
    form.dataset.busy = '1';
    window._orderSheetSkipUntil = Date.now() + 2500;
    const btns = [...form.querySelectorAll('button:not([disabled])')];
    btns.forEach(b => { b.disabled = true; });

    fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(async r => {
            if (!r.ok) {
                const data = await r.json().catch(() => ({}));
                const err = new Error(data.message || 'Ошибка при сохранении');
                err.status = r.status;
                throw err;
            }
            return (r.headers.get('Content-Type') || '').includes('text/html') ? r.text() : null;
        })
        .then(html => html ? renderOrderSheet(html) : loadOrderSheet(currentOrderSheetId))
        .catch(err => {
            if (err.status === 409) { loadOrderSheet(currentOrderSheetId); return; }
            let el = form.querySelector('.formInlineError');
            if (!el) {
                el = document.createElement('p');
                el.className = 'formInlineError';
                el.style.cssText = 'color:#e5484d; font-size:13px; margin:8px 0 0;';
                form.appendChild(el);
            }
            el.textContent = err.message || 'Ошибка при сохранении';
        })
        .finally(() => {
            delete form.dataset.busy;
            btns.forEach(b => { b.disabled = false; });
        });
});

// Аккордеоны (сменить стол / мастер / история)
function toggleCollapse(btn) {
    btn.closest('.actionTabViewOrderCardHide').classList.toggle('actionTabViewOrderCardShow');
}



// Состав — показать/скрыть форму добавления
function toggleAddItemForm(btn) {
    const wrap = document.getElementById('addItemFormWrap');
    wrap.style.display = wrap.style.display === 'none' ? 'block' : 'none';
}

function toggleRemoveItemForm(itemId) {
    const el = document.getElementById('removeItemForm' + itemId);
    el.classList.toggle('active');
}

// Выбор позиций для добавления
let selectedAddItems = {};
function toggleItemSelect(el, type, id, name) {
    const key = type + '_' + id;
    if (selectedAddItems[key]) {
        delete selectedAddItems[key];
        el.classList.remove('--actionTabSearchSectionResultActive');
    } else {
        selectedAddItems[key] = { type, id, name };
        el.classList.add('--actionTabSearchSectionResultActive');
    }
    renderSelectedAddItems();
}

function renderSelectedAddItems() {
    const keys = Object.keys(selectedAddItems);
    const label = document.getElementById('addItemSelectedLabel');
    const names = document.getElementById('addItemSelectedNames');
    const hiddenWrap = document.getElementById('addItemHiddenInputs');
    if (!label) return;

    if (keys.length === 0) { label.style.display = 'none'; hiddenWrap.innerHTML = ''; return; }

    label.style.display = 'block';
    names.textContent = keys.map(k => selectedAddItems[k].name).join('; ');
    hiddenWrap.innerHTML = keys.map((k, i) => {
        const it = selectedAddItems[k];
        return `<input type="hidden" name="items[${i}][type]" value="${it.type}"><input type="hidden" name="items[${i}][id]" value="${it.id}">`;
    }).join('');
}

// Поиск позиций для добавления
document.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'addItemSearch') {
        const q = e.target.value.trim().toLowerCase();
        document.querySelectorAll('#addItemResults .addItemOption').forEach(el => {
            el.style.display = !q || el.dataset.name.includes(q) ? 'flex' : 'none';
        });
    }

    // Поиск гостя внутри карточки заказа
    if (e.target && e.target.id === 'viewOrderGuestSearch') {
        clearTimeout(window._viewOrderGuestSearchTimeout);
        const q = e.target.value.trim();
        const results = document.getElementById('viewOrderGuestResults');
        if (q.length < 1) { results.innerHTML = ''; return; }
        window._viewOrderGuestSearchTimeout = setTimeout(() => {
            fetch(`/orders/guests/search?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(guests => {
                    const statusClassMap = { new: '--questNew', regular: '--questRegular', permanent: '--questPermanent', vip: '--questVip' };
                    results.innerHTML = guests.map(g => {
                        const cls = statusClassMap[g.status] || '';
                        const initials = (g.name || '').trim().split(/\s+/).map(w => w[0]).join('').toUpperCase().slice(0, 2);
                        return `<div class="ordersCardTop" onclick='selectViewOrderGuest(${g.id})'>
                            <div class="ordersCardUser">
                                <div class="ordersCardUserImg"><span>${initials}</span></div>
                                <div class="ordersCardUserInfo">
                                    <div class="ordersCardUserInfoTop">
                                        <p class="ordersCardUserInfoName">${g.name}</p>
                                        <p class="ordersCardUserInfoUsername">${g.username}</p>
                                    </div>
                                    <div class="ordersCardUserInfoBtm">
                                        <div class="ordersCardUserInfoStatus ${cls}"><span>${g.status_label}</span></div>
                                        <p class="ordersCardUserInfoVisit">${g.visits_count} визита</p>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    }).join('');
                });
        }, 300);
    }
});

function selectViewOrderGuest(id) {
    document.getElementById('viewOrderGuestIdInput').value = id;
    document.getElementById('viewOrderAttachGuestForm').requestSubmit();
}

function toggleDetachGuestConfirm() {
    const el = document.getElementById('detachGuestConfirm');
    if (!el) return;
    el.classList.toggle('active');
}

// Тикающие таймеры (общие для всех .timer-counter на странице/в модалке)
function tickTimers(scope = document) {
    const now = Math.floor(Date.now() / 1000);
    scope.querySelectorAll('.timer-counter').forEach(el => {
        const diff = now - parseInt(el.dataset.start);
        const h = Math.floor(diff / 3600), m = Math.floor((diff % 3600) / 60), s = diff % 60;
        el.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    });
}
setInterval(() => tickTimers(), 1000);