@php $isEdit = isset($page); @endphp

<div class="form-group">
    <label>Заголовок <span style="color:red">*</span></label>
    <input type="text" name="title" value="{{ old('title', $page->title ?? '') }}" required placeholder="Название статьи">
</div>

<div class="form-group">
    <label>Краткое описание</label>
    <input type="text" name="description" value="{{ old('description', $page->description ?? '') }}" placeholder="Отображается в списке статей">
</div>

<div class="form-group">
    <label>Порядок сортировки</label>
    <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? $maxSort ?? 0) }}" min="0" style="max-width:120px;">
</div>

<div class="form-group">
    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
        <input type="checkbox" name="is_published" value="1" {{ old('is_published', ($page->is_published ?? true) ? '1' : '') ? 'checked' : '' }}>
        Опубликовано
    </label>
</div>

<div class="form-group">
    <label>Содержание</label>
    <textarea name="content" id="editor">{{ old('content', $page->content ?? '') }}</textarea>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css">
@endpush

@push('scripts')
<script type="importmap">
{
    "imports": {
        "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.js",
        "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.3.1/"
    }
}
</script>
<script type="module">
import { ClassicEditor, Essentials, Bold, Italic, Heading, Link, List,
         Table, TableToolbar, Image, ImageUpload, ImageResize,
         Paragraph, BlockQuote, Indent, MediaEmbed,
         SimpleUploadAdapter } from 'ckeditor5';

ClassicEditor.create(document.getElementById('editor'), {
    plugins: [
        Essentials, Bold, Italic, Heading, Link, List,
        Table, TableToolbar, Image, ImageUpload, ImageResize,
        Paragraph, BlockQuote, Indent, SimpleUploadAdapter,
    ],
    toolbar: [
        'heading', '|',
        'bold', 'italic', '|',
        'link', 'blockQuote', '|',
        'bulletedList', 'numberedList', 'indent', 'outdent', '|',
        'insertTable', '|',
        'uploadImage', '|',
        'undo', 'redo'
    ],
    heading: {
        options: [
            { model: 'paragraph',  title: 'Параграф', class: 'ck-heading_paragraph' },
            { model: 'heading1',   view: 'h1', title: 'Заголовок 1', class: 'ck-heading_heading1' },
            { model: 'heading2',   view: 'h2', title: 'Заголовок 2', class: 'ck-heading_heading2' },
            { model: 'heading3',   view: 'h3', title: 'Заголовок 3', class: 'ck-heading_heading3' },
        ]
    },
    table: {
        contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
    },
    simpleUpload: {
        uploadUrl: '{{ route('wiki.upload-image') }}',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    },
    language: 'ru',
}).catch(err => console.error(err));
</script>
@endpush