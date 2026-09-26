function filterGuestList() {
    const activeFilterBtn = document.querySelector('.filtersBtn.active');
    const status = activeFilterBtn ? activeFilterBtn.dataset.status : '';
    const searchInput = document.getElementById('guestSearch');
    const q = searchInput ? searchInput.value.trim().toLowerCase() : '';

    const items = document.querySelectorAll('#guestList .ordersCard');
    let visible = 0;

    items.forEach(item => {
        const matchStatus = !status || item.dataset.status === status;
        const matchSearch = !q || item.dataset.search.includes(q);
        const show = matchStatus && matchSearch;
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const noResults = document.getElementById('guestNoResults');
    if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.filtersBtn[data-status]');
    if (!btn) return;
    document.querySelectorAll('.filtersBtn[data-status]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterGuestList();
});

document.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'guestSearch') filterGuestList();
});

document.addEventListener('DOMContentLoaded', () => filterGuestList());

function loadGuestList() {
    fetch('/guests/stats-partial').then(r => r.text()).then(html => {
        const el = document.getElementById('guestHeaderStats');
        if (el) el.innerHTML = html;
    });

    fetch('/guests/list-partial').then(r => r.text()).then(html => {
        const el = document.getElementById('guestList');
        if (el) { el.innerHTML = html; filterGuestList(); }
    });
}

// ===== Создание / редактирование =====
function openGuestCreateSheet() {
    document.getElementById('guestFormTitle').textContent = 'Создать гостя';
    fetch('/guests/create-form').then(r => r.text()).then(html => {
        document.getElementById('guestFormInner').innerHTML = html;
        openSheet('createGuest');
    });
}

let currentGuestSheetId = null;

function openGuestEditSheetFromCard() {
    if (!currentGuestSheetId) return;
    openGuestEditSheet(currentGuestSheetId);
}

function openGuestEditSheet(id) {
    document.getElementById('guestFormTitle').textContent = 'Редактировать гостя';
    fetch(`/guests/${id}/edit-form`).then(r => r.text()).then(html => {
        document.getElementById('guestFormInner').innerHTML = html;
        openSheet('createGuest');
        initAllCustomSelects(document.getElementById('createGuest'));
    });
}

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.id === 'createGuestForm' || form.id === 'editGuestForm') {
        e.preventDefault();
        const fd = new FormData(form);
        const isEdit = form.id === 'editGuestForm';

        fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => {
                if (!r.ok) return;
                closeSheet('createGuest');
                loadGuestList();
                if (isEdit && currentGuestSheetId) loadGuestSheet(currentGuestSheetId);
            });
    }
});

// ===== Карточка гостя =====
function openGuestSheet(id) {
    currentGuestSheetId = id;
    document.getElementById('viewGuestInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>';
    openSheet('viewGuest');
    loadGuestSheet(id);
}

function loadGuestSheet(id) {
    fetch(`/guests/${id}/sheet`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('viewGuestInner').innerHTML = html;
            initAllCustomSelects(document.getElementById('viewGuestInner'));
            loadGuestVisitHistoryShort(id);
        })
        .catch(() => { document.getElementById('viewGuestInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Не удалось загрузить</p>'; });
}

function loadGuestVisitHistoryShort(id) {
    fetch(`/guests/${id}/visit-history/sheet`)
        .then(r => r.text())
        .then(html => {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            const rows = temp.querySelector('#guestVisitHistoryFullRows') || temp;
            const rowsEl = document.getElementById('guestVisitHistoryShortRows');
            if (rowsEl) rowsEl.innerHTML = rows.innerHTML;
        });
}

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.closest('#viewGuestInner')) {
        e.preventDefault();
        const fd = new FormData(form);
        fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => {
                if (!r.ok) return;
                loadGuestSheet(currentGuestSheetId);
                loadGuestList();
            });
    }
});

