@extends('layouts.app')

@section('main-class', 'guests')

@section('content')

<header>
    <div class="headerTop">
        <h1>Гости</h1>

        @role('admin')
        <a href="{{ route('settings.index') }}" class="headerUser">
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
            <input type="text" id="guestSearch" placeholder="Имя, username, email, номер телефона...">
        </div>

        <div class="headerStatsWrapperAction">
            <button type="button" onclick="openGuestCreateSheet()" class="headerStatsButtonPrimary">
                <span>Создать</span>
            </button>
        </div>
    </div>

    <div class="filters">
        <div class="filtersRow">
            <button type="button" class="filtersBtn active" data-status="">Все гости</button>
            <button type="button" class="filtersBtn" data-status="vip">VIP</button>
            <button type="button" class="filtersBtn" data-status="permanent">Постоянный</button>
            <button type="button" class="filtersBtn" data-status="regular">Регулярный</button>
            <button type="button" class="filtersBtn" data-status="new">Новый</button>
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