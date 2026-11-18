@extends('layouts.app')

@push('scripts')
<script src="/assets/js/stats.js?v={{ filemtime(public_path('/assets/js/stats.js')) }}"></script>
@endpush

@section('content')
<header class="defaultFixed statsHeader">
    <div class="headerTop">
        <h1>Статистика</h1>
        @include('partials.shift-toggle')
        <a href="{{ route('analytics.index') }}" class="headerUser"><img src="/assets/img/brands/mix.svg" alt=""></a>
    </div>
    <div class="topNav">
        <div class="filters">
            <div class="filtersRow" id="statTabs">
                @foreach($tabs as $t)
                    <a href="#" class="filtersBtn {{ $tab === $t ? 'active' : '' }}" data-tab="{{ $t }}">{{ $tabDefs[$t][0] }}</a>
                @endforeach
            </div>
            <div class="filtersRow" id="statPeriods">
                @foreach(['today' => 'Сегодня', 'week' => 'Неделя', 'month' => 'Месяц', 'year' => 'Год', 'all' => 'Всё', 'month_pick' => 'Выбрать месяц', 'custom' => 'Свои даты'] as $k => $l)
                    <a href="#" class="filtersBtn {{ $period->key === $k ? 'active' : '' }}" data-period="{{ $k }}">{{ $l }}</a>
                @endforeach
            </div>
            <div class="statDates" id="statDates" style="{{ in_array($period->key, ['custom', 'month_pick']) ? '' : 'display:none' }}">
                <input type="month" id="statMonth" value="{{ $period->from->format('Y-m') }}" style="{{ $period->key === 'month_pick' ? '' : 'display:none' }}">
                <div id="statRange" style="{{ $period->key === 'custom' ? '' : 'display:none' }}">
                    <input type="date" id="statFrom" value="{{ $period->from->toDateString() }}">
                    <input type="date" id="statTo" value="{{ $period->to->toDateString() }}">
                </div>
            </div>
        </div>
    </div>
</header>

<section class="analytics defaultFixed statsBody" id="stats-content" data-url="{{ url('/stats/tab') }}" data-tab="{{ $tab }}" data-period="{{ $period->key }}"></section>
@include('partials.guest-sheets')
@endsection
