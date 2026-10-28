@foreach($mixes as $mix)
    @php
        $strengthLabels = ['light' => 'Лёгкая', 'medium' => 'Средняя', 'strong' => 'Крепкая'];
        $mainIngredient = $mix->ingredients->first();
        $mainTobacco = $mainIngredient?->tobacco;
        $flavorTags = $mix->ingredients->flatMap(fn($i) => $i->tobacco?->flavorTags ?? collect())->unique('id')->values();
        $categories = $flavorTags->map(fn($t) => $t->category)->filter()->unique('id');
        $searchData = mb_strtolower(implode(' ', array_filter([
            $mix->name,
            $mix->description_short,
            $mix->creator?->name,
            $mainTobacco?->name,
            $mainTobacco?->brand?->name,
            $categories->pluck('name')->implode(' '),
            $flavorTags->pluck('name')->implode(' '),
        ])), 'UTF-8');
    @endphp
    <div class="productListItem"
            data-mix-id="{{ $mix->id }}"
            data-status="{{ $mix->is_active ? 'active' : 'inactive' }}"
            data-mine="{{ $mix->created_by == auth()->id() ? '1' : '0' }}"
            data-search="{{ $searchData }}">
        <div class="productListItemTop">
            <div class="productListItemTopInfo">
                <img src="/assets/img/brands/mix.svg" alt="">

                <div class="productListItemTopBody">
                    <p class="productListItemToptitle">{{ $mix->name }}</p>
                    <p class="productListItemTopDescription">{{ $mix->description_short }}</p>
                </div>
            </div>

            <div class="productListItemTopId">#{{ $mix->id }}</div>
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

        @if($flavorTags->count())
        <div class="productListItemTastes">
            @foreach($flavorTags as $tag)
            <div class="productListItemTastesItem">
                <span>{{ $tag->name }}</span>
            </div>
            @endforeach
        </div>
        @endif

        <div class="productListItemBottom">
            <div class="productListItemLeft">
                <div class="productListItemLeftItem">
                    <img src="/assets/img/master-create.svg" alt="">
                    <span>{{ $mix->creator?->name ?? '—' }}</span>
                </div>
            </div>

            <button type="button" onclick="openMixSheet({{ $mix->id }})" class="productListItemBottomButton">
                Открыть
            </button>
        </div>
    </div>
@endforeach

<p id="mixNoResults" style="display:none; color:var(--info); text-align:center; padding:20px;">Ничего не найдено</p>