@extends('layouts.app')

@section('main-class', 'badges')

@section('content')
<header>
    <div class="headerTop">
        <h1>Ярлыки</h1>

        @role('admin')
        <a href="{{ route('settings.index') }}" class="headerUser">
            <img src="/assets/img/brands/mix.svg" alt="">
        </a>
        @endrole
    </div>
    <div class="headerStats">
        <div class="headerStat">
            <p id="labels-total">{{ $stats['total'] }}</p>
            <span>Всего</span>
        </div>
    </div>
</header>

<form action="#" class="topNav" onsubmit="return false;">
    <div class="topNavRow0vd32">
        <div class="search">
            <img src="/assets/img/search-icon.svg" alt="">
            <input type="text" id="labels-search" placeholder="Название, описание...">
        </div>
        <div class="headerStatsWrapperAction">
            <button type="button" onclick="openSheet('createBadge')" class="headerStatsButtonPrimary">
                <span>Создать</span>
            </button>
        </div>
    </div>
</form>

<section class="badgesSection" id="labels-list">
    @foreach($labels as $label)
        @include('labels.partials._row', ['label' => $label])
    @endforeach
</section>
<div id="labels-no-results" style="display:none; text-align:center; color:#999; padding:40px;">Ничего не найдено</div>

{{-- View sheet --}}
<div class="actionTab viewProductSameTab viewBadge" id="viewBadge">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Карточка ярлыка</h3>
            <div class="actionTabHeaderBtns">
                <button onclick="openEditBadge()" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/edit-icon-gold-lg.svg" alt="">
                </button>
                <button onclick="closeSheet('viewBadge')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div id="viewBadge-body"></div>
    </div>
</div>

{{-- Create sheet --}}
<div class="actionTab viewProductSameTab createBadge" id="createBadge">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Создать ярлык</h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('createBadge')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="actionTabCnt actionTabCnt6nsd5 --fx1">
            <div class="actionTabSection">
                <label class="actionTabTitle">Название*</label>
                <div class="defaultForm"><input id="create-name" type="text" placeholder="Средние табаки, цитрус, турка, холодок..."></div>
            </div>
            <div class="actionTabSection">
                <label class="actionTabTitle">Описание</label>
                <div class="defaultForm"><textarea id="create-description" rows="3"></textarea></div>
            </div>
            <div class="actionTabSection">
                <label class="actionTabTitle">Заметка</label>
                <div class="defaultForm"><textarea id="create-note" rows="3"></textarea></div>
            </div>
        </div>
        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button onclick="submitCreateBadge()" class="actionTabActionsButton --buttonPrimary">Сохранить</button>
                <button onclick="closeSheet('createBadge')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit sheet --}}
<div class="actionTab viewProductSameTab editBadge" id="editBadge">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Редактировать ярлык</h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('editBadge')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="actionTabCnt actionTabCnt6nsd5 --fx1">
            <div class="actionTabSection">
                <label class="actionTabTitle">Название*</label>
                <div class="defaultForm"><input id="edit-name" type="text"></div>
            </div>
            <div class="actionTabSection">
                <label class="actionTabTitle">Описание</label>
                <div class="defaultForm"><textarea id="edit-description" rows="3"></textarea></div>
            </div>
        </div>
        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button onclick="submitEditBadge()" class="actionTabActionsButton --buttonPrimary">Сохранить</button>
                <button onclick="closeSheet('editBadge')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>

@include('partials.guest-sheets')

{{-- History sheet (stacked over viewBadge) --}}
<div class="actionTab viewMixStoryTab" id="viewLabelHistoryTab">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>История изменений <br> <span id="labelHistoryTitle"></span></h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('viewLabelHistoryTab')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="viewTabaccoDeactiveListWrapper" id="labelHistoryInner"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/guest-sheet.js"></script>
<script>
let currentLabel = null;

document.getElementById('labels-search').addEventListener('input', function() {
    const q = mbLower(this.value);
    let visible = 0;
    document.querySelectorAll('#labels-list .item').forEach(el => {
        const match = !q || el.dataset.search.includes(q);
        el.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('labels-no-results').style.display = visible === 0 ? 'block' : 'none';
});

function mbLower(s) { return s.toLowerCase(); }

function openLabelSheet(id) {
    fetch(`/labels/${id}/sheet`)
        .then(r => r.json())
        .then(data => {
            currentLabel = data;
            document.getElementById('viewBadge-body').innerHTML = data.html;
            openSheet('viewBadge');
        });
}

function openEditBadge() {
    if (!currentLabel) return;
    document.getElementById('edit-name').value = currentLabel.name;
    document.getElementById('edit-description').value = currentLabel.description ?? '';
    openSheet('editBadge');
}

function submitEditBadge() {
    fetch(`/labels/${currentLabel.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            name: document.getElementById('edit-name').value,
            description: document.getElementById('edit-description').value,
        }),
    })
    .then(r => r.json())
    .then(() => {
        closeSheet('editBadge');
        const id = currentLabel.id;
        openLabelSheet(id);
        const row = document.querySelector(`#labels-list .item[data-id="${id}"]`);
        row.dataset.name = document.getElementById('edit-name').value;
        row.dataset.description = document.getElementById('edit-description').value;
        row.dataset.search = mbLower(row.dataset.name + ' ' + row.dataset.description);
        row.querySelector('span').textContent = row.dataset.name;
    });
}

function submitCreateBadge() {
    fetch(`/labels`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            name: document.getElementById('create-name').value,
            description: document.getElementById('create-description').value,
            note: document.getElementById('create-note').value,
        }),
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('labels-list').insertAdjacentHTML('afterbegin', data.row);
        document.getElementById('labels-total').textContent = data.total;
        document.getElementById('create-name').value = '';
        document.getElementById('create-description').value = '';
        document.getElementById('create-note').value = '';
        closeSheet('createBadge');
    });
}

function toggleBadge() {
    fetch(`/labels/${currentLabel.id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('labels-total').textContent = data.total;
        openLabelSheet(currentLabel.id);
        const row = document.querySelector(`#labels-list .item[data-id="${currentLabel.id}"]`);
        row.dataset.active = data.is_active ? '1' : '0';
    });
}

function addBadgeNote() {
    const note = document.getElementById('view-new-note').value;
    if (!note) return;
    fetch(`/labels/${currentLabel.id}/notes`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ note }),
    }).then(() => openLabelSheet(currentLabel.id));
}

function deleteBadgeNote(noteId) {
    fetch(`/labels/${currentLabel.id}/notes/${noteId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    }).then(() => openLabelSheet(currentLabel.id));
}

function revealMore(el) {
    el.closest('.actionTabCntRow2nvj3, .actionTabCnt').querySelectorAll('.item.hidden').forEach(i => i.classList.remove('hidden'));
    el.remove();
}

function openLabelHistorySheet(id, name) {
    document.getElementById('labelHistoryTitle').textContent = name;
    fetch(`/labels/${id}/history-sheet`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('labelHistoryInner').innerHTML = html;
            openSheet('viewLabelHistoryTab');
        });
}
</script>
@endpush