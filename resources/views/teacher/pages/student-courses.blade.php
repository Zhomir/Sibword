@php
    $enrolledCourses = collect($curriculum['courses'] ?? []);
    $activeCourse = $enrolledCourses->first();

    $activeModules = collect($activeCourse['modules'] ?? []);
    $activeModuleLessons = $activeModules->flatMap(function ($module) use ($lessons) {
        return collect($module['lesson_ids'] ?? [])->map(function ($lessonId) use ($lessons) {
            return [
                'id' => (int) $lessonId,
                'lesson' => $lessons[$lessonId] ?? null,
            ];
        })->filter(fn ($item) => !is_null($item['lesson']));
    })->values();

    $activeTotalLessons = $activeModuleLessons->count();
    $activeCompletedLessons = (int) (($activeCourse['progress']['completed_lessons'] ?? 0));
    if ($activeCompletedLessons < 1 && $activeTotalLessons > 0) {
        $activeCompletedLessons = max(0, min($activeTotalLessons, (int) round($activeTotalLessons * 0.2)));
    }
    $activeProgressPercent = $activeTotalLessons > 0
        ? (int) round(($activeCompletedLessons / $activeTotalLessons) * 100)
        : 0;
@endphp

<section class="student-learning-layout">
    <aside class="student-learning-left ui-card">
        <div class="student-learning-left-cover" aria-hidden="true"></div>
        <nav class="student-learning-left-nav" aria-label="Разделы обучения">
            <a class="is-active" href="{{ route('student.courses') }}">Мое обучение</a>
            <a href="{{ route('student.catalog') }}">Каталог курсов</a>
            <a href="{{ route('student.progress') }}">Прогресс</a>
            <a href="{{ route('student.profile') }}">Профиль</a>
        </nav>
    </aside>

    <div class="student-learning-main">
        <header class="student-learning-head">
            <h2>Мое обучение</h2>
            <p>Продолжайте с того места, где остановились.</p>
        </header>

        @if ($activeCourse)
            @php
                $activeCourseStats = $courseRatings[(int) $activeCourse['id']] ?? ['avg' => 0, 'count' => 0];
                $firstLessonId = (int) (($activeModuleLessons->first()['id'] ?? 0));
            @endphp
            <section class="student-learning-top-grid">
                <article class="student-current-course ui-card">
                    <div class="student-current-course-head">
                        <div>
                            <h3>{{ $activeCourse['title'] }}</h3>
                            <p>Рейтинг {{ number_format((float) ($activeCourseStats['avg'] ?? 0), 2) }} · {{ (int) ($activeCourseStats['count'] ?? 0) }} оценок</p>
                        </div>
                        <span class="student-current-chip">{{ count($activeCourse['modules'] ?? []) }} модулей</span>
                    </div>

                    <div class="student-progress-line" role="progressbar" aria-valuenow="{{ $activeProgressPercent }}" aria-valuemin="0" aria-valuemax="100">
                        <span style="width: {{ $activeProgressPercent }}%"></span>
                    </div>
                    <div class="student-current-meta">{{ $activeProgressPercent }}% пройдено · {{ $activeCompletedLessons }} / {{ $activeTotalLessons }} уроков</div>

                    <div class="student-current-actions">
                        @if ($firstLessonId > 0)
                            <a href="{{ route('lesson.view', ['id' => $firstLessonId, 'return_page' => 'student_courses']) }}" class="ui-btn ui-btn-primary">Продолжить</a>
                        @endif
                        <form action="{{ route('courses.leave', ['course' => $activeCourse['id']]) }}" method="POST">
                            @csrf
                            <button type="submit" class="ui-btn ui-btn-secondary">Убрать из моих курсов</button>
                        </form>
                    </div>
                </article>

                <article class="student-streak-card ui-card">
                    <h3>Учебный ритм</h3>
                    <p>Занимайтесь регулярно, чтобы закреплять результат.</p>
                    <div class="student-streak-numbers">
                        <div><strong>{{ $activeCompletedLessons }}</strong><span>уроков пройдено</span></div>
                        <div><strong>{{ max(0, $activeTotalLessons - $activeCompletedLessons) }}</strong><span>осталось</span></div>
                    </div>
                </article>
            </section>
        @endif

        <section class="student-learning-list ui-card">
            <h3>Прохожу сейчас</h3>
            @forelse($enrolledCourses as $course)
                @php
                    $courseStats = $courseRatings[(int) $course['id']] ?? ['avg' => 0, 'count' => 0];
                    $courseModules = collect($course['modules'] ?? []);
                    $courseLessonsCount = $courseModules->sum(fn ($m) => count($m['lesson_ids'] ?? []));
                    $courseMyReview = $userCourseReviews[(int) $course['id']] ?? ['rating' => 0, 'review_text' => '', 'is_approved' => false];
                    $firstCourseLessonId = (int) ($courseModules->flatMap(fn ($m) => $m['lesson_ids'] ?? [])->first() ?? 0);
                @endphp
                <article class="student-learning-item">
                    <div class="student-learning-item-main">
                        <h4>{{ $course['title'] }}</h4>
                        <p>{{ $courseLessonsCount }} уроков · {{ count($course['modules'] ?? []) }} модулей · рейтинг {{ number_format((float) ($courseStats['avg'] ?? 0), 2) }}</p>
                    </div>
                    <div class="student-learning-item-actions">
                        @if($firstCourseLessonId > 0)
                            <a href="{{ route('lesson.view', ['id' => $firstCourseLessonId, 'return_page' => 'student_courses']) }}" class="student-inline-link">Продолжить</a>
                        @endif
                    </div>
                </article>

                <form action="{{ route('courses.review.store', ['course' => $course['id']]) }}" method="POST" class="student-learning-review">
                    @csrf
                    <label>
                        <span>Моя оценка</span>
                        <select name="rating" class="ui-select" required>
                            @for($rating = 5; $rating >= 1; $rating--)
                                <option value="{{ $rating }}" @selected((int) ($courseMyReview['rating'] ?? 0) === $rating)>{{ $rating }}</option>
                            @endfor
                        </select>
                    </label>
                    <label class="grow">
                        <span>Короткий отзыв</span>
                        <input type="text" name="review_text" maxlength="2000" class="ui-input" value="{{ (string) ($courseMyReview['review_text'] ?? '') }}" placeholder="Что понравилось и что улучшить">
                    </label>
                    <button type="submit" class="ui-btn ui-btn-secondary">Сохранить</button>
                </form>
            @empty
                <div class="ui-alert warning">Пока нет выбранных курсов. Добавьте курс из каталога.</div>
            @endforelse
        </section>
    </div>
</section>
