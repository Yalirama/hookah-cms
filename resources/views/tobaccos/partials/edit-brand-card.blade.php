@php $lazy = $lazy ?? true; @endphp
<div class="actionTabViewOrderCard actionTabViewOrderCardHide editTobaccoBrandCard {{ $lazy ? '' : 'actionTabViewOrderCardShow' }}" data-brand-id="{{ $brand->id }}" data-loaded="{{ $lazy ? '0' : '1' }}">
    <div class="actionTabViewOrderCardTop" onclick="toggleEditBrand(this)">
        <p class="actionTabTitle">{{ $brand->name }} <span style="color:var(--info); font-weight:400;">({{ $lazy ? $brand->active_count . '/' . $brand->tobaccos_count : 'найдено ' . $tobaccos->count() }})</span></p>
        <button type="button" class="arrowDown"><img src="/assets/img/arrow-down.svg" alt=""></button>
    </div>
    <div class="actionTabSearchSectionResult">
        <div class="editTobaccoRowsInner">
            @unless($lazy)
                @include('tobaccos.partials.edit-rows', ['tobaccos' => $tobaccos])
            @endunless
        </div>
    </div>
</div>