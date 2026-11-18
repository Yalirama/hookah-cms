(function () {
    const tok = () => document.querySelector('meta[name="csrf-token"]').content;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const $ = id => document.getElementById(id);
    let timer, cur = null;

    async function api(url, body) {
        const opt = body ? { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tok(), 'Accept': 'application/json' }, body: JSON.stringify(body) } : { headers: { 'Accept': 'application/json' } };
        const r = await fetch(url, opt);
        const j = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(j.message || (j.errors ? Object.values(j.errors).flat().join(' ') : 'Ошибка ' + r.status));
        return j;
    }

    window.openCatalogSheet = function () {
        fetch('/settings/catalog/panel').then(r => r.text()).then(h => { $('catalog-panel').innerHTML = h; openSheet('settingsTab15'); });
    };
    window.catalogFilter = v => { const q = v.trim().toLowerCase(); document.querySelectorAll('#catalog-brands .catRow').forEach(r => r.style.display = !q || r.dataset.name.includes(q) ? '' : 'none'); };

    window.catalogSearch = function (v) {
        clearTimeout(timer);
        const box = $('catalog-results');
        if (v.trim().length < 2) { box.innerHTML = ''; return; }
        box.innerHTML = '<p class="statNote">Ищу…</p>';
        timer = setTimeout(() => api('/settings/catalog/search?q=' + encodeURIComponent(v.trim())).then(d => {
            box.innerHTML = d.brands.length ? d.brands.map(b => `<div class="catRow"><div class="catRowName"><p>${esc(b.name)}</p><span>${esc(b.country || '')}${b.local_id ? ' · в базе: ' + b.local_count : ' · нет в базе'}</span></div><button type="button" class="catBtn" data-slug="${esc(b.slug)}">Открыть</button></div>`).join('') : '<p class="statNote">Ничего не нашли. Проверьте написание.</p>';
            box.querySelectorAll('[data-slug]').forEach(b => b.onclick = () => catalogOpen({ slug: b.dataset.slug }));
        }).catch(e => { box.innerHTML = '<p class="statNote bad">' + esc(e.message) + '</p>'; }), 450);
    };

    window.catalogOpen = function (q) {
        const box = $('catalog-brand');
        box.innerHTML = '<div class="actionTabCnt"><p class="statNote">Сверяю с каталогом… Для большого бренда это может занять до минуты.</p></div>';
        box.scrollIntoView({ behavior: 'smooth', block: 'start' });
        api('/settings/catalog/brand?' + new URLSearchParams(q)).then(render).catch(e => { box.innerHTML = '<div class="actionTabCnt"><p class="statNote bad">' + esc(e.message) + '</p></div>'; });
    };

    function render(d) {
        cur = d;
        const b = d.brand;
        const newHtml = d.new.length ? `
            <label class="catAll"><input type="checkbox" id="cat-all" onchange="catalogAll(this.checked)"> Выбрать все (${d.new.length})</label>
            <div class="catList catScroll">${d.new.map((i, k) => `<label class="catItem"><input type="checkbox" class="cat-pick" value="${esc(i.slug)}"><span>${esc(i.name)}${i.alt ? '<small>' + esc(i.alt) + '</small>' : ''}</span></label>`).join('')}</div>
            <p class="formErrorBox" id="cat-error" style="display:none"></p>
            <div class="catProgress" id="cat-progress" style="display:none"><i></i></div>
            <button type="button" class="btnConfirm" id="cat-go" onclick="catalogImport()">Добавить выбранные</button>`
            : '<p class="statNote">Новых позиций нет: всё, что есть в каталоге, уже у вас.</p>';
        const localOnly = d.local_only.length ? `
            <div class="statFoldBox"><button type="button" class="statFold" onclick="this.parentNode.classList.toggle('open')"><span>Только у вас (нет в каталоге)</span><b>${d.local_only.length}</b></button>
            <div class="statFoldBody">${d.local_only.map(t => `<div class="catRow"><div class="catRowName"><p>${esc(t.name)}</p><span>${t.manual ? 'создан вручную' : 'из прошлых загрузок'}</span></div>${t.manual && d.new.length ? `<button type="button" class="catBtn" onclick="catalogLinkOpen(${t.id})">Привязать</button>` : ''}</div><div class="catLink" id="cl-${t.id}"></div>`).join('')}</div></div>` : '';
        $('catalog-brand').innerHTML = `<div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
            <p class="analyticsCardTitle">${esc(b.name)}</p>
            <p class="statNote">${esc(b.country || '')} В каталоге: <b>${b.total}</b> · у вас: <b>${b.local_count}</b> · новых: <b>${d.new.length}</b></p>
            <p class="statSub">Новые позиции</p>${newHtml}${localOnly}
            <button type="button" class="catBtn catBtnWide" onclick="catalogOpen({slug:'${esc(b.slug)}', fresh: 1})">Проверить заново</button></div>`;
    }

    window.catalogAll = on => document.querySelectorAll('.cat-pick').forEach(c => c.checked = on);

    window.catalogImport = async function () {
        const picked = [...document.querySelectorAll('.cat-pick:checked')].map(c => c.value);
        const err = $('cat-error');
        err.style.display = 'none';
        if (!picked.length) { err.textContent = 'Отметьте хотя бы одну позицию'; err.style.display = 'block'; return; }
        const go = $('cat-go'), bar = $('cat-progress');
        go.disabled = true; bar.style.display = 'block';
        let done = 0; const failed = [];
        for (let i = 0; i < picked.length; i += 4) {
            const chunk = picked.slice(i, i + 4);
            try {
                const r = await api('/settings/catalog/import', { slug: cur.brand.slug, items: chunk });
                done += r.done.length; failed.push(...r.failed);
            } catch (e) { err.textContent = e.message; err.style.display = 'block'; go.disabled = false; return; }
            bar.firstElementChild.style.width = Math.round((i + chunk.length) / picked.length * 100) + '%';
            go.textContent = 'Загружено ' + done + ' из ' + picked.length;
        }
        go.textContent = 'Готово: добавлено ' + done + (failed.length ? ', не удалось ' + failed.length : '');
        setTimeout(() => catalogOpen({ slug: cur.brand.slug }), 900);
    };

    window.catalogLinkOpen = function (id) {
        const box = $('cl-' + id);
        if (box.innerHTML) { box.innerHTML = ''; return; }
        box.innerHTML = `<div class="defaultForm"><select id="cls-${id}"><option value="">Выберите позицию из каталога</option>${cur.new.map(i => `<option value="${esc(i.slug)}">${esc(i.name)}${i.alt ? ' — ' + esc(i.alt) : ''}</option>`).join('')}</select></div>
            <p class="statNote">Все заказы, миксы и остатки этого табака перейдут на выбранный каталожный, а ручной будет скрыт.</p>
            <p class="formErrorBox" id="cle-${id}" style="display:none"></p><button type="button" class="btnConfirm" onclick="catalogLink(${id})">Привязать</button>`;
    };

    window.catalogLink = function (id) {
        const slug = $('cls-' + id).value, err = $('cle-' + id);
        if (!slug) { err.textContent = 'Выберите позицию'; err.style.display = 'block'; return; }
        api('/settings/catalog/link', { manual_id: id, slug: cur.brand.slug, item: slug }).then(() => catalogOpen({ slug: cur.brand.slug, fresh: 1 })).catch(e => { err.textContent = e.message; err.style.display = 'block'; });
    };

    window.catalogManualSave = function () {
        const err = $('cm-error'); err.style.display = 'none';
        const body = { brand_id: $('cm-brand').value || null, brand_name: $('cm-brand-name').value.trim() || null, name: $('cm-name').value.trim(), alt_name: $('cm-alt').value.trim() || null, strength: $('cm-strength').value, description: $('cm-desc').value.trim() || null };
        api('/settings/catalog/manual', body).then(() => openCatalogSheet()).catch(e => { err.textContent = e.message; err.style.display = 'block'; });
    };
})();
