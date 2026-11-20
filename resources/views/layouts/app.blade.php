<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#FFF3EA">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Hookah CRM Test">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <link rel="apple-touch-icon" href="/assets/img/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/img/icons/icon-192.png">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }
    </script>

    <meta name="vapid-key" content="{{ config('webpush.public_key') }}">
    <script src="/assets/js/push.js?v={{ filemtime(public_path('/assets/js/push.js')) }}" defer></script>

    <link rel="stylesheet" href="/assets/css/style.css?v={{ filemtime(public_path('/assets/css/style.css')) }}">
    <link rel="stylesheet" href="/assets/css/theme-peach.css?v={{ filemtime(public_path('/assets/css/theme-peach.css')) }}">
    @stack('styles')
</head>
<body>

<main class="@yield('main-class')">
    @if(session('error'))
        <div class="flashToast --error" role="alert">{{ session('error') }}</div>
    @endif

    @yield('content')
</main>

<nav class="menu">
    <a href="{{ route('analytics.index') }}" class="menuItem {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5V21H3z"/></svg>

        <span>Главная</span>
    </a>

    <a href="{{ route('orders.index') }}" class="menuItem {{ request()->routeIs('orders.*') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4h6v3H9zM9 12h6M9 16h4"/></svg>

        <span>Заказы</span>
    </a>

    <button class="menuItemCreateOrder" onclick="openSheet('createOrder')"></button>

    <a href="{{ route('guests.index') }}" class="menuItem {{ request()->routeIs('guests.*') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.4c2.2.7 3.5 2.6 3.5 5.6"/></svg>

        <span>Гости</span>
    </a>

    <a href="{{ route('tobaccos.index') }}" id="menuCatalog" data-urls='@json(['tobaccos' => route('tobaccos.index'), 'mixes' => route('mixes.index'), 'labels' => route('labels.index')])' class="menuItem {{ request()->routeIs('tobaccos.*', 'mixes.*', 'labels.*') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/></svg>

        <span>Каталог</span>
    </a>
</nav>
<script>try{var m=document.getElementById('menuCatalog'),u=JSON.parse(m.dataset.urls),k=localStorage.getItem('catalogTab');if(u[k])m.href=u[k]}catch(e){}</script>

@include('partials.create-order-sheet')

<div class="actionTab viewOrder" id="viewOrder">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Заказ <span id="viewOrderIdLabel"></span></h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('viewOrder')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="actionTabInner" id="viewOrderInner"></div>
    </div>
</div>

<script src="/assets/js/hints.js?v={{ filemtime(public_path('/assets/js/hints.js')) }}" defer></script>
<script src="/assets/js/app.js?v={{ filemtime(public_path('/assets/js/app.js')) }}"></script>
<script src="/assets/js/order-sheet.js?v={{ filemtime(public_path('/assets/js/order-sheet.js')) }}"></script>
@stack('scripts')
@vite(['resources/js/app.js'])
</body>
</html>