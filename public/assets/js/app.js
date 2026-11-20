function pluralOrders(n) {
    const m = n % 100, d = n % 10;
    return m > 10 && m < 20 ? 'заказов' : d === 1 ? 'заказ' : d > 1 && d < 5 ? 'заказа' : 'заказов';
}

function openSheet(id) {
    const overlay = document.getElementById(id);
    const sheet = overlay.querySelector('.actionTabWrapper');
    overlay.classList.add('active');
    requestAnimationFrame(() => sheet.classList.add('active'));
    document.body.style.overflow = 'hidden';
}

function closeSheet(id) {
    const overlay = document.getElementById(id);
    const sheet = overlay.querySelector('.actionTabWrapper');
    sheet.classList.remove('active');
    setTimeout(() => {
        overlay.classList.remove('active');

        const hasActive = [...document.querySelectorAll('.actionTab')].some(item => item.classList.contains('active'));
        if (!hasActive) {
            document.body.style.overflow = '';
        }
    }, 350);
}

document.addEventListener('DOMContentLoaded', () => {
    const actionTabViewOrderCardHide = document.querySelectorAll('.actionTabViewOrderCardHide');

    actionTabViewOrderCardHide.forEach(item => {
        const top = item.querySelector('.actionTabViewOrderCardTop');

        top.addEventListener('click', () => {
            item.classList.toggle('actionTabViewOrderCardShow');
        });
    });

    document.querySelectorAll('.actionTab').forEach(overlay => {
        overlay.addEventListener('click', () => closeSheet(overlay.id));
        overlay.querySelector('.actionTabWrapper').addEventListener('click', e => e.stopPropagation());

        let startY = 0;
        let startedOnHeader = false;
        const sheet = overlay.querySelector('.actionTabWrapper');
        const header = sheet.querySelector('.actionTabHeader');

        sheet.addEventListener('touchstart', e => {
            startedOnHeader = header && header.contains(e.target);
            startY = e.touches[0].clientY;
        });
        sheet.addEventListener('touchend', e => {
            if (!startedOnHeader) return;
            if (e.changedTouches[0].clientY - startY > 80) closeSheet(overlay.id);
        });
    });
});

// ===== Кастомный time-picker (settings, createOrder) =====
function initCustomTime(root) {
    if (root.dataset.customTimeInit) return;
    root.dataset.customTimeInit = '1';

    const hidden = root.querySelector('input[type="hidden"]');
    const hhEl = root.querySelector('.customTimeHH');
    const mmEl = root.querySelector('.customTimeMM');
    const dropdown = root.querySelector('.customTimeDropdown');

    let hh = hhEl.textContent !== '--' ? hhEl.textContent : null;
    let mm = mmEl.textContent !== '--' ? mmEl.textContent : null;

    root.addEventListener('click', (e) => {
        if (e.target.closest('.customTimeDropdown')) return;
        e.stopPropagation();
        closeAllCustomDropdowns(root);
        root.classList.toggle('--open');
    });

    dropdown.addEventListener('click', (e) => {
        const opt = e.target.closest('.customTimeOption');
        if (opt) {
            const col = opt.closest('.customTimeCol');
            col.querySelectorAll('.customTimeOption').forEach(o => o.classList.remove('--active'));
            opt.classList.add('--active');
            if (col.dataset.type === 'hh') { hh = opt.dataset.value; hhEl.textContent = hh; }
            if (col.dataset.type === 'mm') { mm = opt.dataset.value; mmEl.textContent = mm; }
            return;
        }
        if (e.target.closest('.customTimeConfirm')) {
            if (hh !== null && mm !== null) {
                hidden.value = `${hh}:${mm}`;
                root.classList.add('--filled');
                root.dispatchEvent(new CustomEvent('customtime:change', { detail: { value: hidden.value }, bubbles: true }));
            }
            root.classList.remove('--open');
        }
    });

    root.resetCustom = function () {
        hidden.value = '';
        hh = null; mm = null;
        hhEl.textContent = '--';
        mmEl.textContent = '--';
        root.classList.remove('--filled', '--open');
        dropdown.querySelectorAll('.customTimeOption').forEach(o => o.classList.remove('--active'));
    };
}

