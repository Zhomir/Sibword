<section class="teacher-card">
    <h3 class="teacher-section-title">Создать ачивку за курс</h3>
    <form id="teacher-achievement-form" class="teacher-field">
        @csrf
        <div class="teacher-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
            <div>
                <label class="teacher-label" for="achievement_course_id">Курс</label>
                <select id="achievement_course_id" name="course_id" class="teacher-select" required>
                    <option value="">Выберите курс</option>
                    @foreach(($curriculum['courses'] ?? []) as $course)
                        <option value="{{ $course['id'] }}">{{ $course['title'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="teacher-label" for="achievement_title">Название</label>
                <input id="achievement_title" name="title" type="text" class="teacher-input" maxlength="120" required>
            </div>
            <div>
                <label class="teacher-label" for="achievement_xp_reward">XP в карточке</label>
                <input id="achievement_xp_reward" name="xp_reward" type="number" class="teacher-input" min="0" max="1000" value="50">
            </div>
        </div>
        <div class="teacher-field">
            <label class="teacher-label" for="achievement_description">Описание</label>
            <textarea id="achievement_description" name="description" rows="2" class="teacher-textarea" maxlength="500" placeholder="Например: завершите курс полностью"></textarea>
        </div>
        <div class="teacher-actions">
            <button type="submit" class="btn btn-success btn-sm">Создать ачивку</button>
        </div>
    </form>
</section>

<section class="teacher-card">
    <h3 class="teacher-section-title">Мои курсовые ачивки</h3>
    <div id="teacher-achievements-list" class="teacher-courses-grid">
        @forelse(($teacherCourseAchievements ?? []) as $achievement)
            <article class="teacher-course-card student-achievement-card">
                <div class="teacher-course-card-body">
                    <div class="teacher-course-card-kicker">Курс: {{ $achievement['course_title'] }}</div>
                    <h4 class="course-module-title">{{ $achievement['title'] }}</h4>
                    <p class="teacher-course-card-copy">{{ $achievement['description'] !== '' ? $achievement['description'] : 'Описание не задано.' }}</p>
                    <div class="teacher-course-card-meta"><span>+{{ (int) ($achievement['xp_reward'] ?? 0) }} XP</span></div>
                </div>
                <div class="teacher-course-card-actions">
                    <button type="button" class="btn btn-danger btn-sm js-delete-course-achievement" data-achievement-id="{{ (int) $achievement['id'] }}">Удалить</button>
                </div>
            </article>
        @empty
            <div class="teacher-muted">Пока нет курсовых ачивок. Создайте первую.</div>
        @endforelse
    </div>
</section>

