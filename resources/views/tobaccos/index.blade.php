@extends('layouts.app')

@section('main-class', 'tabaccos')

@section('content')

<header>
    <div class="headerTop">
        <h1>Табаки</h1>

        @include('partials.shift-toggle')

        @role('admin')
        <a href="{{ route('settings.index') }}" class="headerUser">
            <img src="/assets/img/brands/mix.svg" alt="">
        </a>
        @else
        <a href="{{ route('wiki.index') }}" class="headerUser">
            <img src="/assets/img/brands/mix.svg" alt="">
        </a>
        @endrole
    </div>

    <div class="headerStatsWrapper">
        <div class="headerStats" id="tobaccoHeaderStats">
            @include('tobaccos.partials.header-stats')
        </div>

        @include('partials.catalog-tabs')

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
        <div class="filtersRow" style="display:grid; grid-template-columns:repeat(2, 1fr);">
            <div class="defaultForm customSelect" id="tobaccoStatusSelect">
                <div class="customSelectValue" data-placeholder="Активные">Активные</div>
                <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l4 4 4-4"></path></svg>
                <input type="hidden" id="tobaccoStatusValue" value="active">
                <div class="customSelectDropdown"><div class="customSelectOption --active" data-value="active">Активные</div><div class="customSelectOption" data-value="inactive">Деактивные</div><div class="customSelectOption" data-value="all">Все</div></div>
            </div>
            <div class="defaultForm customSelect" id="tobaccoStockSelect">
                <div class="customSelectValue" data-placeholder="Любое наличие">Любое наличие</div>
                <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l4 4 4-4"></path></svg>
                <input type="hidden" id="tobaccoStockValue" value="any">
                <div class="customSelectDropdown"><div class="customSelectOption --active" data-value="any">Любое наличие</div><div class="customSelectOption" data-value="ok">Есть</div><div class="customSelectOption" data-value="low">Мало</div><div class="customSelectOption" data-value="none">Нет</div>@role('admin')<div class="customSelectOption" data-value="off">Без учёта</div>@endrole</div>
            </div>
            @include('partials.filter-select', ['id' => 'tobaccoSortSelect', 'selected' => 'name', 'full' => true, 'options' => ['name' => 'По имени', 'count_desc' => 'Популярные', 'count_asc' => 'Непопулярные']])
        </div>
    </div>
    </div>
</header>

<section class="productList" id="tobaccoList">
    @include('tobaccos.partials.list-items')
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
            <h3>Список табаков</h3>
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
<script src="/assets/js/tobaccos.js?v={{ filemtime(public_path('/assets/js/tobaccos.js')) }}"></script>
@endpush