@extends('layouts.app')

@section('main-class', 'guests')

@section('content')

<header>
    <div class="headerTop">
        <h1>Гости</h1>

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
    <div class="headerStats" id="guestHeaderStats">
        @include('guests.partials.header-stats')
    </div>
</header>

<form class="topNav" onsubmit="return false;">
    <div class="topNavRow0vd32">
        <div class="search">
            <img src="/assets/img/search-icon.svg" alt="">
            <input type="text" id="guestSearch" placeholder="Имя, телефон, username...">
        </div>

        <div class="headerStatsWrapperAction">
            <button type="button" onclick="openGuestCreateSheet()" class="headerStatsButtonPrimary">
                <span>Создать</span>
            </button>
        </div>
    </div>

    <div class="filters">
        <div class="filtersRow" style="display:grid; grid-template-columns:repeat(2, 1fr);">
            @include('partials.filter-select', ['id' => 'guestStatusSelect', 'selected' => 'all', 'options' => ['all' => 'Все гости', 'vip' => 'VIP', 'permanent' => 'Постоянный', 'regular' => 'Регулярный', 'new' => 'Новый']])
            @include('partials.filter-select', ['id' => 'guestSortSelect', 'selected' => 'new', 'options' => ['new' => 'Новые гости', 'old' => 'Старые гости', 'count_desc' => 'Больше заказов', 'count_asc' => 'Меньше заказов', 'name' => 'По имени']])
        </div>
    </div>
</form>

<section class="guestsSection" id="guestList">
    @include('guests.partials.list-items')
</section>

@include('partials.guest-sheets')

@endsection

@push('scripts')
<script src="/assets/js/guest-sheet.js?v={{ filemtime(public_path('/assets/js/guest-sheet.js')) }}"></script>
@endpush