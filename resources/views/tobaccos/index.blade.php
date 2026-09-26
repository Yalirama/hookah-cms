@extends('layouts.app')

@section('main-class', 'tabaccos')

@section('content')

<header>
    <div class="headerTop">
        <h1>Табаки</h1>

        @role('admin')
        <a href="{{ route('settings.index') }}" class="headerUser">
            <img src="/assets/img/brands/mix.svg" alt="">
        </a>
        @endrole
    </div>

    <div class="headerStatsWrapper">
        <div class="headerStats" id="tobaccoHeaderStats">
            @include('tobaccos.partials.header-stats')
        </div>

        @role('admin')
        <div class="headerStatsWrapperAction">
            <button onclick="openTobaccoEditSheet()" class="headerStatsButtonPrimary">
                <span>Редактировать список</span>
            </button>

            <button onclick="openDeactivationLogSheet()" class="headerStatsButtonSecondary">
                <span>Журнал деактиваций</span>
            </button>
        </div>
        @endrole
    </div>

    <div class="topNav">
        <div class="search">
            <img src="/assets/img/search-icon.svg" alt="">
            <input type="text" id="tobaccoSearch" placeholder="Название, вкус, крепость...">
        </div>

        <div class="filters">
            <div class="filtersRow">
                <button type="button" class="filtersBtn active" data-status="active">Активные</button>
                <button type="button" class="filtersBtn" data-status="inactive">Деактивные</button>
                <button type="button" class="filtersBtn" data-status="all">Все</button>
            </div>
        </div>
    </div>
</header>

<section class="productList" id="tobaccoList">
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
</section>

{{-- sheet: карточка табака --}}
<div class="actionTab viewProductSameTab viewTabaccos" id="viewTabaccos">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Карточка табака</h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('viewTabaccos')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div id="viewTabaccosInner">
            <p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>
        </div>
    </div>
</div>

@role('admin')
<div class="actionTab viewTabaccosList" id="viewTabaccosList">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Редактирование списка табаков</h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('viewTabaccosList')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div class="viewTabaccosListInner" id="viewTabaccosListInner">
            <p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>
        </div>
    </div>
</div>

<div class="actionTab viewTabaccoDeactiveList" id="viewTabaccoDeactiveList">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Журнал деактиваций</h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('viewTabaccoDeactiveList')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div class="viewTabaccoDeactiveListWrapper" id="viewTabaccoDeactiveListInner">
            <p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>
        </div>
    </div>
</div>
@endrole

@endsection

@push('scripts')
<script src="/assets/js/tobaccos.js"></script>
@endpush