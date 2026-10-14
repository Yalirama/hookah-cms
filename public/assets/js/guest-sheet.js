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
    if (!form.closest('#viewGuestInner')) return;
    e.preventDefault();

    if (form.classList.contains('guestNoteForm')) return addGuestNote(form);
    if (form.classList.contains('guestNoteDeleteForm')) return deleteGuestNote(form);

    if (form.dataset.busy) return;
    form.dataset.busy = '1';
    const fd = new FormData(form);
    const btns = [...form.querySelectorAll('button:not([disabled])')];
    btns.forEach(b => { b.disabled = true; });

    fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => {
            if (!r.ok) return;
            loadGuestSheet(currentGuestSheetId);
            loadGuestList();
        })
        .finally(() => {
            delete form.dataset.busy;
            btns.forEach(b => { b.disabled = false; });
        });
});

function buildGuestNoteItem(box, text, token) {
    const d = new Date();
    const p = n => String(n).padStart(2, '0');
    const item = document.createElement('div');
    item.className = 'actionTabCntForm8s2id guestNoteItem';
    item.innerHTML = `
        <div class="actionTabCntForm8s2idRow">
            <textarea readonly rows="3"></textarea>
            <div class="actionTabCntForm8s2idRowAction">
                <div class="actionTabCntForm8s2idRowActionItem">
                    <div class="item"><img src="/assets/img/master-done.svg" alt=""><p class="noteUser"></p></div>
                    <div class="item"><img src="/assets/img/time-icon.svg" alt=""><p class="noteDate"></p></div>
                </div>
                <form method="POST" action="" class="guestNoteDeleteForm">
                    <input type="hidden" name="_token">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit"><img src="/assets/img/close-icon.svg" alt=""><span>Удалить</span></button>
                </form>
            </div>
        </div>`;
    item.querySelector('textarea').value = text;
    item.querySelector('.noteUser').textContent = box.dataset.user;
    item.querySelector('.noteDate').textContent = `${p(d.getDate())}.${p(d.getMonth() + 1)}.${d.getFullYear()} ${p(d.getHours())}:${p(d.getMinutes())}`;
    item.querySelector('input[name="_token"]').value = token;
    return item;
}

function addGuestNote(form) {
    const ta = form.querySelector('textarea[name="note"]');
    const text = ta.value.trim();
    if (!text || form.dataset.busy) return;
    form.dataset.busy = '1';

    const fd = new FormData(form);
    const item = buildGuestNoteItem(form.closest('.guestNotesBox'), text, fd.get('_token'));
    form.after(item);
    ta.value = '';

    fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(async r => {
            if (!r.ok) throw new Error();
            return r.json();
        })
        .then(data => { item.querySelector('.guestNoteDeleteForm').action = data.delete_url; })
        .catch(() => { item.remove(); ta.value = text; })
        .finally(() => { delete form.dataset.busy; });
}

function deleteGuestNote(form) {
    if (form.dataset.busy || !form.getAttribute('action')) return;
    form.dataset.busy = '1';

    const item = form.closest('.guestNoteItem');
    const fd = new FormData(form);
    item.style.display = 'none';

    fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => {
            if (!r.ok) throw new Error();
            item.remove();
        })
        .catch(() => { item.style.display = ''; })
        .finally(() => { delete form.dataset.busy; });
}

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

function toggleGuestLabel(row) {
    const ACTIVE = '--actionTabSearchSectionResultActive';
    const guestId = row.dataset.guestId;
    const labelId = row.dataset.labelId;
    const rows = [...document.querySelectorAll(`#guestLabelsListWrap .guestLabelRow[data-label-id="${labelId}"]`)];
    if (rows.some(r => r.dataset.busy)) return;

    const wasActive = row.classList.contains(ACTIVE);
    const assignment = row.dataset.assignment;
    const prev = rows.map(r => [r, r.classList.contains(ACTIVE), r.dataset.assignment || '']);
    const token = document.querySelector('meta[name="csrf-token"]').content;

    rows.forEach(r => { r.classList.remove(ACTIVE); r.dataset.busy = '1'; });
    if (!wasActive) row.classList.add(ACTIVE);

    let req;
    if (wasActive) {
        req = fetch(`/guests/${guestId}/labels/${assignment}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token, 'X-HTTP-Method-Override': 'DELETE' },
        });
    } else {
        const fd = new FormData();
        fd.append('label_id', labelId);
        fd.append('type', row.dataset.type);
        fd.append('_token', token);
        req = fetch(`/guests/${guestId}/labels`, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    }

    req.then(async r => {
            if (!r.ok) throw new Error();
            return r.json().catch(() => ({}));
        })
        .then(data => {
            const id = wasActive ? '' : (data.id || '');
            rows.forEach(r => { r.dataset.assignment = id; });
            clearTimeout(window._guestCardRefresh);
            window._guestCardRefresh = setTimeout(() => loadGuestSheet(guestId), 800);
        })
        .catch(() => {
            prev.forEach(([r, was, asg]) => { r.classList.toggle(ACTIVE, was); r.dataset.assignment = asg; });
        })
        .finally(() => rows.forEach(r => { delete r.dataset.busy; }));
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
function openOrderFromGuest(orderId) {
    ['viewGuestsVisitHistory', 'viewGuest'].forEach(id => {
        const el = document.getElementById(id);
        if (el && el.classList.contains('active')) closeSheet(id);
    });
    openOrderSheet(orderId);
}
