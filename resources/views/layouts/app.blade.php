<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0F0E0C">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Hookah CRM">
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

    {{-- Стили мобильного приложения (там же описаны .menu, .menuItem и т.д.) --}}
    <link rel="stylesheet" href="/assets/css/style.css?v=1.009">

    <!-- <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; }
        .main { min-height: 100vh; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 24px; }
        .page-header p { color: #666; margin-top: 4px; }
        .card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-card .label { font-size: 13px; color: #666; }
        .stat-card .value { font-size: 32px; font-weight: bold; margin-top: 4px; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; text-decoration: none; }
        .btn-primary { background: #c9a84c; color: #fff; }
        .btn-secondary { background: #eee; color: #333; }
        .btn-danger { background: #c95a2a; color: #fff; }
        .btn-success { background: #4caf74; color: #fff; }
        table { width: 100%; border-collapse: collapse; }
        table th { text-align: left; padding: 10px 12px; background: #f8f8f8; font-size: 13px; color: #666; border-bottom: 2px solid #eee; }
        table td { padding: 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        table tr:hover td { background: #fafafa; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-pending     { background: #fff3cd; color: #856404; }
        .badge-in_progress { background: #cce5ff; color: #004085; }
        .badge-ready       { background: #d4edda; color: #155724; }
        .badge-served      { background: #d1ecf1; color: #0c5460; }
        .badge-service     { background: #e2d9f3; color: #4a235a; }
        .badge-redo        { background: #f8d7da; color: #721c24; }
        .badge-closed      { background: #e2e3e5; color: #383d41; }
        .badge-cancelled   { background: #f8d7da; color: #721c24; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; color: #555; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 8px 12px; border: 1px solid #ddd;
            border-radius: 6px; font-size: 14px; outline: none;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #c9a84c;
        }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-bar input { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
    </style> -->
    @stack('styles')
</head>
<body>

<main class="@yield('main-class')">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @yield('content')
</main>

{{-- Памятка / Настройки / Аналитика (как отдельный пункт) в это меню пока не выведены —
     добавим их отдельным шагом, когда решим, как и куда именно (профиль/ещё-меню и т.п.) --}}
<nav class="menu">
    <a href="{{ route('analytics.index') }}" class="menuItem {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M6.10566 9.77508C5.95664 9.61972 5.84064 9.43577 5.76466 9.23434C5.68869 9.03291 5.6543 8.81818 5.66359 8.6031C5.67288 8.38802 5.72565 8.17705 5.81871 7.98292C5.91178 7.7888 6.0432 7.61554 6.20506 7.4736C6.36692 7.33166 6.55586 7.22398 6.76048 7.15707C6.9651 7.09015 7.18115 7.06539 7.3956 7.08426C7.61005 7.10314 7.81846 7.16527 8.00824 7.2669C8.19802 7.36853 8.36525 7.50755 8.49982 7.67558C8.63505 7.50931 8.80242 7.37201 8.99193 7.27191C9.18144 7.17181 9.38919 7.11096 9.60276 7.093C9.81633 7.07504 10.0313 7.10034 10.2349 7.16739C10.4385 7.23444 10.6264 7.34186 10.7875 7.48322C10.9486 7.62458 11.0795 7.79698 11.1724 7.99011C11.2654 8.18324 11.3184 8.39313 11.3283 8.60722C11.3383 8.82131 11.3049 9.03521 11.2303 9.23612C11.1557 9.43703 11.0413 9.62082 10.894 9.7765L9.03391 11.7995C8.96755 11.8759 8.88556 11.9372 8.79348 11.9792C8.7014 12.0212 8.60138 12.0429 8.50018 12.0429C8.39898 12.0429 8.29895 12.0212 8.20688 11.9792C8.1148 11.9372 8.03281 11.8759 7.96645 11.7995L6.10566 9.77508Z" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M2.125 7.08302C2.12495 6.87694 2.16986 6.67334 2.2566 6.4864C2.34334 6.29947 2.46982 6.13371 2.62721 6.00069L7.58554 1.75069C7.84124 1.53458 8.16521 1.41602 8.5 1.41602C8.83479 1.41602 9.15876 1.53458 9.41446 1.75069L14.3728 6.00069C14.5302 6.13371 14.6567 6.29947 14.7434 6.4864C14.8301 6.67334 14.875 6.87694 14.875 7.08302V13.458C14.875 13.8337 14.7257 14.1941 14.4601 14.4598C14.1944 14.7254 13.8341 14.8747 13.4583 14.8747H3.54167C3.16594 14.8747 2.80561 14.7254 2.53993 14.4598C2.27426 14.1941 2.125 13.8337 2.125 13.458V7.08302Z" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>

        <span>ГЛАВНАЯ</span>
    </a>

    <a href="{{ route('orders.index') }}" class="menuItem {{ request()->routeIs('orders.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M10.6253 1.41602H6.37533C5.98412 1.41602 5.66699 1.73315 5.66699 2.12435V3.54102C5.66699 3.93222 5.98412 4.24935 6.37533 4.24935H10.6253C11.0165 4.24935 11.3337 3.93222 11.3337 3.54102V2.12435C11.3337 1.73315 11.0165 1.41602 10.6253 1.41602Z" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M11.333 2.83398H12.7497C13.1254 2.83398 13.4857 2.98324 13.7514 3.24892C14.0171 3.51459 14.1663 3.87493 14.1663 4.25065V14.1673C14.1663 14.543 14.0171 14.9034 13.7514 15.1691C13.4857 15.4347 13.1254 15.584 12.7497 15.584H4.24967C3.87395 15.584 3.51362 15.4347 3.24794 15.1691C2.98226 14.9034 2.83301 14.543 2.83301 14.1673V4.25065C2.83301 3.87493 2.98226 3.51459 3.24794 3.24892C3.51362 2.98324 3.87395 2.83398 4.24967 2.83398H5.66634" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M8.5 7.79102H11.3333" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M8.5 11.334H11.3333" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M5.66699 7.79102H5.67699" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M5.66699 11.334H5.67699" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>

        <span>ЗАКАЗЫ</span>
    </a>

    <a href="{{ route('tobaccos.index') }}" class="menuItem {{ request()->routeIs('tobaccos.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
        <g clip-path="url(#clip0_657_12665)">
        <path d="M9.08775 1.54377C8.90318 1.45958 8.70269 1.41602 8.49983 1.41602C8.29697 1.41602 8.09648 1.45958 7.91191 1.54377L1.8415 4.30627C1.7158 4.36169 1.60894 4.45247 1.53391 4.56754C1.45889 4.68262 1.41895 4.81702 1.41895 4.95439C1.41895 5.09176 1.45889 5.22617 1.53391 5.34125C1.60894 5.45632 1.7158 5.5471 1.8415 5.60252L7.919 8.3721C8.10356 8.45629 8.30406 8.49985 8.50691 8.49985C8.70977 8.49985 8.91027 8.45629 9.09483 8.3721L15.1723 5.6096C15.298 5.55418 15.4049 5.4634 15.4799 5.34833C15.5549 5.23325 15.5949 5.09885 15.5949 4.96148C15.5949 4.82411 15.5549 4.6897 15.4799 4.57462C15.4049 4.45955 15.298 4.36877 15.1723 4.31335L9.08775 1.54377Z" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M1.41699 8.5C1.41666 8.63548 1.45518 8.76822 1.528 8.88246C1.60082 8.99671 1.70488 9.08768 1.82783 9.14458L7.9195 11.9142C8.1031 11.9973 8.30232 12.0403 8.50387 12.0403C8.70542 12.0403 8.90465 11.9973 9.08825 11.9142L15.1657 9.15167C15.2911 9.09531 15.3974 9.00369 15.4716 8.88798C15.5458 8.77226 15.5848 8.63746 15.5837 8.5" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M1.41699 12.041C1.41666 12.1765 1.45518 12.3092 1.528 12.4235C1.60082 12.5377 1.70488 12.6287 1.82783 12.6856L7.9195 15.4552C8.1031 15.5383 8.30232 15.5813 8.50387 15.5813C8.70542 15.5813 8.90465 15.5383 9.08825 15.4552L15.1657 12.6927C15.2911 12.6363 15.3974 12.5447 15.4716 12.429C15.5458 12.3133 15.5848 12.1785 15.5837 12.041" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        </g>
        <defs>
        <clipPath id="clip0_657_12665">
        <rect width="17" height="17" fill="white"/>
        </clipPath>
        </defs>
        </svg>

        <span>ТАБАКИ</span>
    </a>

    <button class="menuItemCreateOrder" onclick="openSheet('createOrder')"></button>

    <a href="{{ route('mixes.index') }}" class="menuItem {{ request()->routeIs('mixes.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M9.91667 1.41602V5.66602C9.91657 5.90365 9.97625 6.13749 10.0902 6.34602L13.9931 13.486C14.1112 13.7018 14.171 13.9446 14.1668 14.1905C14.1625 14.4364 14.0943 14.677 13.9689 14.8885C13.8435 15.1001 13.6651 15.2753 13.4514 15.397C13.2377 15.5188 12.996 15.5827 12.75 15.5827H4.25C4.00405 15.5827 3.76233 15.5188 3.5486 15.397C3.33488 15.2753 3.15653 15.1001 3.0311 14.8885C2.90566 14.677 2.83747 14.4364 2.83322 14.1905C2.82897 13.9446 2.88882 13.7018 3.00688 13.486L6.90979 6.34602C7.02376 6.13749 7.08344 5.90365 7.08334 5.66602V1.41602" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M4.57129 10.625H12.4295" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M6.02051 1.41602H10.9788" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>

        <span>МИКСЫ</span>
    </a>

    <a href="{{ route('guests.index') }}" class="menuItem {{ request()->routeIs('guests.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M11.3337 14.875V13.4583C11.3337 12.7069 11.0352 11.9862 10.5038 11.4549C9.97244 10.9235 9.25177 10.625 8.50033 10.625H4.25033C3.49888 10.625 2.77821 10.9235 2.24686 11.4549C1.7155 11.9862 1.41699 12.7069 1.41699 13.4583V14.875" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M11.333 2.21484C11.9406 2.37236 12.4787 2.72716 12.8628 3.22355C13.2469 3.71995 13.4553 4.32985 13.4553 4.95751C13.4553 5.58517 13.2469 6.19507 12.8628 6.69147C12.4787 7.18786 11.9406 7.54266 11.333 7.70018" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M15.583 14.8747V13.458C15.5825 12.8303 15.3736 12.2204 14.989 11.7243C14.6044 11.2281 14.0658 10.8737 13.458 10.7168" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M6.37532 7.79167C7.94013 7.79167 9.20866 6.52314 9.20866 4.95833C9.20866 3.39353 7.94013 2.125 6.37532 2.125C4.81052 2.125 3.54199 3.39353 3.54199 4.95833C3.54199 6.52314 4.81052 7.79167 6.37532 7.79167Z" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>

        <span>ГОСТИ</span>
    </a>

    <a href="{{ route('labels.index') }}" class="menuItem {{ request()->routeIs('labels.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
        <g clip-path="url(#clip0_657_12660)">
        <path d="M8.91541 1.8311C8.6498 1.5654 8.28952 1.4161 7.91383 1.41602H2.83366C2.45794 1.41602 2.0976 1.56527 1.83192 1.83095C1.56625 2.09662 1.41699 2.45696 1.41699 2.83268V7.91285C1.41707 8.28854 1.56638 8.64882 1.83208 8.91443L7.99741 15.0798C8.31936 15.3997 8.75479 15.5792 9.20866 15.5792C9.66253 15.5792 10.098 15.3997 10.4199 15.0798L15.0807 10.4189C15.4007 10.097 15.5802 9.66155 15.5802 9.20768C15.5802 8.75382 15.4007 8.31838 15.0807 7.99643L8.91541 1.8311Z" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M5.31217 5.66732C5.50778 5.66732 5.66634 5.50875 5.66634 5.31315C5.66634 5.11755 5.50778 4.95898 5.31217 4.95898C5.11657 4.95898 4.95801 5.11755 4.95801 5.31315C4.95801 5.50875 5.11657 5.66732 5.31217 5.66732Z" fill="#332E27" stroke="#332E27" stroke-linecap="round" stroke-linejoin="round"/>
        </g>
        <defs>
        <clipPath id="clip0_657_12660">
        <rect width="17" height="17" fill="white"/>
        </clipPath>
        </defs>
        </svg>

        <span>ЯРЛЫКИ</span>
    </a>
</nav>

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

<script src="/assets/js/app.js?v={{ filemtime(public_path('/assets/js/app.js')) }}"></script>
<script src="/assets/js/order-sheet.js?v={{ filemtime(public_path('/assets/js/order-sheet.js')) }}"></script>
@stack('scripts')
@vite(['resources/js/app.js'])
</body>
</html>