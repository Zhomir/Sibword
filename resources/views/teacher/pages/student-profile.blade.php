@php
    $userName = (string) (auth()->user()?->name ?? 'Студент');
    $userEmail = (string) (auth()->user()?->email ?? '—');
    $initial = mb_strtoupper(mb_substr($userName, 0, 2));

    $completedLessons = (int) ($studentCurrentCourseSummary['completed_lessons'] ?? 0);
    $totalLessons = (int) ($studentCurrentCourseSummary['total_lessons'] ?? 0);
    $remainingLessons = (int) ($studentCurrentCourseSummary['remaining_lessons'] ?? 0);
    $progressPercent = $totalLessons > 0 ? (int) round(($completedLessons / max(1, $totalLessons)) * 100) : 0;
    $courseTitle = (string) ($studentCurrentCourseSummary['course_title'] ?? 'Курс пока не выбран');
@endphp

<section class="student-profile-stepik">
    <aside class="student-profile-left">
        <div class="student-stepik-avatar">{{ $initial }}</div>

        <div class="student-stepik-side-stats">
            <div><strong>{{ $completedLessons }}</strong> решено</div>
            <div><strong>{{ $progressPercent }}%</strong> текущий прогресс</div>
        </div>

        <div class="student-stepik-meta">
            <div><span>Email:</span> {{ $userEmail }}</div>
            <div><span>Роль:</span> Студент</div>
        </div>

        <div class="student-stepik-quick-actions">
            <a href="{{ route('student.courses') }}" class="ui-btn ui-btn-secondary">Мое обучение</a>
            <a href="{{ route('student.catalog') }}" class="ui-btn ui-btn-secondary">Каталог</a>
        </div>

        <button type="button" class="ui-btn ui-btn-secondary student-stepik-edit-btn" id="profile-edit-toggle" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}">Редактировать профиль</button>
    </aside>

    <div class="student-profile-right">
        <header class="student-stepik-header">
            <h2>{{ $userName }}</h2>
            <p>Активность за последнее время</p>
        </header>

        <nav class="student-stepik-tabs" aria-label="Вкладки профиля">
            <button type="button" class="is-active">Обзор</button>
            <button type="button">Данные</button>
            <button type="button">Сертификаты</button>
        </nav>

        <section class="student-stepik-activity-card ui-card">
            <div class="student-activity-grid" aria-hidden="true">
                @for ($i = 0; $i < 84; $i++)
                    <span class="{{ $i < max(0, min(84, $progressPercent)) ? 'is-active' : '' }}"></span>
                @endfor
            </div>

            <div class="student-activity-summary">
                <div>
                    <strong>{{ $remainingLessons }}</strong>
                    <span>уроков осталось</span>
                </div>
                <div>
                    <strong>{{ $completedLessons }}</strong>
                    <span>уроков пройдено</span>
                </div>
                <div>
                    <strong>{{ $progressPercent }}%</strong>
                    <span>прогресс курса</span>
                </div>
            </div>
        </section>

        <section class="ui-card student-stepik-course">
            <h3>Текущий курс</h3>
            <p>{{ $courseTitle }}</p>
            <div class="student-progress-line" role="progressbar" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100">
                <span style="width: {{ $progressPercent }}%"></span>
            </div>
            <div class="student-stepik-course-foot">
                <span>Пройдено: {{ $completedLessons }} / {{ $totalLessons }}</span>
                <span>Осталось: {{ $remainingLessons }}</span>
            </div>
        </section>

        <section class="ui-card student-stepik-rhythm">
            <h3>Учебный ритм</h3>
            <p>Старайтесь проходить хотя бы 1 урок в день, чтобы сохранить темп.</p>
        </section>

        <form action="{{ route('student.profile.update') }}" method="POST" class="ui-card student-stepik-edit-form {{ $errors->any() ? '' : 'is-hidden' }}" id="student-profile-edit-form">
            @csrf
            <h3>Редактирование профиля</h3>
            <div class="ui-grid cols-2">
                <label>
                    <div class="student-field-label">Имя</div>
                    <input type="text" name="name" class="ui-input" maxlength="255" required value="{{ old('name', $userName) }}">
                </label>
                <label>
                    <div class="student-field-label">Email</div>
                    <input type="email" name="email" class="ui-input" maxlength="255" required value="{{ old('email', $userEmail) }}">
                </label>
            </div>

            @if ($errors->any())
                <div class="ui-alert danger ui-section">{{ $errors->first() }}</div>
            @endif

            <div class="student-profile-actions">
                <button type="submit" class="ui-btn ui-btn-primary">Сохранить изменения</button>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('profile-edit-toggle');
    const form = document.getElementById('student-profile-edit-form');
    if (!toggle || !form) return;

    toggle.addEventListener('click', function () {
        const hidden = form.classList.toggle('is-hidden');
        toggle.setAttribute('aria-expanded', hidden ? 'false' : 'true');
    });
});
</script>
