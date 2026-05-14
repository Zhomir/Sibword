@php
    $teacherName = (string) (auth()->user()->name ?? 'Преподаватель');
    $teacherEmail = (string) (auth()->user()->email ?? '—');
    $initial = mb_strtoupper(mb_substr($teacherName, 0, 2));

    $coursesCount = count($curriculum['courses'] ?? []);
    $lessonsCount = collect($lessons ?? [])->count();
    $achievementsCount = count($teacherCourseAchievements ?? []);
@endphp

<section class="teacher-profile-stepik">
    <aside class="teacher-profile-left">
        <div class="teacher-stepik-avatar">{{ $initial }}</div>

        <div class="teacher-stepik-side-stats">
            <div><strong>{{ $coursesCount }}</strong> курсов</div>
            <div><strong>{{ $lessonsCount }}</strong> уроков</div>
        </div>

        <div class="teacher-stepik-meta">
            <div><span>Email:</span> {{ $teacherEmail }}</div>
            <div><span>Роль:</span> Преподаватель</div>
        </div>

        <div class="teacher-stepik-quick-actions">
            <a href="{{ route('teacher.panel.page') }}" class="ui-btn ui-btn-primary">Открыть редактор</a>
            <a href="{{ route('teacher.analytics.page') }}" class="ui-btn ui-btn-secondary">Аналитика</a>
        </div>
    </aside>

    <div class="teacher-profile-right">
        <header class="teacher-stepik-header">
            <h2>{{ $teacherName }}</h2>
            <p>Панель преподавателя: управление курсами, уроками и результатами учеников.</p>
        </header>

        <section class="teacher-stepik-stats">
            <article class="teacher-stepik-stat-card">
                <div class="teacher-stepik-stat-label">Курсы</div>
                <div class="teacher-stepik-stat-value">{{ $coursesCount }}</div>
            </article>
            <article class="teacher-stepik-stat-card">
                <div class="teacher-stepik-stat-label">Уроки</div>
                <div class="teacher-stepik-stat-value">{{ $lessonsCount }}</div>
            </article>
            <article class="teacher-stepik-stat-card">
                <div class="teacher-stepik-stat-label">Курсовые ачивки</div>
                <div class="teacher-stepik-stat-value">{{ $achievementsCount }}</div>
            </article>
        </section>

        <section class="teacher-courses-grid">
            <article class="teacher-course-card">
                <div class="teacher-course-card-body">
                    <div class="teacher-course-card-kicker">Раздел</div>
                    <h3 class="teacher-course-card-title">Аналитика</h3>
                    <p class="teacher-course-card-copy">Смотрите статистику попыток, ошибки по шагам и зоны риска по урокам.</p>
                </div>
                <div class="teacher-course-card-actions">
                    <a href="{{ route('teacher.analytics.page') }}" class="btn btn-primary btn-sm">Перейти</a>
                </div>
            </article>

            <article class="teacher-course-card">
                <div class="teacher-course-card-body">
                    <div class="teacher-course-card-kicker">Раздел</div>
                    <h3 class="teacher-course-card-title">Курсы</h3>
                    <p class="teacher-course-card-copy">Список ваших курсов, публикация и быстрый переход к редактированию.</p>
                </div>
                <div class="teacher-course-card-actions">
                    <a href="{{ route('teacher.courses.page') }}" class="btn btn-primary btn-sm">Перейти</a>
                </div>
            </article>

            <article class="teacher-course-card">
                <div class="teacher-course-card-body">
                    <div class="teacher-course-card-kicker">Раздел</div>
                    <h3 class="teacher-course-card-title">Редактор</h3>
                    <p class="teacher-course-card-copy">Создание курсов, модулей, уроков и наполнение практическими шагами.</p>
                </div>
                <div class="teacher-course-card-actions">
                    <a href="{{ route('teacher.panel.page') }}" class="btn btn-primary btn-sm">Перейти</a>
                </div>
            </article>

            <article class="teacher-course-card">
                <div class="teacher-course-card-body">
                    <div class="teacher-course-card-kicker">Раздел</div>
                    <h3 class="teacher-course-card-title">Ачивки</h3>
                    <p class="teacher-course-card-copy">Управление курсовыми достижениями и мотивацией учеников.</p>
                </div>
                <div class="teacher-course-card-actions">
                    <a href="{{ route('teacher.achievements.page') }}" class="btn btn-primary btn-sm">Перейти</a>
                </div>
            </article>
        </section>
    </div>
</section>
