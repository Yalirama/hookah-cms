@extends('layouts.app')

@push('styles')
<style>
    /* Отступ сверху под фиксированный header — зависит от того, сколько строк
       занимает .headerTopWelcome p (считает JS ниже и вешает класс --h2/--h3) */
    .analytics.defaultFixed {
        padding: calc(105vw / var(--resizer) * 100) 0 0 0;
    }
    .analytics.defaultFixed.--h2 {
        padding: calc(120vw / var(--resizer) * 100) 0 0 0;
    }
    .analytics.defaultFixed.--h3 {
        padding: calc(125vw / var(--resizer) * 100) 0 0 0;
    }
    /* Бары гостей растут снизу вверх; если в основном style.css уже задан
       align-items — эта строка ничего не сломает, просто подстрахует */
    .analyticsQuestsItemRow {
        align-items: flex-end;
    }
</style>
@endpush

@push('scripts')
<script src="/assets/js/guest-sheet.js?v={{ filemtime(public_path('/assets/js/guest-sheet.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var section = document.querySelector('.analytics.defaultFixed');
    var header = document.querySelector('header.defaultFixed');
    if (!section || !header) return;
    var fit = function () { section.style.paddingTop = header.offsetHeight + 'px'; };
    fit();
    window.addEventListener('resize', fit);
});

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.inactiveStatusFilter');
    if (!btn) return;
    e.preventDefault();

    const url = new URL(btn.href);
    const status = url.searchParams.get('inactive_status');
    const period = url.searchParams.get('period');

    fetch(`{{ route('analytics.inactive-guests') }}?period=${encodeURIComponent(period)}&inactive_status=${encodeURIComponent(status)}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('inactiveGuestsCard').outerHTML = html;
        })
        .catch(() => alert('Не удалось загрузить фильтр'));
});

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.periodFilterBtn');
    if (!btn) return;
    e.preventDefault();

    const period = btn.dataset.period;

    document.querySelectorAll('.periodFilterBtn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    fetch(`{{ route('analytics.content') }}?period=${encodeURIComponent(period)}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('analytics-content').innerHTML = html;
            history.replaceState(null, '', `?period=${period}`);
        })
        .catch(() => {});
});
</script>
@endpush

@section('content')

@php
    $now = now();
    $weekdays = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
    $months = [1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'];
    $weekdayLabel = $weekdays[$now->dayOfWeek];
    $monthLabel = $months[$now->month];

    $hour = $now->hour;
    if ($hour >= 6 && $hour < 12) {
        $greeting = 'Доброе утро';
    } elseif ($hour >= 12 && $hour < 18) {
        $greeting = 'Добрый день';
    } elseif ($hour >= 18 && $hour < 23) {
        $greeting = 'Добрый вечер';
    } else {
        $greeting = 'Доброй ночи';
    }

    $roleLabel = auth()->user()->hasRole('admin') ? 'админ' : 'мастер';

    $surnameInitial = auth()->user()->surname
        ? mb_strtoupper(mb_substr(auth()->user()->surname, 0, 1, 'UTF-8'), 'UTF-8') . '.'
        : '';
    $displayName = trim(auth()->user()->name . ' ' . $surnameInitial);
@endphp

<header class="defaultFixed">
    <div class="headerTop">
        <h1>Аналитика</h1>
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

    <div class="headerTopWelcome">
        <span>{{ $weekdayLabel }}, {{ $now->day }} {{ $monthLabel }}</span>
        <p>{{ $greeting }}, {{ $roleLabel }} {{ $displayName }}</p>
    </div>

    <form action="#" class="topNav">
        <div class="filters">
            <div class="filtersRow">
                @foreach(['today'=>'Сегодня','week'=>'Неделя','month'=>'Месяц','half'=>'Полгода','year'=>'Год','all'=>'Все'] as $key => $label)
                    <a href="?period={{ $key }}" data-period="{{ $key }}" class="filtersBtn periodFilterBtn {{ $period === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </form>
</header>

<section class="analytics defaultFixed" id="analytics-content">
@if($shift)
<div class="analyticsCard statCard shiftCard">
    <div class="hintRow"><p class="analyticsCardTitle">{{ $shift['label'] }}</p><button type="button" class="hintBtn" data-hint="Ваша текущая смена, а если она закрыта, последняя (не старше 12 часов). «Приготовил» — заказы, которые вы перевели в «Готов». «Среднее время» — от создания заказа до «Готов». «Переделок» — сколько из приготовленных вами заказов ушло на переделку. «Подробнее» открывает вашу статистику за любой период.">?</button></div>
    @if(empty($shift['idle']))
    <div class="statGrid statGrid3">
        <div class="statTile"><b>{{ $shift['prepared'] }}</b><span>приготовил</span></div>
        <div class="statTile"><b>{{ $shift['avgNew'] !== null ? \App\Support\Fmt::mins($shift['avgNew']) : '—' }}</b><span>среднее время</span></div>
        <div class="statTile"><b>{{ $shift['redo'] }}</b><span>переделок</span></div>
    </div>
    <p class="statLine">Обслужил: <b>{{ $shift['serviced'] }}</b> · Закрыл: <b>{{ $shift['closed'] }}</b> · На смене: <b>{{ $shift['hours'] }} ч</b></p>
    @else
    <p class="statNote">Смена не включена. Включите её вверху справа, и здесь появятся цифры смены.</p>
    @endif
    <a href="{{ route('my-stats') }}" class="statMoreBtn">Подробнее</a>
</div>
@endif
    {!! $contentHtml !!}
</section>

@include('partials.guest-sheets')

@endsection