function expandTopList(btn) {
    const row = btn.closest('.topListRow');
    const hidden = row.querySelectorAll('.topListHidden[style*="display:none"], .topListHidden[style*="display: none"]');
    let shown = 0;
    hidden.forEach(el => {
        if (shown < 5) { el.style.display = ''; shown++; }
    });
    if (row.querySelectorAll('.topListHidden[style*="display:none"], .topListHidden[style*="display: none"]').length === 0) {
        btn.closest('.actionTabViewOrderCard').style.display = 'none';
    }
}

// ===== История визитов (полная) =====
function openGuestVisitHistorySheet(id, name) {
    document.getElementById('guestVisitHistoryInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>';
    openSheet('viewGuestsVisitHistory');
    fetch(`/guests/${id}/visit-history/sheet`)
        .then(r => r.text())
        .then(html => { document.getElementById('guestVisitHistoryInner').innerHTML = html; })
        .catch(() => { document.getElementById('guestVisitHistoryInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Не удалось загрузить</p>'; });
}

function loadMoreGuestVisitHistory(lastId, btn) {
    const guestId = document.getElementById('guestVisitHistoryFullRows')?.dataset.guestId;
    if (!guestId) return;
    btn.textContent = 'Загрузка...';

    fetch(`/guests/${guestId}/visit-history/more?last_id=${lastId}`)
        .then(r => r.text())
        .then(html => {
            btn.remove();
            document.getElementById('guestVisitHistoryFullRows').insertAdjacentHTML('beforeend', html);
        })
        .catch(() => { btn.textContent = 'Ошибка, повторить'; });
}

// ===== Ярлыки =====
function openGuestLabelsSheet(id, name) {
    document.getElementById('guestLabelsTitle').textContent = 'Карточка гостя ' + name;
    document.getElementById('guestLabelsInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>';
    openSheet('addOrEditBagesToGuest');
    fetch(`/guests/${id}/labels/sheet`)
        .then(r => r.text())
        .then(html => { document.getElementById('guestLabelsInner').innerHTML = html; })
        .catch(() => { document.getElementById('guestLabelsInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Не удалось загрузить</p>'; });
}

function filterGuestLabels(q) {
    q = q.trim().toLowerCase();
    document.querySelectorAll('#guestLabelsListWrap .guestLabelRow').forEach(row => {
        row.style.display = !q || row.dataset.labelName.includes(q) ? '' : 'none';
    });
}

function toggleGuestLabel(guestId, labelId, type, currentAssignmentId, isActiveHere) {
    if (isActiveHere) {
        fetch(`/guests/${guestId}/labels/${currentAssignmentId}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-HTTP-Method-Override': 'DELETE' },
        }).then(() => refreshGuestLabelsSheet(guestId));
    } else {
        const fd = new FormData();
        fd.append('label_id', labelId);
        fd.append('type', type);
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        fetch(`/guests/${guestId}/labels`, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(() => refreshGuestLabelsSheet(guestId));
    }
}

function refreshGuestLabelsSheet(guestId) {
    const openIndexes = Array.from(document.querySelectorAll('#guestLabelsListWrap .guestLabelBrandCard'))
        .map((card, i) => card.classList.contains('actionTabViewOrderCardShow') ? i : null)
        .filter(i => i !== null);

    fetch(`/guests/${guestId}/labels/sheet`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('guestLabelsInner').innerHTML = html;

            const cards = document.querySelectorAll('#guestLabelsListWrap .guestLabelBrandCard');
            cards.forEach((card, i) => {
                card.classList.toggle('actionTabViewOrderCardShow', openIndexes.includes(i));
            });

            loadGuestSheet(guestId);
        });
}

function toggleGuestFormSubmit(input) {
    const form = input.closest('form');
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = !input.value.trim();
}

function openGuestHistorySheet(id, name) {
    document.getElementById('guestHistoryTitle').textContent = name;
    document.getElementById('guestHistoryInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>';
    openSheet('viewGuestHistoryTab');
    fetch(`/guests/${id}/history/sheet`)
        .then(r => r.text())
        .then(html => { document.getElementById('guestHistoryInner').innerHTML = html; })
        .catch(() => { document.getElementById('guestHistoryInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Не удалось загрузить</p>'; });
}