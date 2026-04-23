            <h2 class="teacher-section-title">Личный кабинет студента</h2>
            <div class="teacher-card">
                <h3 class="teacher-section-title">Твой прогресс</h3>
                <p>Выполнено уроков: <strong>{{ $userProgress['completed_lessons'] }}</strong></p>
                <p>Очки опыта (XP): <strong>{{ $userProgress['xp'] }}</strong></p>
                <div class="teacher-progress">
                    <div class="teacher-progress-fill js-progress-fill" data-progress="{{ min($userProgress['completed_lessons'] * 10, 100) }}"></div>
                </div>
            </div>

            <div class="teacher-card">
                <h3 class="teacher-section-title">Мои достижения</h3>
                <div class="teacher-course-card-meta">
                    <span>Получено: {{ count($studentAchievements ?? []) }}</span>
                </div>
                @php
                    $goalPool = collect($studentAchievementProgress ?? [])
                        ->map(function ($goal) {
                            $current = (int) ($goal['current'] ?? 0);
                            $target = max(1, (int) ($goal['target'] ?? 1));
                            $remain = max(0, $target - $current);
                            $goal['remain'] = $remain;
                            $goal['target'] = $target;
                            $goal['current'] = $current;
                            return $goal;
                        });
                    $nextGoals = $goalPool
                        ->filter(fn ($goal) => (int) ($goal['remain'] ?? 0) > 0)
                        ->sortBy('remain')
                        ->take(3);
                    if ($nextGoals->isEmpty()) {
                        $nextGoals = $goalPool->sortByDesc('target')->take(3);
                    }
                @endphp
                <div class="teacher-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; margin: 10px 0 16px;">
                    @foreach($nextGoals as $goal)
                        @php
                            $goalCurrent = (int) ($goal['current'] ?? 0);
                            $goalTarget = max(1, (int) ($goal['target'] ?? 1));
                            $goalPercent = (int) min(100, round(($goalCurrent / $goalTarget) * 100));
                            $goalRemain = max(0, $goalTarget - $goalCurrent);
                        @endphp
                        <div class="teacher-segment" style="margin-bottom: 0;">
                            <div class="teacher-segment-title">{{ $goal['title'] ?? 'Цель' }}</div>
                            <div class="teacher-meta-text" style="margin-top: 4px;">{{ $goalCurrent }} / {{ $goalTarget }} {{ $goal['unit'] ?? '' }}</div>
                            <div class="teacher-progress" style="margin-top: 8px;">
                                <div class="teacher-progress-fill" style="width: {{ $goalPercent }}%;"></div>
                            </div>
                            <div class="teacher-meta-text" style="margin-top: 6px;">
                                @if($goalRemain > 0)
                                    Осталось: {{ $goalRemain }} {{ $goal['unit'] ?? '' }}
                                @else
                                    Цель выполнена
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="teacher-courses-grid">
                    @forelse(($studentAchievements ?? []) as $achievement)
                        <article class="teacher-course-card">
                            <div class="teacher-course-card-body">
                                <div class="teacher-course-card-kicker">Достижение</div>
                                <h4 class="course-module-title">{{ $achievement['title'] }}</h4>
                                <p class="teacher-course-card-copy">{{ $achievement['description'] !== '' ? $achievement['description'] : 'Описание не задано.' }}</p>
                                <div class="teacher-course-card-meta">
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
                                                    <a href="{{ route('teacher.indes', ['page' => 'lesson_view', 'id' => $lessonItem['id']]) }}" class="btn btn-primary">Открыть урок</a>
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

            <div class="teacher-card">
                <h3 class="teacher-section-title">Доступные курсы</h3>
                <div class="teacher-courses-grid">
                    @forelse(($availableCourses ?? []) as $availableCourse)
                        @php
                            $availableStats = $courseRatings[(int) $availableCourse['id']] ?? ['avg' => 0, 'count' => 0];
                        @endphp
                        <article class="teacher-course-card">
                            <div class="teacher-course-card-body">
                                <div class="teacher-course-card-kicker">Курс</div>
                                <h3 class="teacher-course-card-title">{{ $availableCourse['title'] }}</h3>
                                <p class="teacher-course-card-copy">
                                    {{ $availableCourse['description'] !== '' ? $availableCourse['description'] : 'Описание пока не добавлено.' }}
                                </p>
                                <div class="teacher-course-card-meta">
                                    <span>{{ $availableCourse['modules_count'] }} модулей</span>
                                    <span>{{ $availableCourse['level'] !== '' ? $availableCourse['level'] : 'Уровень не указан' }}</span>
                                    <span>Рейтинг: {{ number_format((float) ($availableStats['avg'] ?? 0), 2) }}</span>
                                    <span>Оценок: {{ (int) ($availableStats['count'] ?? 0) }}</span>
                                </div>
                            </div>
                            <div class="teacher-course-card-actions">
                                <form action="{{ route('courses.enroll', ['course' => $availableCourse['id']]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Выбрать курс</button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="teacher-muted">Свободных курсов для выбора сейчас нет.</div>
                    @endforelse
                </div>
            </div>

