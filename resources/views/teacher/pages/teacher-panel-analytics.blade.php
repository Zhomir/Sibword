@php
    $analytics = $teacherAnalytics ?? ['summary' => [], 'top_steps' => [], 'top_lessons' => [], 'risk_lessons' => [], 'filters' => [], 'courses' => []];
    $analyticsSummary = $analytics['summary'] ?? [];
    $topSteps = $analytics['top_steps'] ?? [];
    $topLessons = $analytics['top_lessons'] ?? [];
    $riskLessons = $analytics['risk_lessons'] ?? [];
    $analyticsFilters = $analytics['filters'] ?? ['course_id' => 0, 'period_days' => 30, 'risk_threshold' => 40];
    $analyticsCourses = $analytics['courses'] ?? [];
@endphp

<div class="teacher-card">
    <h3 class="teacher-heading-row">
        Мини-аналитика
        <span class="teacher-muted">По вашим курсам</span>
    </h3>

    <form action="{{ route('teacher.indes') }}" method="GET" class="teacher-inline-input teacher-field">
        <input type="hidden" name="page" value="teacher_panel">
        <div>
            <label class="teacher-label">Курс</label>
            <select name="analytics_course_id" class="teacher-select">
                <option value="0">Все мои курсы</option>
                @foreach($analyticsCourses as $course)
                    <option value="{{ $course['id'] }}" @selected((int) ($analyticsFilters['course_id'] ?? 0) === (int) $course['id'])>{{ $course['title'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="teacher-label">Период</label>
            <select name="analytics_period" class="teacher-select">
                <option value="7" @selected((int) ($analyticsFilters['period_days'] ?? 30) === 7)>7 дней</option>
                <option value="30" @selected((int) ($analyticsFilters['period_days'] ?? 30) === 30)>30 дней</option>
            </select>
        </div>
        <div>
            <label class="teacher-label">Порог риска, %</label>
            <input type="number" min="10" max="90" name="analytics_risk_threshold" value="{{ (int) ($analyticsFilters['risk_threshold'] ?? 40) }}" class="teacher-input">
        </div>
        <div class="teacher-actions">
            <button type="submit" class="btn btn-primary btn-sm">Обновить</button>
        </div>
    </form>

    <div class="teacher-courses-grid">
        <article class="teacher-course-card">
            <div class="teacher-course-card-body">
                <div class="teacher-course-card-kicker">Сводка ответов</div>
                <div class="teacher-course-card-meta">
                    <span>Ответов: {{ (int) ($analyticsSummary['answers_total'] ?? 0) }}</span>
                    <span>Ошибок: {{ (int) ($analyticsSummary['wrong_total'] ?? 0) }}</span>
                    <span>Доля ошибок: {{ number_format((float) ($analyticsSummary['error_percent'] ?? 0), 2) }}%</span>
                </div>
            </div>
        </article>
        <article class="teacher-course-card">
            <div class="teacher-course-card-body">
                <div class="teacher-course-card-kicker">Сводка попыток</div>
                <div class="teacher-course-card-meta">
                    <span>Попыток: {{ (int) ($analyticsSummary['attempts_total'] ?? 0) }}</span>
                    <span>Завершено: {{ (int) ($analyticsSummary['completed_total'] ?? 0) }}</span>
                    <span>Средний score: {{ number_format((float) ($analyticsSummary['avg_score'] ?? 0), 2) }}%</span>
                </div>
            </div>
        </article>
    </div>

    <div class="teacher-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 14px; margin-top: 14px;">
        <section class="teacher-segment">
            <div class="teacher-segment-head">
                <div>
                    <h4 class="teacher-segment-title">Топ проблемных заданий</h4>
                    <p class="teacher-segment-copy">Где чаще всего ошибаются студенты</p>
                </div>
            </div>
            <div class="table-scroll">
                <table class="teacher-table">
                    <tr class="teacher-table-head-row">
                        <th class="teacher-table-th">Задание</th>
                        <th class="teacher-table-th">Курс/урок</th>
                        <th class="teacher-table-th">Ошибки</th>
                    </tr>
                    @forelse($topSteps as $step)
                        <tr class="teacher-table-row">
                            <td class="teacher-table-td teacher-table-td--compact">{{ $step['step_title'] }}</td>
                            <td class="teacher-table-td teacher-table-td--compact">
                                <div>{{ $step['course_title'] }}</div>
                                <small class="teacher-muted">{{ $step['lesson_title'] }}</small>
                            </td>
                            <td class="teacher-table-td teacher-table-td--compact">
                                {{ $step['wrong_total'] }} / {{ $step['answers_total'] }} ({{ number_format((float) $step['error_percent'], 2) }}%)
                            </td>
                        </tr>
                    @empty
                        <tr class="teacher-table-row">
                            <td colspan="3" class="teacher-table-td teacher-muted">Недостаточно данных по ответам.</td>
                        </tr>
                    @endforelse
                </table>
            </div>
        </section>

        <section class="teacher-segment">
            <div class="teacher-segment-head">
                <div>
                    <h4 class="teacher-segment-title">Проблемные уроки</h4>
                    <p class="teacher-segment-copy">Уроки с наибольшей долей ошибок</p>
                </div>
            </div>
            <div class="table-scroll">
                <table class="teacher-table">
                    <tr class="teacher-table-head-row">
                        <th class="teacher-table-th">Урок</th>
                        <th class="teacher-table-th">Курс</th>
                        <th class="teacher-table-th">Ошибки</th>
                    </tr>
                    @forelse($topLessons as $lesson)
                        <tr class="teacher-table-row">
                            <td class="teacher-table-td teacher-table-td--compact">{{ $lesson['lesson_title'] }}</td>
                            <td class="teacher-table-td teacher-table-td--compact">{{ $lesson['course_title'] }}</td>
                            <td class="teacher-table-td teacher-table-td--compact">
                                {{ $lesson['wrong_total'] }} / {{ $lesson['answers_total'] }} ({{ number_format((float) $lesson['error_percent'], 2) }}%)
                            </td>
                        </tr>
                    @empty
                        <tr class="teacher-table-row">
                            <td colspan="3" class="teacher-table-td teacher-muted">Недостаточно данных по урокам.</td>
                        </tr>
                    @endforelse
                </table>
            </div>
        </section>

        <section class="teacher-segment">
            <div class="teacher-segment-head">
                <div>
                    <h4 class="teacher-segment-title">Уроки риска</h4>
                    <p class="teacher-segment-copy">Ошибка выше порога {{ (int) ($analyticsFilters['risk_threshold'] ?? 40) }}%</p>
                </div>
            </div>
            <div class="table-scroll">
                <table class="teacher-table">
                    <tr class="teacher-table-head-row">
                        <th class="teacher-table-th">Урок</th>
                        <th class="teacher-table-th">Курс</th>
                        <th class="teacher-table-th">Ошибка</th>
                    </tr>
                    @forelse($riskLessons as $lesson)
                        <tr class="teacher-table-row">
                            <td class="teacher-table-td teacher-table-td--compact">{{ $lesson['lesson_title'] }}</td>
                            <td class="teacher-table-td teacher-table-td--compact">{{ $lesson['course_title'] }}</td>
                            <td class="teacher-table-td teacher-table-td--compact">{{ number_format((float) $lesson['error_percent'], 2) }}%</td>
                        </tr>
                    @empty
                        <tr class="teacher-table-row">
                            <td colspan="3" class="teacher-table-td teacher-muted">По текущему фильтру уроков риска нет.</td>
                        </tr>
                    @endforelse
                </table>
            </div>
        </section>
    </div>
</div>
