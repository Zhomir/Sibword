            <div class="teacher-card">
                <div class="teacher-catalog-head">
                    <div>
                        <h2 class="teacher-section-title">Мои курсы</h2>
                    </div>
                    <a href="{{ route('teacher.indes', ['page' => 'teacher_panel']) }}" class="btn btn-primary">Открыть редактор</a>
                </div>

                <div class="teacher-catalog-search">
                    <input
                        type="text"
                        id="teacherCourseSearch"
                        class="teacher-input"
                        placeholder="Название курса"
                        data-input-action="filter-teacher-courses"
                    >
                </div>

                <div id="teacherCoursesGrid" class="teacher-courses-grid">
                    @forelse(($curriculum['courses'] ?? []) as $course)
                        @php
                            $courseModules = collect($course['modules'] ?? []);
                            $courseLessonsCount = $courseModules->sum(fn ($module) => count($module['lesson_ids'] ?? []));
                            $courseModulesText = $courseModules->count() . ' модул' . ($courseModules->count() === 1 ? 'ь' : ($courseModules->count() < 5 ? 'я' : 'ей'));
                            $courseLessonsText = $courseLessonsCount . ' урок' . ($courseLessonsCount === 1 ? '' : ($courseLessonsCount < 5 ? 'а' : 'ов'));
                        @endphp
                        <article
                            class="teacher-course-card"
                            data-course-title="{{ mb_strtolower($course['title'] ?? '') }}"
                        >
                            <div class="teacher-course-card-body">
                                <div class="teacher-course-card-kicker">Курс</div>
                                <h3 class="teacher-course-card-title">{{ $course['title'] }}</h3>
                                <p class="teacher-course-card-copy">
                                    В этом курсе сейчас {{ $courseModulesText }} и {{ $courseLessonsText }}.
                                </p>
                                <div class="teacher-course-card-meta">
                                    <span>{{ $courseModulesText }}</span>
                                    <span>{{ $courseLessonsText }}</span>
                                </div>
                            </div>
                            <div class="teacher-course-card-actions">
                                <a href="{{ route('teacher.indes', ['page' => 'teacher_panel']) }}" class="btn btn-primary btn-sm">Перейти в редактор</a>
                            </div>
                        </article>
                    @empty
                        <div class="teacher-muted">Пока нет ни одного курса. Сначала создайте курс в редакторе.</div>
                    @endforelse
                </div>
            </div>

