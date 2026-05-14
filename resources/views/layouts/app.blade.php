<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Слово Сибири - образовательная платформа</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=5">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=5">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}?v=5">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=5">

    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout-app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-polish.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ui-kit.css') }}">
    <script src="{{ asset('js/theme-preload.js') }}"></script>
</head>
<body data-theme="light">
    <nav class="nav-container">
        <div class="nav-content">
            <div class="logo">
                <a href="/" class="logo-link">СЛОВО<span>СИБИРИ</span></a>
            </div>

            <div class="nav-links">
                <a href="/">Главная</a>
                <a href="{{ route('courses.index') }}">Каталог</a>

                @auth
                    @if (Auth::user()->isStudent())
                        <a href="{{ route('student.dashboard') }}">Курсы</a>
                    @endif

                    @if (Auth::user()->isTeacher())
                        <a href="{{ route('teacher.home') }}">Профиль преподавателя</a>
                        <a href="{{ route('teacher.courses.page') }}">Курсы преподавателя</a>
                        <a href="{{ route('teacher.panel.page') }}">Редактор</a>
                    @endif

                    @if (Auth::user()->isAdmin())
                        <a href="{{ route('admin.index') }}">Админ</a>
                    @endif

                    <a href="{{ route('dashboard') }}" class="user-link">ЛК ({{ Auth::user()->name }})</a>
                    <form action="{{ route('logout') }}" method="POST" class="inline-form">
                        @csrf
                        <button type="submit" class="btn-login-outline btn-login-inline">Выйти</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-login-outline">Войти</a>
                @endauth

                <button id="theme-toggle" type="button" class="theme-toggle" aria-pressed="false">
                    <span id="theme-toggle-label">Светлая</span>
                </button>
            </div>

            <div class="hamburger-menu">
                <span></span><span></span><span></span>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script src="{{ asset('js/main.js') }}"></script>

    @yield('scripts')
</body>
</html>

