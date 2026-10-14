<div class="item" data-wiki-id="{{ $page->id }}">
    <div class="itemInfo">
        <p>{{ $page->title }}</p>
        <span>{{ $page->description ?? '—' }}</span>
    </div>

    @unless($page->is_published)
        <span class="badge" style="color:rgba(192, 87, 74, 1);font-size: calc(12vw / var(--resizer) * 100);line-height: calc(12vw / var(--resizer) * 100);">Скрыта</span>
    @endunless

    <button type="button" onclick="openWikiViewSheet({{ $page->id }})" class="btnOpen">Открыть</button>
    <button type="button" onclick="openWikiEditSheet({{ $page->id }})" class="btnOpen btnEdit">Редактировать</button>
    <button type="button" onclick="openWikiToggleConfirm({{ $page->id }}, '{{ addslashes($page->title) }}', {{ $page->is_published ? 'true' : 'false' }})"
        class="btnOpen {{ $page->is_published ? 'btnCancel' : 'btnBlue' }}">
        {{ $page->is_published ? 'Деактивировать' : 'Активировать' }}
    </button>
</div>