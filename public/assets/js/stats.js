(function () {
    const box = document.getElementById('stats-content');
    if (!box) return;
    const st = { tab: box.dataset.tab, p: box.dataset.period };
    const q = id => document.getElementById(id);
    const fit = () => { const h = document.querySelector('header.defaultFixed'); if (h) box.style.paddingTop = h.offsetHeight + 'px'; };
    let timer;

    function params() {
        const u = new URLSearchParams({ p: st.p });
        if (st.p === 'month_pick') u.set('m', q('statMonth').value);
        if (st.p === 'custom') { u.set('from', q('statFrom').value); u.set('to', q('statTo').value); }
        return u;
    }

    function load() {
        box.innerHTML = '<p class="statLoading">Считаю…</p>';
        const u = params();
        fetch(box.dataset.url + '/' + st.tab + '?' + u)
            .then(r => r.ok ? r.text() : Promise.reject(r.status))
            .then(html => { box.innerHTML = html; fit(); })
            .catch(code => { box.innerHTML = '<p class="statLoading">Не удалось загрузить' + (code === 403 ? ': нет доступа' : '') + '</p>'; });
        u.set('tab', st.tab);
        history.replaceState(null, '', '?' + u);
    }

    function mark(row, attr, val) { document.querySelectorAll('#' + row + ' .filtersBtn').forEach(b => b.classList.toggle('active', b.dataset[attr] === val)); }

    q('statTabs').addEventListener('click', e => {
        const b = e.target.closest('[data-tab]'); if (!b) return; e.preventDefault();
        st.tab = b.dataset.tab; mark('statTabs', 'tab', st.tab); load();
    });
    q('statPeriods').addEventListener('click', e => {
        const b = e.target.closest('[data-period]'); if (!b) return; e.preventDefault();
        st.p = b.dataset.period; mark('statPeriods', 'period', st.p);
        q('statDates').style.display = ['custom', 'month_pick'].includes(st.p) ? '' : 'none';
        q('statMonth').style.display = st.p === 'month_pick' ? '' : 'none';
        q('statRange').style.display = st.p === 'custom' ? '' : 'none';
        fit(); load();
    });
    ['statMonth', 'statFrom', 'statTo'].forEach(id => q(id).addEventListener('change', () => { clearTimeout(timer); timer = setTimeout(load, 300); }));

    box.addEventListener('click', e => {
        const b = e.target.closest('.subTab'); if (!b) return;
        const row = b.closest('.subTabs'), g = row.dataset.group;
        row.querySelectorAll('.subTab').forEach(x => x.classList.toggle('active', x === b));
        box.querySelectorAll('[data-pane-group="' + g + '"]').forEach(p => { p.hidden = p.dataset.pane !== b.dataset.pane; });
    });

    box.addEventListener('click', e => {
        const t = e.target.closest('.statFold'); if (!t) return;
        t.closest('.statFoldBox').classList.toggle('open');
    });

    fit(); window.addEventListener('resize', fit);
    load();
})();
