<style>
#teacherCoursesGrid.teacher-courses-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    align-items: stretch;
}

#teacherCoursesGrid .teacher-course-card {
    flex-direction: column;
    height: 100%;
}

#teacherCoursesGrid .teacher-course-card-actions {
    margin-top: auto;
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
}

#teacherCoursesGrid .teacher-course-card-actions .btn {
    width: 100%;
}

@media (max-width: 1400px) {
    #teacherCoursesGrid.teacher-courses-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 1100px) {
    #teacherCoursesGrid.teacher-courses-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    #teacherCoursesGrid.teacher-courses-grid {
        grid-template-columns: 1fr;
    }
}
</style>
            <div class="teacher-card">
                <div class="teacher-catalog-head">
                    <div>
                        <h2 class="teacher-section-title">Мои курсы</h2>
                    </div>
                    <a href="{{ route('teacher.panel.page') }}" class="btn btn-primary">Открыть редактор</a>
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
                <div id="teacherCoursesAjaxStatus" class="teacher-muted" style="margin: 8px 0 0;"></div>
                @php
                    $allCourses = collect($curriculum['courses'] ?? [])->values();
                    $perPage = 4;
                    $totalCourses = $allCourses->count();
                    $totalPages = max(1, (int) ceil($totalCourses / $perPage));
                    $currentPage = (int) request()->query('courses_page', 1);
                    if ($currentPage < 1) {
                        $currentPage = 1;
                    }
                    if ($currentPage > $totalPages) {
                        $currentPage = $totalPages;
                    }
                    $offset = ($currentPage - 1) * $perPage;
                    $pagedCourses = $allCourses->slice($offset, $perPage)->values();
                @endphp

                @if($totalCourses > 0)
                    <div class="teacher-actions" style="margin: 10px 0 6px; justify-content: space-between;">
                        <div class="teacher-muted">Страница {{ $currentPage }} из {{ $totalPages }}</div>
                        <div style="display:flex; gap:8px;">
                            @if($currentPage > 1)
                                <a href="{{ route('teacher.courses.page', ['courses_page' => $currentPage - 1]) }}" class="btn btn-outline btn-sm">Назад</a>
                            @else
                                <button type="button" class="btn btn-outline btn-sm" disabled>Назад</button>
                            @endif
                            @if($currentPage < $totalPages)
                                <a href="{{ route('teacher.courses.page', ['courses_page' => $currentPage + 1]) }}" class="btn btn-primary btn-sm">Далее</a>
                            @else
                                <button type="button" class="btn btn-primary btn-sm" disabled>Далее</button>
                            @endif
                        </div>
                    </div>
                @endif

                <div id="teacherCoursesGrid" class="teacher-courses-grid">
                    @forelse($pagedCourses as $course)
                        @php
                            $courseModules = collect($course['modules'] ?? []);
                            $courseLessonsCount = $courseModules->sum(fn ($module) => count($module['lesson_ids'] ?? []));
                            $courseModulesText = $courseModules->count() . ' модул' . ($courseModules->count() === 1 ? 'ь' : ($courseModules->count() < 5 ? 'я' : 'ей'));
                            $courseLessonsText = $courseLessonsCount . ' урок' . ($courseLessonsCount === 1 ? '' : ($courseLessonsCount < 5 ? 'а' : 'ов'));
                            $isPublished = ($course['status'] ?? 'draft') === 'published';
                        @endphp
                        <article
                            class="teacher-course-card"
                            data-course-id="{{ (int) ($course['id'] ?? 0) }}"
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
                                    <span class="js-course-status-label">{{ $isPublished ? 'Опубликован' : 'Черновик' }}</span>
                                </div>
                            </div>
                            <div class="teacher-course-card-actions">
                                <form action="{{ route('teacher.courses.togglePublication', ['course' => $course['id']]) }}" method="POST" class="js-ajax-course-form" data-action-type="toggle-publication">
                                    @csrf
                                    <button type="submit" class="btn {{ $isPublished ? 'btn-outline' : 'btn-success' }} btn-sm js-toggle-publication-btn">
                                        {{ $isPublished ? 'Снять с публикации' : 'Опубликовать' }}
                                    </button>
                                </form>
                                <a href="{{ route('teacher.panel.page') }}" class="btn btn-primary btn-sm">Перейти в редактор</a>
                                <form
                                    action="{{ route('teacher.courses.delete', ['course' => $course['id']]) }}"
                                    method="POST"
                                    class="js-delete-course-form js-ajax-course-form"
                                    data-action-type="delete-course"
                                    data-course-title="{{ $course['title'] }}"
                                >
                                    @csrf
                                    <input type="hidden" name="confirm_course_title" value="">
                                    <button type="submit" class="btn btn-danger btn-sm">Удалить курс</button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="teacher-muted">Пока нет ни одного курса. Сначала создайте курс в редакторе.</div>
                    @endforelse
                </div>
                @if($totalCourses > 0)
                    <div class="teacher-actions" style="margin-top: 14px; justify-content: space-between;">
                        <div class="teacher-muted">Страница {{ $currentPage }} из {{ $totalPages }}</div>
                        <div style="display:flex; gap:8px;">
                            @if($currentPage > 1)
                                <a href="{{ route('teacher.courses.page', ['courses_page' => $currentPage - 1]) }}" class="btn btn-outline btn-sm">Назад</a>
                            @else
                                <button type="button" class="btn btn-outline btn-sm" disabled>Назад</button>
                            @endif
                            @if($currentPage < $totalPages)
                                <a href="{{ route('teacher.courses.page', ['courses_page' => $currentPage + 1]) }}" class="btn btn-primary btn-sm">Далее</a>
                            @else
                                <button type="button" class="btn btn-primary btn-sm" disabled>Далее</button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div id="deleteCourseModal" class="teacher-modal" style="display: none;">
                <div class="teacher-modal-card teacher-modal-card--compact">
                    <h3 class="teacher-section-title">Удаление курса</h3>
                    <p class="teacher-muted teacher-field">
                        Чтобы подтвердить удаление, введите точное название курса:
                    </p>
                    <div id="deleteCourseModalCourseTitle" class="teacher-field teacher-label"></div>
                    <input
                        type="text"
                        id="deleteCourseModalInput"
                        class="teacher-input teacher-field"
                        placeholder="Введите название курса"
                    >
                    <div id="deleteCourseModalError" class="teacher-modal-error is-hidden"></div>
                    <div class="teacher-modal-actions teacher-modal-actions--end">
                        <button type="button" id="deleteCourseModalCancel" class="btn btn-muted btn-sm">Отмена</button>
                        <button type="button" id="deleteCourseModalConfirm" class="btn btn-danger btn-sm">Удалить курс</button>
                    </div>
                </div>
            </div>

