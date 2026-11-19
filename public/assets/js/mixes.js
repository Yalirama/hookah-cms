function filterMixList() {
    const activeFilterBtn = document.querySelector('#mixList').closest('main').querySelector('.filtersBtn.active');
    const status = activeFilterBtn ? activeFilterBtn.dataset.status : 'active';
    const searchInput = document.getElementById('mixSearch');
    const q = searchInput ? searchInput.value.trim().toLowerCase() : '';

    const items = document.querySelectorAll('#mixList .productListItem');
    let visible = 0;

    items.forEach(item => {
        let matchStatus;
        if (status === 'all') matchStatus = true;
        else if (status === 'mine') matchStatus = item.dataset.mine === '1';
        else matchStatus = item.dataset.status === status;

        const matchSearch = !q || item.dataset.search.includes(q);
        const show = matchStatus && matchSearch;
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const noResults = document.getElementById('mixNoResults');
    if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.filtersBtn[data-status]');
    if (!btn) return;
    document.querySelectorAll('.filtersBtn[data-status]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterMixList();
});

document.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'mixSearch') filterMixList();
});

function loadMixList() {
    fetch('/mixes/stats-partial')
        .then(r => r.text())
        .then(html => {
            const el = document.getElementById('mixHeaderStats');
            if (el) el.innerHTML = html;
        })
        .catch(() => {});

    fetch('/mixes/list-partial')
        .then(r => r.text())
        .then(html => {
            const el = document.getElementById('mixList');
            if (el) { el.innerHTML = html; filterMixList(); }
        })
        .catch(() => {});
}

document.addEventListener('DOMContentLoaded', () => filterMixList());

let currentMixSheetId = null;

function openMixSheet(id) {
    currentMixSheetId = id;
    document.getElementById('viewMixInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>';
    openSheet('viewMix');
    loadMixSheet(id);
}

function loadMixSheet(id) {
    fetch(`/mixes/${id}/sheet`)
        .then(r => r.text())
        .then(html => { document.getElementById('viewMixInner').innerHTML = html; })
        .catch(() => { document.getElementById('viewMixInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Не удалось загрузить</p>'; });
}

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.closest('#viewMixInner')) {
        e.preventDefault();
        const fd = new FormData(form);
        fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(async r => {
                if (!r.ok) {
                    const data = await r.json().catch(() => ({}));
                    throw new Error(data.message || 'Ошибка при сохранении');
                }
                return r;
            })
            .then(() => { loadMixSheet(currentMixSheetId); loadMixList(); })
            .catch(err => alert(err.message || 'Ошибка при сохранении'));
    }
});

