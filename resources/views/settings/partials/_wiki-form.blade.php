@php $isEdit = $page !== null; @endphp

<div class="actionTabSection">
    <label class="actionTabTitle">Заголовок*</label>
    <div class="defaultForm"><input id="wiki-title" type="text" value="{{ $isEdit ? $page->title : '' }}" placeholder="Название статьи"></div>
</div>

<div class="actionTabSection">
    <label class="actionTabTitle">Краткое описание</label>
    <div class="defaultForm"><input id="wiki-description" type="text" value="{{ $isEdit ? $page->description : '' }}" placeholder="Отображается в списке статей"></div>
</div>

<div class="actionTabSection">
    <label class="actionTabTitle">Порядок сортировки</label>
    <div class="defaultForm"><input id="wiki-sort-order" type="number" min="0" value="{{ $isEdit ? $page->sort_order : ($maxSort ?? 0) }}"></div>
</div>

<div class="actionTabSection">
    <label class="switchToggle">
        <input type="checkbox" id="wiki-is-published" {{ ($isEdit ? $page->is_published : true) ? 'checked' : '' }}>
        <span class="switchToggleTrack"><span class="switchToggleThumb"></span></span>
    </label>
    <span style="margin-left:8px;">Опубликовано</span>
</div>

<div class="actionTabSection">
    <label class="actionTabTitle">Содержание</label>
    <textarea id="wiki-editor-content">{{ $isEdit ? $page->content : '' }}</textarea>
</div>