let currentTobaccoSheetId = null;

function openTobaccoSheet(id) {
    currentTobaccoSheetId = id;
    document.getElementById('viewTabaccosInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>';
    openSheet('viewTabaccos');
    loadTobaccoSheet(id);
}

function loadTobaccoSheet(id) {
    fetch(`/tobaccos/${id}/sheet`)
        .then(r => r.text())
        .then(html => { document.getElementById('viewTabaccosInner').innerHTML = html; })
        .catch(() => { document.getElementById('viewTabaccosInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Не удалось загрузить</p>'; });
}

// Перехват форм внутри sheet карточки табака (заметки)
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.closest('#viewTabaccosInner')) {
        e.preventDefault();
        const submitter = e.submitter;
        const fd = new FormData(form);
        if (submitter && submitter.name) fd.set(submitter.name, submitter.value);
        const url = (submitter && submitter.hasAttribute('formaction')) ? submitter.formAction : form.action;
        const method = form.method === 'get' ? 'GET' : 'POST';

        fetch(url, { method, body: method === 'GET' ? undefined : fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(async r => {
                if (!r.ok) {
                    const data = await r.json().catch(() => ({}));
                    throw new Error(data.message || 'Ошибка при сохранении');
                }
                return r;
            })
            .then(() => loadTobaccoSheet(currentTobaccoSheetId))
            .catch(err => alert(err.message || 'Ошибка при сохранении'));
    }
});

// Фильтр по статусу
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.filtersBtn[data-status]');
    if (!btn) return;
    document.querySelectorAll('.filtersBtn[data-status]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterTobaccoList();
});

// Поиск
document.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'tobaccoSearch') {
        filterTobaccoList();
    }
});

