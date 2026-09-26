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

function toggleEditTobaccoCheckbox(id) {
    const row = document.querySelector(`.editTobaccoRow[data-tobacco-id="${id}"]`);
    const commentWrap = document.getElementById('editTobaccoCommentWrap' + id);
    if (!row || !commentWrap) return;

    const originalActive = row.dataset.originalActive === '1';
    const isNowActive = row.classList.toggle('--actionTabSearchSectionResultActive');

    if (isNowActive === originalActive) {
        delete tobaccoListChanges[id];
        commentWrap.style.display = 'none';
    } else {
        tobaccoListChanges[id] = { active: isNowActive, comment: '' };
        commentWrap.style.display = 'block';
    }
}

document.addEventListener('input', function (e) {
    if (e.target && e.target.id && e.target.id.startsWith('editTobaccoComment')) {
        const id = e.target.id.replace('editTobaccoComment', '');
        if (tobaccoListChanges[id]) {
            tobaccoListChanges[id].comment = e.target.value;
        }
    }

    if (e.target && e.target.id === 'editTobaccoListSearch') {
        const q = e.target.value.trim().toLowerCase();

        document.querySelectorAll('#editTobaccoListWrap .editTobaccoBrandCard').forEach(card => {
            const rows = card.querySelectorAll('.editTobaccoRow');
            let visibleCount = 0;

            rows.forEach(row => {
                const show = !q || row.dataset.search.includes(q);
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            card.style.display = visibleCount === 0 ? 'none' : '';

            if (q && visibleCount > 0) {
                card.classList.add('actionTabViewOrderCardShow');
            } else if (!q) {
                card.classList.remove('actionTabViewOrderCardShow');
            }
        });
    }
});

function saveTobaccoListChanges() {
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
        .catch(err => alert(err.message || 'Ошибка при сохранении'));
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