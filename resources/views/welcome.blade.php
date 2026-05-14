@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/home-rich.css') }}?v={{ filemtime(public_path('css/home-rich.css')) }}">

<section class="landing">
    <div class="landing-hero">
        <div class="landing-hero-copy">
            <span class="landing-badge">Интерактивное обучение</span>
            <h1>Сайн байна!<br><span>Бурятский язык</span> в твоем ритме</h1>
            <p>Современная обучающая платформа с уроками, квестами и прогрессом для студентов, преподавателей и админов.</p>
            <div class="landing-actions">
                <a href="{{ route('register') }}" class="main-btn">Начать обучение</a>
                <a href="#why" class="secondary-btn">Узнать больше</a>
            </div>
        </div>
        <div class="landing-hero-art" aria-hidden="true"></div>
    </div>

    <section id="why" class="landing-section">
        <h2>Почему выбирают нас?</h2>
        <div class="landing-features">
            <article class="landing-feature">
                <div class="landing-feature-icon">🎯</div>
                <h3>Практичные уроки</h3>
                <p>Короткие материалы и задания, чтобы сразу применять язык в живых ситуациях.</p>
            </article>
            <article class="landing-feature">
                <div class="landing-feature-icon">🧩</div>
                <h3>Интерактивность</h3>
                <p>Мини-квесты, тренажеры и наглядный прогресс делают обучение регулярным и интересным.</p>
            </article>
            <article class="landing-feature">
                <div class="landing-feature-icon">📊</div>
                <h3>Аналитика</h3>
                <p>Преподаватели видят слабые места и точечно улучшают курс, а студенты понимают свой путь.</p>
            </article>
        </div>

        <div class="landing-stats">
            <div class="landing-stat"><strong>3 роли</strong><span>Студент, Преподаватель, Администратор</span></div>
            <div class="landing-stat"><strong>Адаптивный интерфейс</strong><span>Учитесь и управляйте платформой с любого устройства</span></div>
        </div>
    </section>

    <section class="landing-section">
        <h2>Интерфейс для каждой роли</h2>
        <div class="landing-roles">
            <article class="landing-role-card">
                <h3>Студент</h3>
                <p>Понятная траектория: урок, практика, прогресс и награды.</p>
            </article>
            <article class="landing-role-card">
                <h3>Преподаватель</h3>
                <p>Создание курсов, быстрые правки контента и контроль успеваемости.</p>
            </article>
            <article class="landing-role-card">
                <h3>Администратор</h3>
                <p>Управление пользователями, ролями, контентом и стабильностью платформы.</p>
            </article>
        </div>
    </section>

    <section class="landing-cta">
        <div class="landing-cta-gift">🎁</div>
        <div>
            <h3>Готовы начать?</h3>
            <p>Присоединяйтесь к платформе и изучайте бурятский язык легко, структурно и с реальным результатом.</p>
        </div>
        <div class="landing-actions">
            <a href="{{ route('dashboard') }}" class="main-btn">Перейти к курсам</a>
            <a href="{{ route('open.day') }}" class="secondary-btn">Открыть мини-новеллу</a>
        </div>
    </section>

    <section class="landing-section landing-how">
        <h2>Как это работает?</h2>
        <div class="landing-steps">
            <article><b>1</b><h4>Создайте аккаунт</h4></article>
            <article><b>2</b><h4>Выберите курс</h4></article>
            <article><b>3</b><h4>Учитесь и практикуйтесь</h4></article>
            <article><b>4</b><h4>Отслеживайте прогресс</h4></article>
        </div>
    </section>
</section>
@endsection
