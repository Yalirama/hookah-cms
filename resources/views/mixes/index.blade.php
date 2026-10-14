@extends('layouts.app')

@section('main-class', 'tabaccos')

@section('content')

<header>
    <div class="headerTop">
        <h1>Миксы</h1>

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
        <div class="headerStats" id="mixHeaderStats">
            @include('mixes.partials.header-stats')
        </div>

        <div class="headerStatsWrapperAction">
            <button onclick="openMixCreateSheet()" class="headerStatsButtonPrimary">
                <span>Создать микс</span>
            </button>

            @role('admin')
            <button onclick="openMixDeactivationLogSheet()" class="headerStatsButtonSecondary">
                <span>Журнал деактиваций</span>
            </button>
            @endrole
        </div>
    </div>

    <div class="topNav">
        <div class="search">
            <img src="/assets/img/search-icon.svg" alt="">
            <input type="text" id="mixSearch" placeholder="Название, вкус, крепость...">
        </div>

        <div class="filters">
            <div class="filtersRow">
                <button type="button" class="filtersBtn active" data-status="active">Активные</button>
                <button type="button" class="filtersBtn" data-status="inactive">Деактивные</button>
                <button type="button" class="filtersBtn" data-status="mine">Мои</button>
                <button type="button" class="filtersBtn" data-status="all">Все</button>
            </div>
        </div>
    </div>
</header>

<section class="productList" id="mixList">
    @include('mixes.partials.list-items')
</section>

{{-- sheet: карточка микса --}}
<div class="actionTab viewProductSameTab viewMix" id="viewMix">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Карточка микса</h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('viewMix')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div id="viewMixInner">
            <p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>
        </div>
    </div>
</div>

<div class="actionTab viewMixStoryTab" id="viewMixStoryTab">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>История изменений <br> <span id="mixHistoryTitle"></span></h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('viewMixStoryTab')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div class="viewTabaccoDeactiveListWrapper" id="viewMixStoryInner">
            <p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>
        </div>
    </div>
</div>

<div class="actionTab viewProductSameTab createMix" id="createMix">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Создать микс</h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('createMix')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="--createMixInner" id="createMixInner"></div>
    </div>
</div>

<div class="actionTab viewProductSameTab viewMixEditTab" id="viewMixEditTab">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Редактировать микс</h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('viewMixEditTab')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div id="viewMixEditInner"></div>
    </div>
</div>

@role('admin')
<div class="actionTab viewMixesDeactiveList" id="viewMixesDeactiveList">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Журнал деактиваций</h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('viewMixesDeactiveList')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div class="viewTabaccoDeactiveListWrapper" id="viewMixesDeactiveListInner">
            <p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>
        </div>
    </div>
</div>
@endrole

@endsection

@push('scripts')
<script src="/assets/js/mixes.js?v={{ filemtime(public_path('/assets/js/mixes.js')) }}"></script>
@endpush