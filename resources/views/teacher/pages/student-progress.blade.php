@php
    $completedLessons = (int) ($userProgress['completed_lessons'] ?? 0);
    $xpValue = (int) ($userProgress['xp'] ?? 0);
    $achievementsCount = count($studentAchievements ?? []);
    $progressPercent = min(max((int) round($completedLessons * 10), 0), 100);
@endphp

<section class="student-learning-layout student-progress-layout">
    <aside class="student-learning-left ui-card">
        <div class="student-learning-left-cover" aria-hidden="true"></div>
        <nav class="student-learning-left-nav" aria-label="Разделы обучения">
            <a href="{{ route('student.courses') }}">Мое обучение</a>
            <a href="{{ route('student.catalog') }}">Каталог курсов</a>
            <a class="is-active" href="{{ route('student.progress') }}">Прогресс</a>
            <a href="{{ route('student.profile') }}">Профиль</a>
        </nav>
    </aside>

    <div class="student-learning-main">
        <header class="student-learning-head">
            <h2>Прогресс и достижения</h2>
            <p>Ваш путь обучения: уроки, опыт и открытые награды.</p>
        </header>

        <section class="student-progress-kpi-grid">
            <article class="ui-card student-progress-kpi">
                <div class="student-progress-kpi-label">Пройдено уроков</div>
                <div class="student-progress-kpi-value">{{ $completedLessons }}</div>
                <div class="student-progress-kpi-hint">Суммарно по активным курсам</div>
            </article>
            <article class="ui-card student-progress-kpi">
                <div class="student-progress-kpi-label">Опыт</div>
                <div class="student-progress-kpi-value">{{ $xpValue }} XP</div>
                <div class="student-progress-kpi-hint">Начисляется за завершение уроков</div>
            </article>
            <article class="ui-card student-progress-kpi">
                <div class="student-progress-kpi-label">Достижения</div>
                <div class="student-progress-kpi-value">{{ $achievementsCount }}</div>
                <div class="student-progress-kpi-hint">Награды за активность и прогресс</div>
            </article>
        </section>

        <section class="ui-card student-progress-hero">
            <div class="student-progress-hero-head">
                <h3>Общий прогресс</h3>
                <span>{{ $progressPercent }}%</span>
            </div>
            <div class="student-progress-line" role="progressbar" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100">
                <span style="width: {{ $progressPercent }}%"></span>
            </div>
            <p>Продолжайте в том же темпе: каждый урок приближает к завершению курса.</p>
        </section>

        <section class="ui-card student-achievements-list">
            <h3>Мои достижения</h3>
            <div class="student-achievements-grid">
                @forelse(($studentAchievements ?? []) as $achievement)
                    <article class="student-achievement-item">
                        <div class="student-achievement-top">
                            <span class="student-achievement-type">
                                @if(($achievement['is_system'] ?? true) === false)
                                    Курсовая ачивка
                                @else
                                    Системная ачивка
                                @endif
                            </span>
                            <span class="student-achievement-xp">+{{ (int) ($achievement['xp_reward'] ?? 0) }} XP</span>
                        </div>
                        <h4>{{ $achievement['title'] }}</h4>
                        <p>{{ $achievement['description'] !== '' ? $achievement['description'] : 'Описание не задано.' }}</p>
                        <div class="student-achievement-meta">
                            @if(($achievement['is_system'] ?? true) === false && !empty($achievement['course_title']))
                                <span>Курс: {{ $achievement['course_title'] }}</span>
                            @endif
                            <span>{{ $achievement['achieved_at'] ?? 'только что' }}</span>
                        </div>
                    </article>
                @empty
                    <div class="ui-alert warning">Пока нет достижений. Пройдите первый урок, чтобы открыть первые бейджи.</div>
                @endforelse
            </div>
        </section>
    </div>
</section>