function initAllCustomTimes(scope = document) {
    scope.querySelectorAll('.customTime').forEach(initCustomTime);
}

document.addEventListener('DOMContentLoaded', () => initAllCustomTimes());

function toggleGuestAccordion(btn) {
    const card = btn.closest('.labelGuestAccordionCard');
    card.dataset.open = card.dataset.open === '1' ? '0' : '1';
}

function applyListSort(container, itemSelector, mode) {
    if (typeof container === 'string') container = document.querySelector(container);
    if (!container) return;
    const items = [...container.querySelectorAll(itemSelector)];
    items.forEach((el, i) => { if (el.dataset.idx === undefined) el.dataset.idx = i; });
    const num = (el, k) => parseFloat(el.dataset[k]) || 0;
    const byName = (a, b) => (a.dataset.name || '').localeCompare(b.dataset.name || '', 'ru', { sensitivity: 'base' });
    const orig = (a, b) => num(a, 'idx') - num(b, 'idx');
    const cmp = {
        orig,
        name: (a, b) => byName(a, b) || orig(a, b),
        count_desc: (a, b) => num(b, 'count') - num(a, 'count') || byName(a, b) || orig(a, b),
        count_asc: (a, b) => num(a, 'count') - num(b, 'count') || byName(a, b) || orig(a, b),
        new: (a, b) => num(b, 'created') - num(a, 'created') || orig(a, b),
        old: (a, b) => num(a, 'created') - num(b, 'created') || orig(b, a),
    }[mode] || orig;
    items.sort(cmp).forEach(el => container.appendChild(el));
}


document.addEventListener('contextmenu', e => { if (e.target.closest && e.target.closest('img')) e.preventDefault(); });
document.addEventListener('dragstart', e => { if (e.target.tagName === 'IMG') e.preventDefault(); });

window.appToast = function (msg, type) {
    const el = document.createElement('div');
    el.className = 'flashToast --' + (type === 'success' ? 'success' : 'error');
    el.setAttribute('role', type === 'success' ? 'status' : 'alert');
    el.textContent = msg;
    el.addEventListener('animationend', () => el.remove());
    document.body.appendChild(el);
};

window.appConfirm = function (message, okText) {
    return new Promise(resolve => {
        const wrap = document.createElement('div');
        wrap.className = 'appConfirm';
        wrap.innerHTML = '<div class="appConfirmBox" role="dialog" aria-modal="true"><p></p><div class="appConfirmBtns"><button type="button" class="appConfirmNo">Нет</button><button type="button" class="appConfirmYes"></button></div></div>';
        wrap.querySelector('p').textContent = message;
        wrap.querySelector('.appConfirmYes').textContent = okText || 'Да';
        const done = v => { wrap.remove(); resolve(v); };
        wrap.querySelector('.appConfirmNo').onclick = () => done(false);
        wrap.querySelector('.appConfirmYes').onclick = () => done(true);
        wrap.addEventListener('click', e => { if (e.target === wrap) done(false); });
        document.body.appendChild(wrap);
    });
};

document.addEventListener('click', e => {
    const el = e.target.closest('button[data-confirm],a[data-confirm]');
    if (!el || el._confirmed) return;
    e.preventDefault();
    e.stopPropagation();
    window.appConfirm(el.dataset.confirm).then(ok => {
        if (!ok) return;
        el._confirmed = true;
        el.click();
        el._confirmed = false;
    });
}, true);

document.addEventListener('submit', e => {
    const form = e.target;
    if (!form.dataset || !form.dataset.confirm || form._confirmed) return;
    e.preventDefault();
    e.stopPropagation();
    window.appConfirm(form.dataset.confirm).then(ok => {
        if (!ok) return;
        form._confirmed = true;
        form.requestSubmit();
        form._confirmed = false;
    });
}, true);
