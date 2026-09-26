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