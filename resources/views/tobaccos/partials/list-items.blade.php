@foreach($tobaccos as $tobacco)
    @php
        $strengthIcons = ['light' => '🟢', 'medium' => '🟡', 'strong' => '🔴'];
        $strengthLabels = ['light' => 'Лёгкая', 'medium' => 'Средняя', 'strong' => 'Крепкая'];
        $categories = $tobacco->flavorTags->map(fn($t) => $t->category)->filter()->unique('id');
        $isActive = (bool) $tobacco->venueTobacco?->is_active;
        $searchData = mb_strtolower(implode(' ', array_filter([
            $tobacco->name,
            $tobacco->brand?->name,
            $tobacco->description_short,
            $tobacco->flavorTags->pluck('name')->implode(' '),
        ])), 'UTF-8');
    @endphp
    <div class="productListItem"
            data-tobacco-id="{{ $tobacco->id }}"
            data-status="{{ $isActive ? 'active' : 'inactive' }}"
            data-search="{{ $searchData }}">
        <div class="productListItemTop">
            <div class="productListItemTopInfo">
                <img src="{{ $tobacco->brand?->image_url ?? '/assets/img/product-img.webp' }}" alt="">

                <div class="productListItemTopBody">
                    <p class="productListItemToptitle">{{ $tobacco->brand?->name ?? '—' }}</p>
                    <p class="productListItemTopDescription">{{ $tobacco->name }}{{ $tobacco->alt_name ? ' - ' . $tobacco->alt_name : '' }}</p>
                </div>
            </div>

            <div class="productListItemTopId">#{{ $tobacco->id }}</div>
        </div>

        @if($categories->count())
        <div class="productListItemTags">
            @foreach($categories as $cat)
            <div class="productListItemTagsItem">
                <span>#{{ $cat->name }}</span>
            </div>
            @endforeach
        </div>
        @endif

        @if($tobacco->flavorTags->count())
        <div class="productListItemTastes">
            @foreach($tobacco->flavorTags as $tag)
            <div class="productListItemTastesItem">
                <span>{{ $tag->name }}</span>
            </div>
            @endforeach
        </div>
        @endif

        <div class="productListItemBottom">
            <div class="productListItemLeft">
                <div class="productListItemLeftItem">
                    <img src="/assets/img/grade-icon.svg" alt="">
                    <span>{{ $strengthLabels[$tobacco->strength] ?? '—' }}</span>
                </div>
            </div>

            <button type="button" onclick="openTobaccoSheet({{ $tobacco->id }})" class="productListItemBottomButton">
                Открыть
            </button>
        </div>
    </div>
@endforeach

<p id="tobaccoNoResults" style="display:none; color:var(--info); text-align:center; padding:20px;">Ничего не найдено</p>