function filterTobaccoList() {
    const activeFilterBtn = document.querySelector('.filtersBtn[data-status].active');
    const status = activeFilterBtn ? activeFilterBtn.dataset.status : 'all';
    const searchInput = document.getElementById('tobaccoSearch');
    const q = searchInput ? searchInput.value.trim().toLowerCase() : '';

    const items = document.querySelectorAll('#tobaccoList .productListItem');
    let visible = 0;

    items.forEach(item => {
        const matchStatus = status === 'all' || item.dataset.status === status;
        const matchSearch = !q || item.dataset.search.includes(q);
        const show = matchStatus && matchSearch;
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const noResults = document.getElementById('tobaccoNoResults');
    if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
}

function openTobaccoEditSheet() {
    document.getElementById('viewTabaccosListInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>';
    openSheet('viewTabaccosList');
    loadTobaccoEditSheet();
}

function loadTobaccoEditSheet() {
    tobaccoListChanges = {};
    fetch('/tobaccos/edit-venue/sheet')
        .then(r => r.text())
        .then(html => {
            document.getElementById('viewTabaccosListInner').innerHTML = html;
            requestAnimationFrame(() => {
                document.querySelectorAll('#editTobaccoListWrap .editTobaccoBrandCard').forEach(card => {
                    card.classList.add('--collapseReady');
                });
            });
        })
        .catch(() => { document.getElementById('viewTabaccosListInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Не удалось загрузить</p>'; });
}

let tobaccoListChanges = {};
let editTobaccoSearchTimer;

function syncEditTobaccoRow(row) {
    const change = tobaccoListChanges[row.dataset.tobaccoId];
    const active = change ? change.active : row.dataset.originalActive === '1';
    row.classList.toggle('--actionTabSearchSectionResultActive', active);
    row.querySelector('.editTobaccoCommentWrap').style.display = change ? 'block' : 'none';
    row.querySelector('.editTobaccoComment').value = change ? change.comment : '';
}

function syncEditTobaccoRows(scope) {
    scope.querySelectorAll('.editTobaccoRow').forEach(syncEditTobaccoRow);
}

function toggleEditBrand(top) {
    const card = top.closest('.editTobaccoBrandCard');
    card.classList.toggle('actionTabViewOrderCardShow');
    if (card.dataset.loaded === '1' || !card.classList.contains('actionTabViewOrderCardShow')) return;

    card.dataset.loaded = '1';
    const inner = card.querySelector('.editTobaccoRowsInner');
    inner.innerHTML = '<p style="padding:12px; color:var(--info); text-align:center;">Загрузка...</p>';

    fetch(`/tobaccos/edit-venue/brand/${card.dataset.brandId}`)
        .then(r => r.text())
        .then(html => {
            inner.innerHTML = html;
            syncEditTobaccoRows(inner);
        })
        .catch(() => {
            card.dataset.loaded = '0';
            inner.innerHTML = '<p style="padding:12px; color:var(--info); text-align:center;">Не удалось загрузить</p>';
        });
}

function toggleEditTobaccoCheckbox(btn) {
    const row = btn.closest('.editTobaccoRow');
    const id = row.dataset.tobaccoId;
    const isNowActive = !row.classList.contains('--actionTabSearchSectionResultActive');

    if (isNowActive === (row.dataset.originalActive === '1')) {
        delete tobaccoListChanges[id];
    } else {
        tobaccoListChanges[id] = { active: isNowActive, comment: tobaccoListChanges[id]?.comment || '' };
    }

    syncEditTobaccoRow(row);
}

function runEditTobaccoSearch(q) {
    const list = document.getElementById('editTobaccoBrandList');
    const results = document.getElementById('editTobaccoSearchResults');

    if (!q) {
        results.style.display = 'none';
        results.innerHTML = '';
        list.style.display = '';
        syncEditTobaccoRows(list);
        return;
    }

    fetch(`/tobaccos/edit-venue/search?q=${encodeURIComponent(q)}`)
        .then(r => r.text())
        .then(html => {
            if (document.getElementById('editTobaccoListSearch').value.trim() !== q) return;
            list.style.display = 'none';
            results.style.display = '';
            results.innerHTML = html;
            syncEditTobaccoRows(results);
            requestAnimationFrame(() => results.querySelectorAll('.editTobaccoBrandCard').forEach(c => c.classList.add('--collapseReady')));
        })
        .catch(() => {});
}

document.addEventListener('input', function (e) {
    if (!e.target) return;

    if (e.target.classList && e.target.classList.contains('editTobaccoComment')) {
        const change = tobaccoListChanges[e.target.closest('.editTobaccoRow').dataset.tobaccoId];
        if (change) change.comment = e.target.value;
    }

    if (e.target.id === 'editTobaccoListSearch') {
        clearTimeout(editTobaccoSearchTimer);
        editTobaccoSearchTimer = setTimeout(() => runEditTobaccoSearch(e.target.value.trim()), 250);
    }
});

function saveTobaccoListChanges() {
    const errorEl = document.getElementById('editTobaccoSaveError');
    errorEl.style.display = 'none';

    const changes = Object.keys(tobaccoListChanges).map(id => ({
        tobacco_id: parseInt(id),
        active: tobaccoListChanges[id].active,
        comment: tobaccoListChanges[id].comment || null,
    }));

    if (changes.length === 0) {
        closeSheet('viewTabaccosList');
        return;
    }

    fetch('/tobaccos/update-venue-bulk', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ changes }),
    })
        .then(async r => {
            if (!r.ok) {
                const data = await r.json().catch(() => ({}));
                throw new Error(data.message || 'Ошибка при сохранении');
            }
            return r;
        })
        .then(() => {
            tobaccoListChanges = {};
            closeSheet('viewTabaccosList');
            loadTobaccoList();
        })
        .catch(err => {
            errorEl.textContent = err.message || 'Ошибка при сохранении';
            errorEl.style.display = '';
        });
}


function toggleCollapse(btn) {
    const card = btn.closest('.actionTabViewOrderCardHide');
    card.classList.toggle('actionTabViewOrderCardShow');
}

function loadTobaccoList() {
    fetch('/tobaccos/stats-partial')
        .then(r => r.text())
        .then(html => {
            const el = document.getElementById('tobaccoHeaderStats');
            if (el) el.innerHTML = html;
        })
        .catch(() => {});

    fetch('/tobaccos/list-partial')
        .then(r => r.text())
        .then(html => {
            const el = document.getElementById('tobaccoList');
            if (el) {
                el.innerHTML = html;
                filterTobaccoList();
            }
        })
        .catch(() => {});
}

function openDeactivationLogSheet() {
    document.getElementById('viewTabaccoDeactiveListInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>';
    openSheet('viewTabaccoDeactiveList');
    fetch('/tobaccos/deactivation-log/sheet')
        .then(r => r.text())
        .then(html => { document.getElementById('viewTabaccoDeactiveListInner').innerHTML = html; })
        .catch(() => { document.getElementById('viewTabaccoDeactiveListInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Не удалось загрузить</p>'; });
}

function loadMoreDeactivationLogs(lastId) {
    const btn = document.getElementById('deactivationLogMoreBtn');
    if (btn) btn.textContent = 'Загрузка...';

    fetch(`/tobaccos/deactivation-log/more?last_id=${lastId}`)
        .then(r => r.text())
        .then(html => {
            if (btn) btn.remove();
            document.getElementById('deactivationLogRows').insertAdjacentHTML('beforeend', html);
        })
        .catch(() => { if (btn) btn.textContent = 'Ошибка, повторить'; });
}

document.addEventListener('DOMContentLoaded', () => filterTobaccoList());