function openMixHistorySheet(id, name) {
    document.getElementById('mixHistoryTitle').textContent = 'Микс #' + id + (name ? ' — ' + name : '');
    document.getElementById('viewMixStoryInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>';
    openSheet('viewMixStoryTab');
    fetch(`/mixes/${id}/history/sheet`)
        .then(r => r.text())
        .then(html => { document.getElementById('viewMixStoryInner').innerHTML = html; })
        .catch(() => { document.getElementById('viewMixStoryInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Не удалось загрузить</p>'; });
}

let mixTobaccosCache = null;

function getMixTobaccos() {
    if (mixTobaccosCache) return Promise.resolve(mixTobaccosCache);
    return fetch('/mixes/tobaccos-list')
        .then(r => r.json())
        .then(data => { mixTobaccosCache = data; return data; });
}

function toggleMixIngredientSearch(rowId, type) {
    const wrap = document.getElementById(`${rowId}_search_${type}`);
    if (!wrap) return;
    const isOpening = wrap.style.display === 'none';
    wrap.style.display = isOpening ? 'block' : 'none';
    if (isOpening) filterMixTobaccoOptions(rowId, type, '');
}

function esc(v) {
    return String(v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function filterMixTobaccoOptions(rowId, type, query) {
    const optionsEl = document.getElementById(`${rowId}_options_${type}`);
    if (!optionsEl) return;

    getMixTobaccos().then(tobaccos => {
        const q = query.trim().toLowerCase();
        const mainId = parseInt(document.getElementById(`${rowId}_tobaccoId`)?.value) || 0;
        const altIds = new Set([...document.querySelectorAll(`#${rowId}_alts input[type="hidden"]`)].map(i => parseInt(i.value)));

        const filtered = tobaccos
            .filter(t => type === 'alt' ? t.id !== mainId : !altIds.has(t.id))
            .filter(t => !q || t.search.includes(q))
            .slice(0, q ? 50 : 40);

        if (!filtered.length) { optionsEl.innerHTML = '<div class="mixOptEmpty">Ничего не найдено</div>'; return; }

        optionsEl.innerHTML = filtered.map(t => {
            const on = type === 'alt' && altIds.has(t.id);
            const handler = t.off ? '' : (type === 'alt'
                ? `onclick='toggleMixAlt("${rowId}", ${t.id}, ${esc(JSON.stringify(t.brand))}, ${esc(JSON.stringify(t.name))}, this)'`
                : `onclick='selectMixTobacco("${rowId}", "main", ${t.id}, ${esc(JSON.stringify(t.brand))}, ${esc(JSON.stringify(t.name))})'`);
            return `<div class="ordersCardTop mixOpt ${type === 'alt' ? 'mixOptAlt' : ''} ${on ? '--on' : ''} ${t.off ? '--unavailable' : ''}" ${handler}>
                <div class="ordersCardUser">
                    <div class="ordersCardUserInfo">
                        <div class="actionTabViewOrderProductListBody">
                            <p>${esc(t.brand)} - ${esc(t.name)}</p>
                            ${t.off ? `<span class="addItemOff">${t.off}</span>` : ''}
                        </div>
                    </div>
                </div>
                ${type === 'alt' && !t.off ? '<i class="mixChk"></i>' : ''}
            </div>`;
        }).join('');
    });
}

function getMixIngredientUsedIds(rowId) {
    const used = new Set();
    const mainId = document.getElementById(`${rowId}_tobaccoId`)?.value;
    if (mainId) used.add(parseInt(mainId));

    document.querySelectorAll(`#${rowId}_alts input[type="hidden"]`).forEach(input => {
        used.add(parseInt(input.value));
    });

    return used;
}

function removeMixAlt(btn) {
    const tag = btn.closest('.mixAltTag');
    const row = tag.closest('.mixIngredientRow');
    tag.remove();
    const opts = row.querySelector('[id$="_options_alt"]');
    if (opts && opts.offsetParent) filterMixTobaccoOptions(row.id, 'alt', row.querySelector('[id$="_search_alt"] input')?.value || '');
}

function toggleMixAlt(rowId, id, brand, name, el) {
    const altsWrap = document.getElementById(`${rowId}_alts`);
    const existing = [...altsWrap.querySelectorAll('input[type="hidden"]')].find(i => parseInt(i.value) === id);
    if (existing) {
        existing.closest('.mixAltTag').remove();
        el.classList.remove('--on');
        return;
    }
    const index = document.getElementById(rowId).dataset.index;
    const altIndex = Math.max(-1, ...[...altsWrap.children].map(c => parseInt(c.dataset.altIndex))) + 1;
    const tag = document.createElement('p');
    tag.className = 'actionTabSearchSectionResultText mixAltTag';
    tag.dataset.altIndex = altIndex;
    tag.innerHTML = `<span class="mixAltName"><b>${esc(brand)} -</b> ${esc(name)}<input type="hidden" name="ingredients[${index}][alternatives][${altIndex}][tobacco_id]" value="${id}"></span>
        <button class="--button-03f2d" type="button" onclick="removeMixAlt(this)"><img src="/assets/img/close-icon.svg" alt=""></button>`;
    altsWrap.appendChild(tag);
    el.classList.add('--on');
}

function selectMixTobacco(rowId, type, id, brand, name) {
    document.getElementById(`${rowId}_tobaccoId`).value = id;
    document.getElementById(`${rowId}_label`).innerHTML = `<p>${esc(brand)}</p><span>${esc(name)}</span>`;
    document.getElementById(`${rowId}_search_main`).style.display = 'none';
}

let mixIngredientIndex = 0;

function addMixIngredient(formId) {
    const wrap = document.getElementById(`${formId}Ingredients`);
    const index = wrap.children.length;
    const rowId = `${formId}_ing${index}`;

    const html = `
        <div class="actionTabViewOrderCard mixIngredientRow" data-index="${index}" id="${rowId}">
            <div class="actionTabViewOrderCardTop">
                <div class="actionTabCntRow0md23 mixIngredientMainLabel" id="${rowId}_label">
                    <p>Основной вкус #${index + 1}</p>
                </div>
                <button type="button" onclick="toggleMixIngredientSearch('${rowId}', 'main')">
                    <img src="/assets/img/edit-icon-gold.svg" alt="">
                    <span>Добавить</span>
                </button>
            </div>

            <input type="hidden" name="ingredients[${index}][tobacco_id]" id="${rowId}_tobaccoId" value="">
            <input type="hidden" name="ingredients[${index}][grams]" id="${rowId}_grams" value="">
            <input type="hidden" name="ingredients[${index}][unit]" id="${rowId}_unit" value="g">

            <div class="mixIngredientSearchWrap" id="${rowId}_search_main" style="display:none;">
                <div class="search">
                    <img src="/assets/img/search-icon.svg" alt="">
                    <input type="text" placeholder="Бренд, название, вкус, тег..." oninput="filterMixTobaccoOptions('${rowId}', 'main', this.value)">
                </div>
                <div class="actionTabSearchSectionResult mixTobaccoOptions --actionTabSearchSectionResult-vc31f4" id="${rowId}_options_main"></div>
            </div>

            <div class="actionTabSearchSectionResult">
                <div class="actionTabSearchSectionResultHead">
                    <p class="actionTabSearchSectionResultTitle">Альтернативы</p>
                    <button type="button" onclick="toggleMixIngredientSearch('${rowId}', 'alt')">
                        <img src="/assets/img/edit-icon-gold.svg" alt="">
                        <span>Добавить</span>
                    </button>
                </div>
                <div class="mixIngredientSearchWrap" id="${rowId}_search_alt" style="display:none;">
                    <div class="search">
                        <img src="/assets/img/search-icon.svg" alt="">
                        <input type="text" placeholder="Бренд, название, вкус, тег..." oninput="filterMixTobaccoOptions('${rowId}', 'alt', this.value)">
                    </div>
                    <div class="actionTabSearchSectionResult mixTobaccoOptions --actionTabSearchSectionResult-vc31f4" id="${rowId}_options_alt"></div>
                </div>
                <div class="mixAltList" id="${rowId}_alts"></div>
            </div>
        </div>`;

    wrap.insertAdjacentHTML('beforeend', html);
}

function openMixCreateSheet() {
    fetch('/mixes/create-form')
        .then(r => r.text())
        .then(html => {
            document.getElementById('createMixInner').innerHTML = html;
            openSheet('createMix');
            initAllCustomSelects(document.getElementById('createMix'));
        });
}

function openMixEditSheet(id) {
    fetch(`/mixes/${id}/edit-form`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('viewMixEditInner').innerHTML = html;
            openSheet('viewMixEditTab');
            initAllCustomSelects(document.getElementById('viewMixEditTab'));
        });
}

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.id === 'createMixForm' || form.id === 'editMixForm') {
        e.preventDefault();
        const fd = new FormData(form);
        const isEdit = form.id === 'editMixForm';

        fetch(form.action, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(async r => {
                if (!r.ok) {
                    const data = await r.json().catch(() => ({}));
                    const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                    throw new Error(firstError || data.message || 'Ошибка при сохранении');
                }
                return r;
            })
            .then(() => {
                closeSheet(isEdit ? 'viewMixEditTab' : 'createMix');
                loadMixList();
                if (isEdit && currentMixSheetId) loadMixSheet(currentMixSheetId);
            })
            .catch(err => alert(err.message || 'Ошибка при сохранении'));
    }
});

function openMixDeactivationLogSheet() {
    document.getElementById('viewMixesDeactiveListInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>';
    openSheet('viewMixesDeactiveList');
    fetch('/mixes/deactivation-log/sheet')
        .then(r => r.text())
        .then(html => { document.getElementById('viewMixesDeactiveListInner').innerHTML = html; })
        .catch(() => { document.getElementById('viewMixesDeactiveListInner').innerHTML = '<p style="padding:20px; color:var(--info); text-align:center;">Не удалось загрузить</p>'; });
}

function loadMoreMixDeactivationLogs(lastId) {
    const btn = document.getElementById('mixDeactivationLogMoreBtn');
    if (btn) btn.textContent = 'Загрузка...';

    fetch(`/mixes/deactivation-log/more?last_id=${lastId}`)
        .then(r => r.text())
        .then(html => {
            if (btn) btn.remove();
            document.getElementById('mixDeactivationLogRows').insertAdjacentHTML('beforeend', html);
        })
        .catch(() => { if (btn) btn.textContent = 'Ошибка, повторить'; });
}