<script>
(() => {
    const forms = document.querySelectorAll('.js-delete-course-form');
    if (!forms.length) return;
    const statusBox = document.getElementById('teacherCoursesAjaxStatus');

    const modal = document.getElementById('deleteCourseModal');
    const modalInput = document.getElementById('deleteCourseModalInput');
    const modalTitle = document.getElementById('deleteCourseModalCourseTitle');
    const modalError = document.getElementById('deleteCourseModalError');
    const modalCancel = document.getElementById('deleteCourseModalCancel');
    const modalConfirm = document.getElementById('deleteCourseModalConfirm');

    if (!modal || !modalInput || !modalTitle || !modalError || !modalCancel || !modalConfirm) return;

    let activeForm = null;
    let activeCourseTitle = '';

    const hideError = () => {
        modalError.textContent = '';
        modalError.classList.add('is-hidden');
    };

    const showError = (message) => {
        modalError.textContent = message;
        modalError.classList.remove('is-hidden');
    };

    const closeModal = () => {
        modal.style.display = 'none';
        hideError();
        modalInput.value = '';
        activeForm = null;
        activeCourseTitle = '';
    };

    const showStatus = (message, isError = false) => {
        if (!statusBox) return;
        statusBox.textContent = message;
        statusBox.style.color = isError ? '#ff9b9b' : '';
    };

    const postAjax = async (form) => {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        let payload = null;
        try {
            payload = await response.json();
        } catch (error) {
            payload = null;
        }

        if (!response.ok || !payload || payload.success !== true) {
            throw new Error(payload?.message || `Request failed: ${response.status}`);
        }

        return payload;
    };

    const updatePublicationUi = (form, nextStatus) => {
        const card = form.closest('.teacher-course-card');
        if (!card) return;

        const statusLabel = card.querySelector('.js-course-status-label');
        const button = form.querySelector('.js-toggle-publication-btn');
        const isPublished = String(nextStatus) === 'published';

        if (statusLabel) {
            statusLabel.textContent = isPublished ? 'Опубликован' : 'Черновик';
        }
        if (button) {
            button.textContent = isPublished ? 'Снять с публикации' : 'Опубликовать';
            button.classList.toggle('btn-outline', isPublished);
            button.classList.toggle('btn-success', !isPublished);
        }
    };

    const openModal = (form, courseTitle) => {
        activeForm = form;
        activeCourseTitle = String(courseTitle || '').trim();
        modalTitle.textContent = activeCourseTitle;
        modalInput.value = '';
        hideError();
        modal.style.display = 'flex';
        setTimeout(() => modalInput.focus(), 0);
    };

    modalCancel.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.style.display === 'flex') {
            closeModal();
        }
    });

    modalConfirm.addEventListener('click', () => {
        if (!activeForm) return;
        const formToSubmit = activeForm;

        const typedValue = String(modalInput.value || '').trim();
        if (typedValue.toLowerCase() !== activeCourseTitle.toLowerCase()) {
            showError('Название не совпадает. Проверьте ввод и попробуйте снова.');
            modalInput.focus();
            return;
        }

        const hiddenInput = formToSubmit.querySelector('input[name="confirm_course_title"]');
        if (hiddenInput) {
            hiddenInput.value = typedValue;
        }

        closeModal();
        postAjax(formToSubmit)
            .then((payload) => {
                const card = formToSubmit.closest('.teacher-course-card');
                if (card) {
                    card.remove();
                }
                showStatus(payload.message || 'Курс удален.');
            })
            .catch((error) => {
                showStatus(error.message || 'Не удалось удалить курс.', true);
            });
    });

    modalInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            modalConfirm.click();
        }
    });

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const courseTitle = String(form.getAttribute('data-course-title') || '').trim();
            openModal(form, courseTitle);
        });
    });

    const ajaxForms = document.querySelectorAll('.js-ajax-course-form[data-action-type="toggle-publication"]');
    ajaxForms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            if (button) button.disabled = true;
            try {
                const payload = await postAjax(form);
                updatePublicationUi(form, payload?.course?.status || '');
                showStatus(payload.message || 'Статус курса обновлен.');
            } catch (error) {
                showStatus(error.message || 'Не удалось изменить статус курса.', true);
            } finally {
                if (button) button.disabled = false;
            }
        });
    });
})();
</script>


