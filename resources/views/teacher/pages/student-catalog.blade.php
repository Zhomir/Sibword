<div class="student-learning-layout student-catalog-layout">
    <aside class="student-learning-left ui-card">
        <div class="student-learning-left-cover" aria-hidden="true"></div>
        <nav class="student-learning-left-nav" aria-label="Разделы обучения">
            <a href="{{ route('student.courses') }}">Мое обучение</a>
            <a class="is-active" href="{{ route('student.catalog') }}">Каталог курсов</a>
            <a href="{{ route('student.progress') }}">Прогресс</a>
            <a href="{{ route('student.profile') }}">Профиль</a>
        </nav>
    </aside>

    <div class="student-learning-main">
        <header class="student-learning-head">
            <h2>Каталог курсов</h2>
            <p>Выберите курс и добавьте его в личную траекторию.</p>
        </header>

        <form method="GET" action="{{ route('student.catalog') }}" class="ui-card student-catalog-filters">
            <div class="student-catalog-filter-row">
                <label class="grow">
                    <span>Поиск</span>
                    <input type="text" name="q" value="{{ $catalogQuery ?? '' }}" class="ui-input" placeholder="Название, уровень или описание">
                </label>
                <label>
                    <span>Сортировка</span>
                    <select name="sort" class="ui-select">
                        <option value="rating_desc" @selected(($catalogSort ?? 'rating_desc') === 'rating_desc')>Рейтинг: по убыванию</option>
                        <option value="rating_asc" @selected(($catalogSort ?? '') === 'rating_asc')>Рейтинг: по возрастанию</option>
                        <option value="title_asc" @selected(($catalogSort ?? '') === 'title_asc')>Название: А-Я</option>
                        <option value="title_desc" @selected(($catalogSort ?? '') === 'title_desc')>Название: Я-А</option>
                    </select>
                </label>
                <label>
                    <span>Уровень</span>
                    <select name="level" class="ui-select">
                        <option value="">Любой</option>
                        @foreach(($catalogLevelOptions ?? []) as $levelOption)
                            <option value="{{ $levelOption }}" @selected(($catalogLevel ?? '') === $levelOption)>{{ $levelOption }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Мин. рейтинг</span>
                    <select name="min_rating" class="ui-select">
                        <option value="0" @selected((int) ($catalogMinRating ?? 0) === 0)>Не учитывать</option>
                        <option value="5" @selected((int) ($catalogMinRating ?? 0) === 5)>От 5.0</option>
                        <option value="4" @selected((int) ($catalogMinRating ?? 0) === 4)>От 4.0</option>
                        <option value="3" @selected((int) ($catalogMinRating ?? 0) === 3)>От 3.0</option>
                        <option value="2" @selected((int) ($catalogMinRating ?? 0) === 2)>От 2.0</option>
                        <option value="1" @selected((int) ($catalogMinRating ?? 0) === 1)>От 1.0</option>
                    </select>
                </label>
            </div>

            <div class="student-catalog-filter-actions">
                <button type="submit" class="ui-btn ui-btn-primary">Показать</button>
                <a href="{{ route('student.catalog') }}" class="ui-btn ui-btn-secondary">Сбросить</a>
            </div>
        </form>

        <section class="student-catalog-grid">
            @forelse(($availableCourses ?? []) as $course)
                <article class="ui-card student-catalog-card">
                    <div class="student-catalog-topline" aria-hidden="true"></div>
                    <h3>{{ $course['title'] }}</h3>
                    <div class="student-catalog-rating">
                        <strong>★ {{ number_format((float) ($course['rating_avg'] ?? 0), 2) }}</strong>
                        <span>{{ (int) ($course['rating_count'] ?? 0) }} оценок</span>
                    </div>
                    <p>{{ $course['description'] !== '' ? $course['description'] : 'Описание пока не добавлено.' }}</p>

                    <div class="student-catalog-meta">
                        <span>{{ $course['modules_count'] }} модулей</span>
                        <span>{{ $course['level'] !== '' ? $course['level'] : 'Уровень не указан' }}</span>
                    </div>

                    <form action="{{ route('courses.enroll', ['course' => $course['id']]) }}" method="POST" class="student-catalog-card-actions">
                        @csrf
                        <input type="hidden" name="return_page" value="student_catalog">
                        <input type="hidden" name="return_q" value="{{ $catalogQuery ?? '' }}">
                        <input type="hidden" name="return_sort" value="{{ $catalogSort ?? 'rating_desc' }}">
                        <input type="hidden" name="return_level" value="{{ $catalogLevel ?? '' }}">
                        <input type="hidden" name="return_min_rating" value="{{ (int) ($catalogMinRating ?? 0) }}">
                        <button type="submit" class="ui-btn ui-btn-primary">Начать курс</button>
                    </form>
                </article>
            @empty
                <div class="ui-alert warning">По выбранным параметрам курсы не найдены.</div>
            @endforelse
        </section>
    </div>
</div>
