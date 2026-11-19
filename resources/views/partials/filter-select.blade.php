@php $selected = $selected ?? array_key_first($options); @endphp
<div class="defaultForm customSelect" id="{{ $id }}" @if(!empty($full)) style="grid-column:1/-1" @endif>
    <div class="customSelectValue" data-placeholder="{{ $options[$selected] }}">{{ $options[$selected] }}</div>
    <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l4 4 4-4"></path></svg>
    <input type="hidden" id="{{ $id }}Value" value="{{ $selected }}">
    <div class="customSelectDropdown">@foreach($options as $value => $label)<div class="customSelectOption {{ (string) $value === (string) $selected ? '--active' : '' }}" data-value="{{ $value }}">{{ $label }}</div>@endforeach</div>
</div>
