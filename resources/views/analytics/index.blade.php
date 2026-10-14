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
    var welcomeP = document.querySelector('.headerTopWelcome p');
    var section = document.querySelector('.analytics.defaultFixed');
    if (!welcomeP || !section) return;

    var lineHeight = parseFloat(getComputedStyle(welcomeP).lineHeight);
    if (!lineHeight || isNaN(lineHeight)) return;

    var lines = Math.round(welcomeP.getBoundingClientRect().height / lineHeight);

    section.classList.remove('--h2', '--h3');
    if (lines === 2) {
        section.classList.add('--h2');
    } else if (lines >= 3) {
        section.classList.add('--h3');
    }
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

    $roleLabel = auth()->user()->hasRole('admin') ? 'администратор' : 'мастер';

    $surnameInitial = auth()->user()->surname
        ? mb_strtoupper(mb_substr(auth()->user()->surname, 0, 1, 'UTF-8'), 'UTF-8') . '.'
        : '';
    $displayName = trim(auth()->user()->name . ' ' . $surnameInitial);
@endphp

<header class="defaultFixed">
    <div class="headerTop">
        <h1>Аналитика</h1>

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
    @include('analytics.partials.content')
</section>

@include('partials.guest-sheets')

@endsection