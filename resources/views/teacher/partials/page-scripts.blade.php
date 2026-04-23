@if ($page === 'lesson_view')
@php
    $lessonViewData = [
        'steps' => $lesson['steps'] ?? [],
        'lessonId' => (int) ($lessonId ?? 0),
        'completeUrl' => route('teacher.indes', ['page' => 'student_dashboard']),
        'completeRequestUrl' => route('teacher.indes.handle'),
        'csrfToken' => csrf_token(),
    ];
@endphp
<script src="{{ asset('js/lesson-engine.js') }}"></script>
<script id="teacher-lesson-view-data" type="application/json">@json($lessonViewData)</script>
<script>
(() => {
    const payloadEl = document.getElementById('teacher-lesson-view-data');
    if (!payloadEl || typeof LessonEngine === 'undefined') return;
    try {
        const payload = JSON.parse(payloadEl.textContent || '{}');
        document.body.classList.add('lesson-active');
        new LessonEngine(payload);
    } catch (error) {
        console.error('Lesson init failed', error);
    }
})();
</script>
<script>
(() => {
    const panelSelector = '#lesson-forum';

    const reloadForumPanel = async () => {
        const panel = document.querySelector(panelSelector);
        if (!panel) return;

        try {
            const response = await fetch(window.location.href, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!response.ok) return;

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextPanel = doc.querySelector(panelSelector);
            if (!nextPanel) return;

            panel.innerHTML = nextPanel.innerHTML;
        } catch (error) {
            console.warn('Forum partial reload failed', error);
        }
    };

    const postForm = async (form) => {
        const formData = new FormData(form);
        return fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
    };

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (form.matches('.js-like-form')) {
            event.preventDefault();

            const btn = form.querySelector('.js-like-button');
            if (btn) btn.disabled = true;

            try {
                const response = await postForm(form);
                if (!response.ok) throw new Error(`Like request failed: ${response.status}`);
                const payload = await response.json();

                const countEl = form.querySelector('.js-like-count');
                const likeBtn = form.querySelector('.js-like-button');
                if (countEl) countEl.textContent = String(Number(payload.likes_count || 0));
                if (likeBtn) likeBtn.classList.toggle('is-liked', Boolean(payload.liked));
            } catch (error) {
                console.warn(error);
                await reloadForumPanel();
            } finally {
                if (btn) btn.disabled = false;
            }

            return;
        }

        if (form.matches('.js-comment-form')) {
            event.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const response = await postForm(form);
                if (!response.ok) throw new Error(`Comment request failed: ${response.status}`);
                await response.json();

                const textarea = form.querySelector('textarea[name="body"]');
                if (textarea) textarea.value = '';
                await reloadForumPanel();
            } catch (error) {
                console.warn(error);
                window.location.reload();
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        }
    });
})();
</script>
@endif
@if (in_array($page, ['teacher_panel', 'teacher_courses'], true))
@php
    $teacherIndesData = [
        'dictionary' => $dictionary ?? [],
        'curriculum' => $curriculum ?? ['courses' => []],
        'courseTitleMap' => $courseTitleMap ?? [],
        'moduleTitleMap' => $moduleTitleMap ?? [],
        'cmsUrls' => [
            'getLessonBase' => route('api.lesson.get', ['id' => '__ID__']),
            'saveLesson' => route('api.lesson.save'),
            'deleteLesson' => route('api.lesson.delete'),
            'addCourse' => route('api.course.add'),
            'addModule' => route('api.module.add'),
            'addWord' => route('api.dictionary.add'),
            'deleteWord' => route('api.dictionary.delete'),
            'uploadMedia' => route('teacher.assets.upload'),
        ],
    ];
@endphp
<script id="teacher-indes-data" type="application/json">@json($teacherIndesData)</script>
<script src="{{ asset('js/teacher-indes.bootstrap.js') }}"></script>
<script src="{{ asset('js/teacher-indes.core.js') }}"></script>
<script src="{{ asset('js/teacher-indes.actions.js') }}"></script>
<script>
(() => {
    const syncHiddenInputs = () => {
        const courseId = document.getElementById('courseSelector')?.value || '';
        const moduleId = document.getElementById('lessonModule')?.value || '';
        const moduleCourse = document.getElementById('moduleCourseIdInput');
        const lessonCourse = document.getElementById('lessonCourseIdInput');
        const lessonModule = document.getElementById('lessonModuleIdInput');
        if (moduleCourse) moduleCourse.value = courseId;
        if (lessonCourse) lessonCourse.value = courseId;
        if (lessonModule) lessonModule.value = moduleId;
    };
    document.addEventListener('change', (event) => {
        if (event.target?.id === 'courseSelector' || event.target?.id === 'lessonModule') {
            syncHiddenInputs();
        }
    });
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncHiddenInputs);
    } else {
        syncHiddenInputs();
    }
})();
</script>
@elseif ($page === 'student_dashboard')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-progress-fill').forEach((el) => {
        const value = Number(el.getAttribute('data-progress') || 0);
        const clamped = Math.max(0, Math.min(100, value));
        el.style.width = `${clamped}%`;
    });
});
</script>
@endif
