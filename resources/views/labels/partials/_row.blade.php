<div class="item @if(!$label->is_active) --deac @endif" data-id="{{ $label->id }}" data-active="{{ $label->is_active ? '1' : '0' }}"
     data-name="{{ $label->name }}" data-description="{{ $label->description }}"
     data-search="{{ mb_strtolower($label->name . ' ' . $label->description, 'UTF-8') }}"
     onclick="openLabelSheet({{ $label->id }})">
    <span>{{ $label->name }}</span>
</div>