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
<script src="/assets/js/tobaccos.js?v={{ filemtime(public_path('/assets/js/tobaccos.js')) }}"></script>
@endpush