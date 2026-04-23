@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/home-rich.css') }}">

<section class="home-hero">
    <div class="home-hero-grid">
        <div class="home-hero-main" data-aos="fade-up">
            <span class="home-badge">MVP образовательной платформы</span>
            <h1>Изучайте <span>сибирские языки</span> через современный цифровой маршрут</h1>
            <p>
                Главная построена как визуальное повествование: от мотивации и структуры до
                практики, прогресса и действия. СЛОВОСИБИРИ объединяет теорию, интерактив и
                словарь в единой экосистеме обучения.
            </p>
            <div class="home-hero-actions">
                <a href="{{ route('teacher.indes', ['page' => 'student_dashboard']) }}" class="main-btn">Начать обучение</a>
                <a href="{{ route('teacher.indes', ['page' => 'teacher_panel']) }}" class="secondary-btn">Открыть редактор</a>
            </div>
        </div>

        <aside class="home-hero-panel" data-aos="fade-left">
            <h2>Логика платформы</h2>
            <div class="home-mini-list">
                <div class="home-mini-item">
                    <strong>Курс → Модуль → Урок</strong>
                    <span>Прозрачная структура и этапность</span>
                </div>
                <div class="home-mini-item">
                    <strong>Теория + практика</strong>
                    <span>Мгновенная проверка и обратная связь</span>
                </div>
                <div class="home-mini-item">
                    <strong>Словарь + медиа</strong>
                    <span>Повторение лексики в контексте</span>
                </div>
            </div>
        </aside>
    </div>
</section>

<section class="home-trust" data-aos="fade-up">
    <div class="home-trust-item">3 роли</div>
    <div class="home-trust-item">6+ интерактивных механик</div>
    <div class="home-trust-item">Централизованный контент</div>
    <div class="home-trust-item">Mobile-first интерфейс</div>
</section>

<section class="home-section home-section--story">
    <div class="home-section-head">
        <h2>Повествование обучения</h2>
        <p>Страница ведет пользователя по истории: зачем учиться, как устроен процесс и что он получит на выходе.</p>
    </div>

    <div class="home-story-grid">
        <article class="home-story-card" data-aos="fade-right">
            <h3>Контекст</h3>
            <p>У учащегося мало времени и высокий риск потерять мотивацию в длинных курсах без ритма.</p>
            <ul class="home-story-list">
                <li>Длинные блоки теории сложно удерживать в фокусе</li>
                <li>Нужна практика сразу после объяснения</li>
                <li>Важно видеть прогресс на каждом шаге</li>
            </ul>
        </article>

        <article class="home-story-card" data-aos="fade-left">
            <h3>Решение</h3>
            <div class="home-story-steps">
                <div class="home-story-step"><span>01</span><p>Короткие смысловые уроки вместо перегруженных страниц</p></div>
                <div class="home-story-step"><span>02</span><p>Интерактивные задания сразу внутри урока</p></div>
                <div class="home-story-step"><span>03</span><p>Постоянный видимый прогресс и повторение ошибок</p></div>
            </div>
        </article>
    </div>
</section>

<section class="home-section">
    <div class="home-section-head">
        <h2>Как устроено обучение</h2>
        <p>Логика платформы построена по тезисам MVP: от структуры контента до контроля прогресса.</p>
    </div>

    <div class="home-flow">
        <article class="home-flow-card" data-aos="zoom-in-up">
            <div class="home-flow-index">01</div>
            <h3>Курс</h3>
            <p>Общая тема и образовательная цель.</p>
        </article>
        <article class="home-flow-card" data-aos="zoom-in-up" data-aos-delay="80">
            <div class="home-flow-index">02</div>
            <h3>Модуль</h3>
            <p>Логические блоки для постепенного погружения.</p>
        </article>
        <article class="home-flow-card" data-aos="zoom-in-up" data-aos-delay="140">
            <div class="home-flow-index">03</div>
            <h3>Урок</h3>
            <p>Теория, задания, разбор и повторение в одном цикле.</p>
        </article>
        <article class="home-flow-card" data-aos="zoom-in-up" data-aos-delay="200">
            <div class="home-flow-index">04</div>
            <h3>Прогресс</h3>
            <p>Фиксация попыток и персональный маршрут закрепления.</p>
        </article>
    </div>
</section>

<section class="home-section">
    <div class="home-section-head">
        <h2>Практика в одном уроке</h2>
        <p>Механики подобраны так, чтобы пользователь не выпадал из потока и сразу видел результат.</p>
    </div>

    <div class="home-grid" data-aos="fade-up">
        <article class="home-card"><h3>Множественный выбор</h3><p>Быстрая проверка понимания темы.</p></article>
        <article class="home-card"><h3>Заполнение пропусков</h3><p>Закрепление грамматики в живом контексте.</p></article>
        <article class="home-card"><h3>Сопоставление пар</h3><p>Тренировка слова и перевода.</p></article>
        <article class="home-card"><h3>Сборка фразы</h3><p>Отработка порядка слов и структуры.</p></article>
        <article class="home-card"><h3>Аудирование</h3><p>Восприятие речи на слух с выбором ответа.</p></article>
        <article class="home-card"><h3>Флеш-карточки</h3><p>Цикличное повторение и долговременная память.</p></article>
    </div>
</section>

<section class="home-section">
    <div class="home-section-head">
        <h2>Роли в системе</h2>
        <p>Каждая роль получает отдельный интерфейс, но вся работа строится в единой образовательной модели.</p>
    </div>

    <div class="home-grid home-grid--roles" data-aos="fade-up">
        <article class="home-card">
            <h3>Студент</h3>
            <p>Проходит уроки, видит прогресс, повторяет ошибки и закрепляет материал.</p>
        </article>
        <article class="home-card">
            <h3>Преподаватель</h3>
            <p>Создает курсы, модули и уроки, управляет словарем и медиа.</p>
        </article>
        <article class="home-card">
            <h3>Администратор</h3>
            <p>Контролирует роли пользователей и качество контента.</p>
        </article>
    </div>
</section>

<section class="home-cta" data-aos="fade-up">
    <h2>Готовы перейти от идеи к практике?</h2>
    <p>
        Главная теперь поддерживает сценарий вовлечения: пользователь понимает ценность,
        видит маршрут и получает понятный следующий шаг.
    </p>
    <div class="home-hero-actions">
        <a href="{{ route('teacher.indes', ['page' => 'student_dashboard']) }}" class="main-btn">Перейти к курсам</a>
        <a href="{{ route('open.day') }}" class="secondary-btn">Открыть мини-новеллу</a>
    </div>
</section>
@endsection
