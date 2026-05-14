            <section class="student-profile-hero teacher-card">
                <div>
                    <div class="student-profile-kicker">Профиль студента</div>
                    <h2 class="teacher-section-title student-profile-title">Личный кабинет</h2>
                    <p class="teacher-muted student-profile-copy">Отслеживайте прогресс по курсам, достижения и точки роста в одном экране.</p>
                </div>
                <a href="{{ route('student.catalog') }}" class="btn btn-primary btn-sm">Перейти в каталог</a>
            </section>

            <section class="student-kpi-grid">
                <article class="student-kpi-card teacher-card">
                    <div class="student-kpi-label">Пройдено уроков</div>
                    <div class="student-kpi-value">{{ (int) ($userProgress['completed_lessons'] ?? 0) }}</div>
                    <div class="student-kpi-hint">Суммарно по активным курсам</div>
                </article>
                <article class="student-kpi-card teacher-card">
                    <div class="student-kpi-label">Опыт</div>
                    <div class="student-kpi-value">{{ (int) ($userProgress['xp'] ?? 0) }} XP</div>
                    <div class="student-kpi-hint">Начисляется за завершение уроков</div>
                </article>
                <article class="student-kpi-card teacher-card">
                    <div class="student-kpi-label">Получено достижений</div>
                    <div class="student-kpi-value">{{ count($studentAchievements ?? []) }}</div>
                    <div class="student-kpi-hint">Награды за активность и прогресс</div>
                </article>
            </section>

            <div class="teacher-card student-progress-card">
                <div class="student-progress-head">
                    <h3 class="teacher-section-title">Общий прогресс</h3>
                    <span class="student-progress-badge">{{ min((int) (($userProgress['completed_lessons'] ?? 0) * 10), 100) }}%</span>
                </div>
                <div class="teacher-progress student-progress-bar">
                    <div class="teacher-progress-fill js-progress-fill" data-progress="{{ min((int) (($userProgress['completed_lessons'] ?? 0) * 10), 100) }}"></div>
                </div>
            </div>

            <div class="teacher-card">
                <h3 class="teacher-section-title">Текущий курс</h3>
                @if(!empty($studentCurrentCourseSummary))
                    @php
                        $currentCourse = $studentCurrentCourseSummary;
                    @endphp
                    <div class="student-goals-grid">
                        <div class="student-goal-card teacher-segment">
                            <div class="teacher-segment-title">Проходимый курс</div>
                            <div class="teacher-meta-text">{{ $currentCourse['course_title'] ?? 'Курс' }}</div>
                        </div>
                        <div class="student-goal-card teacher-segment">
                            <div class="teacher-segment-title">Пройдено уроков</div>
                            <div class="teacher-meta-text">{{ (int) ($currentCourse['completed_lessons'] ?? 0) }} / {{ (int) ($currentCourse['total_lessons'] ?? 0) }}</div>
                        </div>
                        <div class="student-goal-card teacher-segment">
                            <div class="teacher-segment-title">Осталось уроков</div>
                            <div class="teacher-meta-text">{{ (int) ($currentCourse['remaining_lessons'] ?? 0) }}</div>
                        </div>
                    </div>
                @else
                    <div class="teacher-muted">Пока нет активного курса. Выберите курс в каталоге, чтобы видеть прогресс.</div>
                @endif
            </div>

            <div class="teacher-card">
                <h3 class="teacher-section-title">Мои достижения</h3>
                <div class="teacher-courses-grid">
                    @forelse(($studentAchievements ?? []) as $achievement)
                        <article class="teacher-course-card student-achievement-card">
                            <div class="teacher-course-card-body">
                                <div class="teacher-course-card-kicker">
                                    @if(($achievement['is_system'] ?? true) === false)
                                        Курсовая ачивка
                                    @else
                                        Системная ачивка
                                    @endif
                                </div>
                                <h4 class="course-module-title">{{ $achievement['title'] }}</h4>
                                <p class="teacher-course-card-copy">{{ $achievement['description'] !== '' ? $achievement['description'] : 'Описание не задано.' }}</p>
                                <div class="teacher-course-card-meta">
                                    @if(($achievement['is_system'] ?? true) === false && !empty($achievement['course_title']))
                                        <span>Курс: {{ $achievement['course_title'] }}</span>
                                    @endif
                                    <span>+{{ (int) ($achievement['xp_reward'] ?? 0) }} XP</span>
                                    <span>{{ $achievement['achieved_at'] ?? 'только что' }}</span>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="teacher-muted">Пока нет достижений. Пройдите первый урок и оставьте комментарий, чтобы открыть первые бейджи.</div>
                    @endforelse
                </div>
            </div>

            <div class="teacher-card">
                <h3 class="teacher-section-title">Мои выбранные курсы</h3>
                <div class="teacher-course-outline">
                    @forelse(($curriculum['courses'] ?? []) as $courseIndex => $course)
                        @php
                            $courseStats = $courseRatings[(int) $course['id']] ?? ['avg' => 0, 'count' => 0];
                            $myReview = $userCourseReviews[(int) $course['id']] ?? ['rating' => 0, 'review_text' => '', 'is_approved' => false];
                        @endphp
                        <section class="course-module-card">
                            <div class="course-module-header">
                                <div>
                                    <div class="course-module-kicker">Курс {{ $courseIndex + 1 }}</div>
                                    <h4 class="course-module-title">{{ $course['title'] }}</h4>
                                    <div class="teacher-course-card-meta">
                                        <span>Рейтинг: {{ number_format((float) ($courseStats['avg'] ?? 0), 2) }}</span>
                                        <span>Оценок: {{ (int) ($courseStats['count'] ?? 0) }}</span>
                                    </div>
                                </div>
                                <div class="course-module-count">{{ count($course['modules'] ?? []) }} модулей</div>
                                <form action="{{ route('courses.leave', ['course' => $course['id']]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline btn-sm">Убрать из моих курсов</button>
                                </form>
                            </div>

                            <div class="course-lesson-list">
                                <form action="{{ route('courses.review.store', ['course' => $course['id']]) }}" method="POST" class="teacher-field teacher-segment">
                                    @csrf
                                    <div class="teacher-grid" style="grid-template-columns: 160px minmax(0, 1fr); gap: 10px;">
                                        <div>
                                            <label class="teacher-label">Моя оценка</label>
                                            <select name="rating" class="teacher-select" required>
                                                @for($rating = 5; $rating >= 1; $rating--)
                                                    <option value="{{ $rating }}" @selected((int) ($myReview['rating'] ?? 0) === $rating)>{{ $rating }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div>
                                            <label class="teacher-label">Мой отзыв</label>
                                            <textarea name="review_text" rows="2" maxlength="2000" class="teacher-textarea" placeholder="Что понравилось, что улучшить...">{{ $myReview['review_text'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                    <div class="teacher-actions">
                                        <button type="submit" class="btn btn-primary btn-sm">Сохранить отзыв</button>
                                    </div>
                                </form>

                                @forelse(($course['modules'] ?? []) as $moduleIndex => $module)
                                    @php
                                        $moduleLessons = collect($module['lesson_ids'] ?? [])
                                            ->map(fn ($lessonId) => ['id' => $lessonId, 'lesson' => $lessons[$lessonId] ?? null])
                                            ->filter(fn ($item) => !is_null($item['lesson']))
                                            ->values();
                                    @endphp
                                    <section class="course-module-card course-module-card--nested">
                                        <div class="course-module-header">
                                            <div>
                                                <div class="course-module-kicker">Модуль {{ $moduleIndex + 1 }}</div>
                                                <h4 class="course-module-title">{{ $module['title'] }}</h4>
                                            </div>
                                            <div class="course-module-count">{{ $moduleLessons->count() }} урок{{ $moduleLessons->count() === 1 ? '' : ($moduleLessons->count() < 5 ? 'а' : 'ов') }}</div>
                                        </div>

                                        <div class="course-lesson-list">
                                            @forelse($moduleLessons as $lessonItem)
                                                <div class="lesson-card lesson-card--outline">
                                                    <div>
                                                        <h4 class="lesson-card-title">{{ $lessonItem['lesson']['title'] }}</h4>
                                                        <small class="lesson-card-meta">Шагов: {{ $lessonItem['lesson']['steps_count'] ?? count($lessonItem['lesson']['steps'] ?? []) }}</small>
                                                    </div>
                                                    <a href="{{ route('lesson.view', ['id' => $lessonItem['id']]) }}" class="btn btn-primary">Открыть урок</a>
                                                </div>
                                            @empty
                                                <div class="teacher-muted">В этом модуле пока нет уроков.</div>
                                            @endforelse
                                        </div>
                                    </section>
                                @empty
                                    <div class="teacher-muted">В этом курсе пока нет модулей.</div>
                                @endforelse
                            </div>
                        </section>
                    @empty
                        <div class="teacher-muted">Пока нет выбранных курсов. Добавьте курс из списка ниже.</div>
                    @endforelse
                </div>
            </div>
