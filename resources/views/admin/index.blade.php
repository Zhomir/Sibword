@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/teacher-indes.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">

<div class="teacher-page">
    <div class="teacher-shell">
        <header class="teacher-header">
            <div class="teacher-title">Панель администратора <span class="teacher-subtitle">Пользователи и база знаний</span></div>
        </header>

        <section class="teacher-card admin-metrics">
            <article class="admin-metric">
                <span class="admin-metric-label">Всего пользователей</span>
                <strong class="admin-metric-value">{{ $totalUsers }}</strong>
            </article>
            <article class="admin-metric">
                <span class="admin-metric-label">Всего слов в базе</span>
                <strong class="admin-metric-value">{{ $totalDictionaryEntries }}</strong>
            </article>
            <article class="admin-metric">
                <span class="admin-metric-label">Режим работы</span>
                <strong class="admin-metric-value">Масштабируемый список</strong>
            </article>
        </section>

        @if (session('admin_status'))
            <div class="teacher-card admin-status admin-status--success">{{ session('admin_status') }}</div>
        @endif

        @if ($errors->any())
            <div class="teacher-card admin-status admin-status--error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="teacher-grid">
            <section class="teacher-card">
                <h2 class="teacher-section-title">Роли пользователей</h2>
                <div class="teacher-muted teacher-field">Список разбит по страницам: можно быстро найти пользователя и изменить роль без длинной прокрутки.</div>

                <form action="{{ route('admin.index') }}" method="GET" class="admin-toolbar teacher-field">
                    <input type="hidden" name="word_search" value="{{ $wordFilters['search'] }}">
                    <input type="hidden" name="word_per_page" value="{{ $wordFilters['per_page'] }}">

                    <div>
                        <label for="user_search" class="teacher-label">Поиск по имени или email</label>
                        <input id="user_search" type="text" name="user_search" value="{{ $userFilters['search'] }}" class="teacher-input" placeholder="Например: ivan или @mail.ru">
                    </div>

                    <div>
                        <label for="user_role" class="teacher-label">Роль</label>
                        <select id="user_role" name="user_role" class="teacher-select">
                            <option value="">Все роли</option>
                            <option value="student" @selected($userFilters['role'] === 'student')>Студент</option>
                            <option value="teacher" @selected($userFilters['role'] === 'teacher')>Преподаватель</option>
                            <option value="admin" @selected($userFilters['role'] === 'admin')>Администратор</option>
                        </select>
                    </div>

                    <div>
                        <label for="user_per_page" class="teacher-label">На странице</label>
                        <select id="user_per_page" name="user_per_page" class="teacher-select">
                            @foreach([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((int) $userFilters['per_page'] === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="admin-toolbar-actions">
                        <button type="submit" class="btn btn-primary btn-sm">Применить</button>
                        <a href="{{ route('admin.index') }}" class="btn btn-outline btn-sm">Сбросить</a>
                    </div>
                </form>

                <div class="admin-table-wrap admin-table-wrap--tall">
                    <table class="admin-table">
                        <thead>
                            <tr class="admin-table-head-row">
                                <th class="admin-th">Пользователь</th>
                                <th class="admin-th">Email</th>
                                <th class="admin-th">Роль</th>
                                <th class="admin-th">Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr class="admin-table-row">
                                    <td class="admin-td">{{ $user->name }}</td>
                                    <td class="admin-td">{{ $user->email }}</td>
                                    <td class="admin-td">
                                        <form action="{{ route('admin.users.role', $user) }}" method="POST" class="teacher-inline-input admin-inline-center">
                                            @csrf
                                            <select name="role" class="teacher-select admin-role-select">
                                                <option value="student" @selected($user->role === 'student')>Студент</option>
                                                <option value="teacher" @selected($user->role === 'teacher')>Преподаватель</option>
                                                <option value="admin" @selected($user->role === 'admin')>Администратор</option>
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-sm">Сохранить</button>
                                        </form>
                                    </td>
                                    <td class="admin-td">
                                        @if (auth()->id() === $user->id)
                                            <span class="teacher-muted">Текущий пользователь</span>
                                        @else
                                            <span class="teacher-muted">Роль можно изменить</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="admin-table-row">
                                    <td colspan="4" class="admin-td teacher-muted">По выбранным фильтрам пользователей не найдено.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="admin-pager teacher-field">
                    <div class="teacher-muted">
                        @if($users->count() > 0)
                            Показаны {{ $users->firstItem() }}-{{ $users->lastItem() }} из {{ $users->total() }}
                        @else
                            Показаны 0 из {{ $users->total() }}
                        @endif
                    </div>
                    <div class="admin-pager-actions">
                        @if($users->previousPageUrl())
                            <a href="{{ $users->previousPageUrl() }}" class="btn btn-outline btn-sm">Назад</a>
                        @else
                            <span class="btn btn-muted btn-sm admin-btn-disabled">Назад</span>
                        @endif

                        <span class="admin-page-indicator">Страница {{ $users->currentPage() }} из {{ max(1, $users->lastPage()) }}</span>

                        @if($users->nextPageUrl())
                            <a href="{{ $users->nextPageUrl() }}" class="btn btn-outline btn-sm">Вперёд</a>
                        @else
                            <span class="btn btn-muted btn-sm admin-btn-disabled">Вперёд</span>
                        @endif
                    </div>
                </div>
            </section>

            <section class="teacher-card">
                <h2 class="teacher-section-title">База знаний</h2>

                <form action="{{ route('admin.knowledge.store') }}" method="POST" class="teacher-field">
                    @csrf
                    <div class="teacher-grid admin-form-grid">
                        <div><label class="teacher-label">Слово</label><input type="text" name="word" class="teacher-input" required></div>
                        <div><label class="teacher-label">Перевод</label><input type="text" name="translation" class="teacher-input" required></div>
                        <div><label class="teacher-label">Транскрипция</label><input type="text" name="transcription" class="teacher-input"></div>
                        <div><label class="teacher-label">Сложность</label><input type="number" step="0.01" min="0" max="9.99" name="complexity_index" class="teacher-input" value="0"></div>
                    </div>
                    <div class="teacher-actions"><button type="submit" class="btn btn-success">Добавить запись</button></div>
                </form>

                <div class="teacher-field admin-toolbar-actions">
                    <a href="{{ route('admin.knowledge.export') }}" class="btn btn-outline btn-sm">Экспорт CSV</a>
                </div>
                <form action="{{ route('admin.knowledge.import') }}" method="POST" enctype="multipart/form-data" class="teacher-field">
                    @csrf
                    <div class="teacher-grid admin-form-grid">
                        <div>
                            <label class="teacher-label">Импорт CSV</label>
                            <input type="file" name="csv_file" accept=".csv,.txt" class="teacher-file" required>
                        </div>
                    </div>
                    <div class="teacher-actions"><button type="submit" class="btn btn-primary btn-sm">Импортировать</button></div>
                </form>

                <form action="{{ route('admin.index') }}" method="GET" class="admin-toolbar teacher-field">
                    <input type="hidden" name="user_search" value="{{ $userFilters['search'] }}">
                    <input type="hidden" name="user_role" value="{{ $userFilters['role'] }}">
                    <input type="hidden" name="user_per_page" value="{{ $userFilters['per_page'] }}">

                    <div>
                        <label for="word_search" class="teacher-label">Поиск по слову, переводу, транскрипции</label>
                        <input id="word_search" type="text" name="word_search" value="{{ $wordFilters['search'] }}" class="teacher-input" placeholder="Например: привет, hello, [pri-vet]">
                    </div>

                    <div>
                        <label for="word_per_page" class="teacher-label">На странице</label>
                        <select id="word_per_page" name="word_per_page" class="teacher-select">
                            @foreach([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((int) $wordFilters['per_page'] === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="admin-toolbar-actions">
                        <button type="submit" class="btn btn-primary btn-sm">Применить</button>
                        <a href="{{ route('admin.index') }}" class="btn btn-outline btn-sm">Сбросить</a>
                    </div>
                </form>

                <div class="admin-table-wrap admin-table-wrap--tall">
                    <table class="admin-table">
                        <thead>
                            <tr class="admin-table-head-row">
                                <th class="admin-th">Слово</th>
                                <th class="admin-th">Перевод</th>
                                <th class="admin-th">Транскрипция</th>
                                <th class="admin-th">Сложность</th>
                                <th class="admin-th">Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dictionaryEntries as $entry)
                                <tr class="admin-table-row">
                                    <td class="admin-td">{{ $entry->word }}</td>
                                    <td class="admin-td">{{ $entry->translation }}</td>
                                    <td class="admin-td">{{ $entry->transcription ?: '-' }}</td>
                                    <td class="admin-td">{{ number_format((float) $entry->complexity_index, 2) }}</td>
                                    <td class="admin-td">
                                        <form action="{{ route('admin.knowledge.delete', $entry) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm">Удалить</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr class="admin-table-row">
                                    <td colspan="5" class="admin-td teacher-muted">По выбранным фильтрам записи не найдены.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="admin-pager teacher-field">
                    <div class="teacher-muted">
                        @if($dictionaryEntries->count() > 0)
                            Показаны {{ $dictionaryEntries->firstItem() }}-{{ $dictionaryEntries->lastItem() }} из {{ $dictionaryEntries->total() }}
                        @else
                            Показаны 0 из {{ $dictionaryEntries->total() }}
                        @endif
                    </div>
                    <div class="admin-pager-actions">
                        @if($dictionaryEntries->previousPageUrl())
                            <a href="{{ $dictionaryEntries->previousPageUrl() }}" class="btn btn-outline btn-sm">Назад</a>
                        @else
                            <span class="btn btn-muted btn-sm admin-btn-disabled">Назад</span>
                        @endif

                        <span class="admin-page-indicator">Страница {{ $dictionaryEntries->currentPage() }} из {{ max(1, $dictionaryEntries->lastPage()) }}</span>

                        @if($dictionaryEntries->nextPageUrl())
                            <a href="{{ $dictionaryEntries->nextPageUrl() }}" class="btn btn-outline btn-sm">Вперёд</a>
                        @else
                            <span class="btn btn-muted btn-sm admin-btn-disabled">Вперёд</span>
                        @endif
                    </div>
                </div>
            </section>
        </div>

        <section class="teacher-card teacher-field">
            <h2 class="teacher-section-title">Модерация отзывов курсов</h2>
            <div class="teacher-muted teacher-field">Управляйте публичностью отзывов. В рейтинге учитываются только одобренные отзывы.</div>

            <form action="{{ route('admin.index') }}" method="GET" class="admin-toolbar teacher-field">
                <input type="hidden" name="user_search" value="{{ $userFilters['search'] }}">
                <input type="hidden" name="user_role" value="{{ $userFilters['role'] }}">
                <input type="hidden" name="user_per_page" value="{{ $userFilters['per_page'] }}">
                <input type="hidden" name="word_search" value="{{ $wordFilters['search'] }}">
                <input type="hidden" name="word_per_page" value="{{ $wordFilters['per_page'] }}">
                <input type="hidden" name="report_status" value="{{ $reportFilters['status'] }}">

                <div>
                    <label for="review_status" class="teacher-label">Статус отзывов</label>
                    <select id="review_status" name="review_status" class="teacher-select">
                        <option value="pending" @selected(($reviewFilters['status'] ?? 'pending') === 'pending')>Неодобренные</option>
                        <option value="approved" @selected(($reviewFilters['status'] ?? '') === 'approved')>Одобренные</option>
                        <option value="all" @selected(($reviewFilters['status'] ?? '') === 'all')>Все</option>
                    </select>
                </div>
                <div class="admin-toolbar-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Применить</button>
                </div>
            </form>

            <div class="admin-table-wrap admin-table-wrap--tall">
                <table class="admin-table">
                    <thead>
                        <tr class="admin-table-head-row">
                            <th class="admin-th">Курс</th>
                            <th class="admin-th">Пользователь</th>
                            <th class="admin-th">Оценка</th>
                            <th class="admin-th">Отзыв</th>
                            <th class="admin-th">Статус</th>
                            <th class="admin-th">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($courseReviews as $review)
                            <tr class="admin-table-row">
                                <td class="admin-td">{{ $review->course->title ?? 'Курс удален' }}</td>
                                <td class="admin-td">
                                    <div>{{ $review->author->name ?? 'Пользователь' }}</div>
                                    <div class="teacher-muted">{{ $review->author->email ?? '-' }}</div>
                                </td>
                                <td class="admin-td">{{ (int) $review->rating }}/5</td>
                                <td class="admin-td">{{ \Illuminate\Support\Str::limit((string) ($review->review_text ?? ''), 200) }}</td>
                                <td class="admin-td">
                                    <span class="admin-report-status {{ $review->is_approved ? 'is-resolved' : 'is-pending' }}">
                                        {{ $review->is_approved ? 'Одобрен' : 'Скрыт' }}
                                    </span>
                                </td>
                                <td class="admin-td">
                                    <div class="admin-report-actions">
                                        @if(!$review->is_approved)
                                            <form method="POST" action="{{ route('admin.reviews.approve', ['courseReview' => $review->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm">Одобрить</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.reviews.reject', ['courseReview' => $review->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-outline btn-sm">Скрыть</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="admin-table-row">
                                <td colspan="6" class="admin-td teacher-muted">Отзывов по выбранному фильтру нет.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-pager teacher-field">
                <div class="teacher-muted">
                    @if($courseReviews->count() > 0)
                        Показаны {{ $courseReviews->firstItem() }}-{{ $courseReviews->lastItem() }} из {{ $courseReviews->total() }}
                    @else
                        Показаны 0 из {{ $courseReviews->total() }}
                    @endif
                </div>
                <div class="admin-pager-actions">
                    @if($courseReviews->previousPageUrl())
                        <a href="{{ $courseReviews->previousPageUrl() }}" class="btn btn-outline btn-sm">Назад</a>
                    @else
                        <span class="btn btn-muted btn-sm admin-btn-disabled">Назад</span>
                    @endif
                    <span class="admin-page-indicator">Страница {{ $courseReviews->currentPage() }} из {{ max(1, $courseReviews->lastPage()) }}</span>
                    @if($courseReviews->nextPageUrl())
                        <a href="{{ $courseReviews->nextPageUrl() }}" class="btn btn-outline btn-sm">Вперёд</a>
                    @else
                        <span class="btn btn-muted btn-sm admin-btn-disabled">Вперёд</span>
                    @endif
                </div>
            </div>
        </section>

        <section class="teacher-card teacher-field">
            <h2 class="teacher-section-title">Модерация форума</h2>
            <div class="teacher-muted teacher-field">Жалобы на сообщения из обсуждений уроков. Можно скрыть или вернуть сообщение и закрыть жалобу.</div>

            <form action="{{ route('admin.index') }}" method="GET" class="admin-toolbar teacher-field">
                <input type="hidden" name="user_search" value="{{ $userFilters['search'] }}">
                <input type="hidden" name="user_role" value="{{ $userFilters['role'] }}">
                <input type="hidden" name="user_per_page" value="{{ $userFilters['per_page'] }}">
                <input type="hidden" name="word_search" value="{{ $wordFilters['search'] }}">
                <input type="hidden" name="word_per_page" value="{{ $wordFilters['per_page'] }}">

                <div>
                    <label for="report_status" class="teacher-label">Статус жалоб</label>
                    <select id="report_status" name="report_status" class="teacher-select">
                        <option value="pending" @selected(($reportFilters['status'] ?? 'pending') === 'pending')>Только новые</option>
                        <option value="resolved" @selected(($reportFilters['status'] ?? '') === 'resolved')>Только обработанные</option>
                        <option value="all" @selected(($reportFilters['status'] ?? '') === 'all')>Все</option>
                    </select>
                </div>

                <div class="admin-toolbar-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Применить</button>
                </div>
            </form>

            <div class="admin-table-wrap admin-table-wrap--tall">
                <table class="admin-table">
                    <thead>
                        <tr class="admin-table-head-row">
                            <th class="admin-th">Дата</th>
                            <th class="admin-th">Статус</th>
                            <th class="admin-th">Причина</th>
                            <th class="admin-th">Сообщение</th>
                            <th class="admin-th">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($moderationReports as $report)
                            @php
                                $post = $forumPostsById[(int) $report->entity_id] ?? null;
                            @endphp
                            <tr class="admin-table-row">
                                <td class="admin-td">{{ optional($report->created_at)->format('d.m.Y H:i') }}</td>
                                <td class="admin-td">
                                    <span class="admin-report-status {{ $report->status === 'resolved' ? 'is-resolved' : 'is-pending' }}">
                                        {{ $report->status === 'resolved' ? 'Обработана' : 'Новая' }}
                                    </span>
                                </td>
                                <td class="admin-td">{{ $report->reason ?: '-' }}</td>
                                <td class="admin-td">
                                    @if($post)
                                        <div><strong>{{ $post->author->name ?? 'Пользователь' }}</strong></div>
                                        <div class="admin-report-message">{{ \Illuminate\Support\Str::limit((string) $post->body, 180) }}</div>
                                        <div class="teacher-muted">
                                            Тема: {{ $post->thread->title ?? 'Без темы' }}
                                        </div>
                                    @else
                                        <span class="teacher-muted">Сообщение уже удалено</span>
                                    @endif
                                </td>
                                <td class="admin-td">
                                    @if($report->status !== 'resolved')
                                        <div class="admin-report-actions">
                                            <form method="POST" action="{{ route('admin.moderation.hide', ['moderationAction' => $report->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm">Скрыть</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.moderation.restore', ['moderationAction' => $report->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-outline btn-sm">Вернуть</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="teacher-muted">Жалоба закрыта</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="admin-table-row">
                                <td colspan="5" class="admin-td teacher-muted">Жалоб по выбранному фильтру нет.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-pager teacher-field">
                <div class="teacher-muted">
                    @if($moderationReports->count() > 0)
                        Показаны {{ $moderationReports->firstItem() }}-{{ $moderationReports->lastItem() }} из {{ $moderationReports->total() }}
                    @else
                        Показаны 0 из {{ $moderationReports->total() }}
                    @endif
                </div>
                <div class="admin-pager-actions">
                    @if($moderationReports->previousPageUrl())
                        <a href="{{ $moderationReports->previousPageUrl() }}" class="btn btn-outline btn-sm">Назад</a>
                    @else
                        <span class="btn btn-muted btn-sm admin-btn-disabled">Назад</span>
                    @endif

                    <span class="admin-page-indicator">Страница {{ $moderationReports->currentPage() }} из {{ max(1, $moderationReports->lastPage()) }}</span>

                    @if($moderationReports->nextPageUrl())
                        <a href="{{ $moderationReports->nextPageUrl() }}" class="btn btn-outline btn-sm">Вперёд</a>
                    @else
                        <span class="btn btn-muted btn-sm admin-btn-disabled">Вперёд</span>
                    @endif
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
