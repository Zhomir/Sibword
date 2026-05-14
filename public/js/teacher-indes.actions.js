const actionHandlers = {};
let dictionaryFilterQuery = '';
let listenersBound = false;
const UI_MESSAGES = {
    brokenPayload: 'Данные редактора повреждены. Обновите страницу.',
    missingEndpoint: 'Серверный адрес для операции не настроен.',
    network: 'Сетевая ошибка. Проверьте соединение и повторите.',
};

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function isPromiseLike(value) {
    return Boolean(value && typeof value.then === 'function');
}

function setBusyState(element, isBusy) {
    if (!element) return;
    element.disabled = isBusy;
    if (isBusy) {
        element.setAttribute('aria-busy', 'true');
    } else {
        element.removeAttribute('aria-busy');
    }
}

function normalizeApiMessage(data, fallbackMessage) {
    if (data && typeof data.message === 'string' && data.message.trim() !== '') {
        return data.message;
    }
    return fallbackMessage;
}

async function apiRequest(url, payload, fallbackMessage, method = 'POST') {
    if (!url) {
        throw new Error(UI_MESSAGES.missingEndpoint);
    }
    const options = {
        method,
        headers: {
            'X-CSRF-TOKEN': getCsrfToken()
        }
    };

    if (payload !== undefined) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(payload);
    }

    let response;
    try {
        response = await fetch(url, options);
    } catch (error) {
        throw new Error(UI_MESSAGES.network);
    }

    const text = await response.text();
    let data = {};
    try {
        data = text ? JSON.parse(text) : {};
    } catch (error) {
        throw new Error(fallbackMessage);
    }

    if (!response.ok || data.success === false) {
        throw new Error(normalizeApiMessage(data, fallbackMessage));
    }

    return data;
}

function setLessonEditorVisible(isVisible) {
    const editor = document.getElementById('lessonEditor');
    if (!editor) return;
    editor.classList.toggle('is-hidden', !isVisible);
}

function setPreviewButtonVisible(isVisible) {
    const button = document.querySelector('[data-action="preview-lesson"]');
    if (!button) return;
    button.classList.toggle('is-hidden', !isVisible);
}

function parseDelimitedList(value) {
    return String(value || '')
        .split(/[,\r\n]/)
        .map(item => item.trim())
        .filter(Boolean);
}

function parseWordList(value) {
    return String(value || '')
        .trim()
        .split(/\s+/)
        .map(item => item.trim())
        .filter(Boolean);
}

function bindClickActions() {
    document.querySelectorAll('[data-action]').forEach(el => {
        el.addEventListener('click', (event) => {
            const action = el.getAttribute('data-action');
            if (!action) return;
            const handler = actionHandlers[action];
            if (handler) {
                event.preventDefault();
                handler(el, event);
            }
        });
    });
}

function bindGlobalListeners() {
    if (listenersBound) return;
    listenersBound = true;

    document.addEventListener('click', (event) => {
        const target = event.target?.closest?.('[data-action]');
        if (!target) return;
        const action = target.getAttribute('data-action');
        if (!action) return;
        const handler = actionHandlers[action];
        if (handler) {
            event.preventDefault();
            handler(target, event);
        }
    });

    document.addEventListener('change', (event) => {
        const target = event.target?.closest?.('[data-change-action]');
        if (!target) return;
        const action = target.getAttribute('data-change-action');
        if (!action) return;
        if (action === 'sync-module-selector') syncModuleSelector();
        if (action === 'sync-lesson-selector') syncLessonSelector();
        if (action === 'load-lesson') loadLesson();
        if (action === 'toggle-step-fields') toggleStepFields();
        if (action === 'toggle-source-mode-fields') toggleSourceModeFields();
    });

    document.addEventListener('input', (event) => {
        const target = event.target?.closest?.('[data-input-action]');
        if (!target) return;
        const action = target.getAttribute('data-input-action');
        if (action === 'filter-teacher-courses') {
            filterTeacherCourses(target.value || '');
        }
        if (action === 'filter-dictionary-options') {
            dictionaryFilterQuery = (target.value || '').trim().toLowerCase();
        }
        if (action === 'render-step-media-preview') {
            renderStepMediaPreview();
        }
    });
}

function bindChangeActions() {
    document.querySelectorAll('[data-change-action]').forEach(el => {
        el.addEventListener('change', () => {
            const action = el.getAttribute('data-change-action');
            if (!action) return;
            if (action === 'sync-module-selector') syncModuleSelector();
            if (action === 'sync-lesson-selector') syncLessonSelector();
            if (action === 'load-lesson') loadLesson();
            if (action === 'toggle-step-fields') toggleStepFields();
            if (action === 'toggle-source-mode-fields') toggleSourceModeFields();
        });
    });
}

function bindInputActions() {
    document.querySelectorAll('[data-input-action]').forEach(el => {
        el.addEventListener('input', () => {
            const action = el.getAttribute('data-input-action');
            if (action === 'filter-dictionary-options') {
                dictionaryFilterQuery = (el.value || '').trim().toLowerCase();
            }
            if (action === 'render-step-media-preview') {
                renderStepMediaPreview();
            }
        });
    });
}

function safeNotifyError(error) {
    const message = error?.message ? String(error.message) : String(error);
    if (typeof showToast === 'function') {
        showToast(`\u041E\u0448\u0438\u0431\u043A\u0430 \u0440\u0435\u0434\u0430\u043A\u0442\u043E\u0440\u0430: ${message}`, 'error');
    } else {
        alert(`\u041E\u0448\u0438\u0431\u043A\u0430 \u0440\u0435\u0434\u0430\u043A\u0442\u043E\u0440\u0430: ${message}`);
    }
}

function withButtonLock(buttonEl, handler) {
    if (!buttonEl) {
        return handler();
    }
    if (buttonEl.dataset.pending === '1') {
        return null;
    }
    buttonEl.dataset.pending = '1';
    setBusyState(buttonEl, true);
    const result = handler();
    if (!isPromiseLike(result)) {
        delete buttonEl.dataset.pending;
        setBusyState(buttonEl, false);
        return result;
    }
    return result.finally(() => {
        delete buttonEl.dataset.pending;
        setBusyState(buttonEl, false);
    });
}

async function createCourse() {
    if (typeof openUiInputModal !== 'function') {
        safeNotifyError('\u041E\u043A\u043D\u043E \u0432\u0432\u043E\u0434\u0430 \u043D\u0435 \u0438\u043D\u0438\u0446\u0438\u0430\u043B\u0438\u0437\u0438\u0440\u043E\u0432\u0430\u043D\u043E.');
        return;
    }
    openUiInputModal({
        title: '\u0421\u043E\u0437\u0434\u0430\u0442\u044C \u043A\u0443\u0440\u0441',
        description: '\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043A\u0443\u0440\u0441\u0430 \u043F\u043E\u044F\u0432\u0438\u0442\u0441\u044F \u0432 \u0441\u043F\u0438\u0441\u043A\u0435 \u0438 \u0441\u0442\u0430\u043D\u0435\u0442 \u0434\u043E\u0441\u0442\u0443\u043F\u043D\u043E \u0434\u043B\u044F \u0432\u044B\u0431\u043E\u0440\u0430.',
        label: '\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043A\u0443\u0440\u0441\u0430',
        placeholder: '\u041D\u0430\u043F\u0440\u0438\u043C\u0435\u0440, \u0411\u0430\u0437\u043E\u0432\u044B\u0439 \u043A\u0443\u0440\u0441',
        submitLabel: '\u0421\u043E\u0437\u0434\u0430\u0442\u044C \u043A\u0443\u0440\u0441',
        onSubmit: async (value) => {
            const title = String(value || '').trim();
            if (!title) {
                setUiInputError('\u0412\u0432\u0435\u0434\u0438\u0442\u0435 \u043D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043A\u0443\u0440\u0441\u0430.');
                return;
            }
            try {
                const data = await apiRequest(cmsUrls.addCourse, { title }, '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0441\u043E\u0437\u0434\u0430\u0442\u044C \u043A\u0443\u0440\u0441');
                curriculum.courses = [...(curriculum.courses || []), { ...(data.course || {}), modules: [] }];
                courseTitleMap[data.course.id] = data.course.title;
                appendCourseOption(data.course);
                document.getElementById('courseSelector').value = normalizeId(data.course.id);
                syncModuleSelector();
                syncLessonSelector();
                closeUiInputModal();
                showToast('\u041A\u0443\u0440\u0441 \u0441\u043E\u0437\u0434\u0430\u043D.', 'success');
            } catch (error) {
                setUiInputError(error.message || '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0441\u043E\u0437\u0434\u0430\u0442\u044C \u043A\u0443\u0440\u0441.');
            }
        }
    });
}

async function createModule() {
    const courseId = document.getElementById('courseSelector')?.value || '';
    if (!courseId) {
        showToast('\u0421\u043D\u0430\u0447\u0430\u043B\u0430 \u0432\u044B\u0431\u0435\u0440\u0438\u0442\u0435 \u0438\u043B\u0438 \u0441\u043E\u0437\u0434\u0430\u0439\u0442\u0435 \u043A\u0443\u0440\u0441.', 'error');
        return;
    }
    openUiInputModal({
        title: '\u0421\u043E\u0437\u0434\u0430\u0442\u044C \u043C\u043E\u0434\u0443\u043B\u044C',
        description: '\u041C\u043E\u0434\u0443\u043B\u044C \u0431\u0443\u0434\u0435\u0442 \u0441\u043E\u0437\u0434\u0430\u043D \u0432\u043D\u0443\u0442\u0440\u0438 \u0432\u044B\u0431\u0440\u0430\u043D\u043D\u043E\u0433\u043E \u043A\u0443\u0440\u0441\u0430.',
        label: '\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043C\u043E\u0434\u0443\u043B\u044F',
        placeholder: '\u041D\u0430\u043F\u0440\u0438\u043C\u0435\u0440, \u0412\u0432\u0435\u0434\u0435\u043D\u0438\u0435',
        submitLabel: '\u0421\u043E\u0437\u0434\u0430\u0442\u044C \u043C\u043E\u0434\u0443\u043B\u044C',
        onSubmit: async (value) => {
            const title = String(value || '').trim();
            if (!title) {
                setUiInputError('\u0412\u0432\u0435\u0434\u0438\u0442\u0435 \u043D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043C\u043E\u0434\u0443\u043B\u044F.');
                return;
            }
            try {
                const data = await apiRequest(cmsUrls.addModule, { course_id: courseId, title }, '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0441\u043E\u0437\u0434\u0430\u0442\u044C \u043C\u043E\u0434\u0443\u043B\u044C');
                const course = getSelectedCourse();
                if (course) {
                    course.modules = [...(course.modules || []), { ...(data.module || {}), lesson_ids: [] }];
                }
                moduleTitleMap[data.module.id] = data.module.title;
                syncModuleSelector(normalizeId(data.module.id));
                syncLessonSelector();
                closeUiInputModal();
                showToast('\u041C\u043E\u0434\u0443\u043B\u044C \u0441\u043E\u0437\u0434\u0430\u043D.', 'success');
            } catch (error) {
                setUiInputError(error.message || '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0441\u043E\u0437\u0434\u0430\u0442\u044C \u043C\u043E\u0434\u0443\u043B\u044C.');
            }
        }
    });
}

function openLessonModal() {
    const courseId = document.getElementById('courseSelector')?.value || '';
    const moduleId = document.getElementById('lessonModule')?.value || '';
    if (!courseId) {
        showToast('\u0421\u043D\u0430\u0447\u0430\u043B\u0430 \u0432\u044B\u0431\u0435\u0440\u0438\u0442\u0435 \u043A\u0443\u0440\u0441.', 'error');
        return;
    }
    if (!moduleId) {
        showToast('\u0421\u043D\u0430\u0447\u0430\u043B\u0430 \u0441\u043E\u0437\u0434\u0430\u0439\u0442\u0435 \u043C\u043E\u0434\u0443\u043B\u044C.', 'error');
        return;
    }
    openUiInputModal({
        title: '\u0421\u043E\u0437\u0434\u0430\u0442\u044C \u0443\u0440\u043E\u043A',
        description: '\u041D\u043E\u0432\u044B\u0439 \u0443\u0440\u043E\u043A \u0431\u0443\u0434\u0435\u0442 \u0441\u043E\u0437\u0434\u0430\u043D \u0432\u043D\u0443\u0442\u0440\u0438 \u0432\u044B\u0431\u0440\u0430\u043D\u043D\u043E\u0433\u043E \u043C\u043E\u0434\u0443\u043B\u044F.',
        label: '\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u0443\u0440\u043E\u043A\u0430',
        placeholder: '\u041D\u0430\u043F\u0440\u0438\u043C\u0435\u0440, \u041F\u0435\u0440\u0432\u043E\u0435 \u0437\u043D\u0430\u043A\u043E\u043C\u0441\u0442\u0432\u043E',
        submitLabel: '\u0421\u043E\u0437\u0434\u0430\u0442\u044C \u0443\u0440\u043E\u043A',
        onSubmit: (value) => {
            const title = String(value || '').trim();
            if (!title) {
                setUiInputError('\u0412\u0432\u0435\u0434\u0438\u0442\u0435 \u043D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u0443\u0440\u043E\u043A\u0430.');
                return;
            }
            const selector = document.getElementById('lessonSelector');
            const ids = Array.from(selector.options).map(option => Number(option.value)).filter(Number.isFinite);
            const newLessonId = ids.length ? Math.max(...ids) + 1 : 1;
            selector.querySelector('option[data-temporary="true"]')?.remove();
            appendLessonOption(newLessonId, title, courseId, moduleId, true);
            selector.value = String(newLessonId);
            currentLessonId = newLessonId;
            currentCourseId = courseId;
            currentModuleId = moduleId;
            currentSteps = [];
            document.getElementById('lessonTitle').value = title;
            setLessonEditorVisible(true);
            setPreviewButtonVisible(true);
            syncLessonSelector(newLessonId);
            renderStepsList();
            closeUiInputModal();
        }
    });
}

async function loadLesson() {
    const selector = document.getElementById('lessonSelector');
    currentLessonId = selector.value;
    if (!currentLessonId) {
        setLessonEditorVisible(false);
        setPreviewButtonVisible(false);
        return;
    }
    const lessonUrl = String(cmsUrls.getLessonBase || '').includes('__ID__')
        ? String(cmsUrls.getLessonBase).replace('__ID__', encodeURIComponent(String(currentLessonId)))
        : `${cmsUrls.getLessonBase}/${encodeURIComponent(String(currentLessonId))}`;

    try {
        showToast('\u0417\u0430\u0433\u0440\u0443\u0436\u0430\u0435\u043C \u0443\u0440\u043E\u043A...', 'info');
        const data = await apiRequest(lessonUrl, undefined, '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0437\u0430\u0433\u0440\u0443\u0437\u0438\u0442\u044C \u0443\u0440\u043E\u043A.', 'GET');
        document.getElementById('lessonTitle').value = data.title || '';
        currentCourseId = data.course_id || getDefaultCourseId();
        document.getElementById('courseSelector').value = currentCourseId;
        syncModuleSelector(data.module_id || '');
        currentModuleId = data.module_id || getDefaultModuleId();
        syncLessonSelector(currentLessonId);
        currentSteps = Array.isArray(data.steps) ? data.steps : [];
        renderStepsList();
        setLessonEditorVisible(true);
        setPreviewButtonVisible(true);
        showToast('\u0423\u0440\u043E\u043A \u0437\u0430\u0433\u0440\u0443\u0436\u0435\u043D.', 'success');
    } catch (error) {
        showToast(error.message || '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0437\u0430\u0433\u0440\u0443\u0437\u0438\u0442\u044C \u0443\u0440\u043E\u043A.', 'error');
    }
}

function renderStepsList() {
    const theoryContainer = document.getElementById('theoryStepsList');
    const practiceContainer = document.getElementById('practiceStepsList');
    if (!theoryContainer || !practiceContainer) return;
    theoryContainer.innerHTML = '';
    practiceContainer.innerHTML = '';
    currentSteps.forEach((step, index) => {
        const stepDiv = document.createElement('div');
        const typeLabel = getStepTypeLabel(step);
        stepDiv.className = 'teacher-step-card';
        stepDiv.innerHTML = `<div class="teacher-step-card-main"><div class="teacher-step-card-title">${typeLabel} ${index + 1}</div><div class="teacher-step-card-preview">${renderStepCardPreview(step) || '\u0411\u0435\u0437 \u0441\u043E\u0434\u0435\u0440\u0436\u0438\u043C\u043E\u0433\u043E'}</div></div><div class="teacher-step-card-actions"><button type="button" data-action="edit-step" data-index="${index}" class="teacher-step-btn teacher-step-btn--edit">\u0418\u0437\u043C\u0435\u043D\u0438\u0442\u044C</button><button type="button" data-action="delete-step" data-index="${index}" class="teacher-step-btn teacher-step-btn--delete">\u0423\u0434\u0430\u043B\u0438\u0442\u044C</button></div>`;
        if (step.type === 'theory' || step.type === 'dialog') theoryContainer.appendChild(stepDiv);
        else practiceContainer.appendChild(stepDiv);
    });
    if (!theoryContainer.innerHTML) theoryContainer.innerHTML = '<div class="teacher-muted">\u0422\u0435\u043E\u0440\u0435\u0442\u0438\u0447\u0435\u0441\u043A\u0438\u0439 \u0441\u0435\u0433\u043C\u0435\u043D\u0442 \u043F\u043E\u043A\u0430 \u043F\u0443\u0441\u0442.</div>';
    if (!practiceContainer.innerHTML) practiceContainer.innerHTML = '<div class="teacher-muted">\u041F\u0440\u0430\u043A\u0442\u0438\u0447\u0435\u0441\u043A\u0438\u0439 \u0441\u0435\u0433\u043C\u0435\u043D\u0442 \u043F\u043E\u043A\u0430 \u043F\u0443\u0441\u0442.</div>';
}

function openStepModal() {
    document.getElementById('stepModal').style.display = 'flex';
    focusWysiwygEditor();
    renderStepMediaPreview();
}
function closeStepModal() { document.getElementById('stepModal').style.display = 'none'; }

function toggleStepFields() {
    const setVisible = (el, visible) => {
        if (!el) return;
        el.classList.toggle('is-hidden', !visible);
        el.style.display = visible ? 'block' : 'none';
    };

    const type = document.getElementById('stepType').value;
    const sections = {
        theory: 'theoryFields',
        multiple_choice: 'multipleChoiceFields',
        fill_blanks: 'fillBlanksFields',
        matching: 'matchingFields',
        word_order: 'wordOrderFields',
        audio_pick: 'audioPickFields',
        flashcards: 'flashcardsFields',
    };
    Object.values(sections).forEach(id => {
        setVisible(document.getElementById(id), false);
    });
    const target = document.getElementById(sections[type]);
    setVisible(target, true);
    setVisible(document.getElementById('taskSourceFields'), type !== 'theory');
    toggleSourceModeFields();
}

function toggleSourceModeFields() {
    const setVisible = (el, visible) => {
        if (!el) return;
        el.classList.toggle('is-hidden', !visible);
        el.style.display = visible ? 'block' : 'none';
    };

    const type = document.getElementById('stepType').value;
    const mode = document.getElementById('contentSourceMode').value;
    const supported = ['multiple_choice', 'fill_blanks', 'matching', 'flashcards'].includes(type);
    setVisible(document.getElementById('dictionaryAssistFields'), mode === 'dictionary' && supported);
}

function renderDictionaryOptions() {
    const selector = document.getElementById('dictionaryWordSelector');
    if (!selector) return;
    selector.innerHTML = '';
    let visibleCount = 0;
    teacherDictionary.forEach((item, index) => {
        const haystack = `${item.word || ''} ${item.translation || ''}`.toLowerCase();
        if (dictionaryFilterQuery && !haystack.includes(dictionaryFilterQuery)) return;
        const option = document.createElement('option');
        option.value = String(index);
        option.textContent = `${item.word} - ${item.translation}`;
        selector.appendChild(option);
        visibleCount += 1;
    });
    const meta = document.getElementById('dictionarySearchMeta');
    if (meta) meta.textContent = visibleCount ? `\u041F\u043E\u043A\u0430\u0437\u0430\u043D\u044B \u0441\u043B\u043E\u0432\u0430: ${visibleCount}` : '\u0421\u043B\u043E\u0432\u0430 \u043D\u0435 \u043D\u0430\u0439\u0434\u0435\u043D\u044B';
}

function applyDictionaryPreset() {
    const selected = Array.from(document.getElementById('dictionaryWordSelector')?.selectedOptions || []).map(opt => teacherDictionary[Number(opt.value)]).filter(Boolean);
    const type = document.getElementById('stepType').value;
    if (!selected.length) return;
    if (type === 'multiple_choice') {
        quizOptions = selected.map(item => item.word);
        renderQuizOptions();
    }
    if (type === 'fill_blanks') {
        const options = selected.map(item => item.word);
        document.getElementById('fillBlanksOptions').value = options.join(', ');
        if (!document.getElementById('fillBlanksCorrect').value.trim()) {
            document.getElementById('fillBlanksCorrect').value = options[0] || '';
        }
        if (!document.getElementById('fillBlanksSentence').value.trim()) {
            document.getElementById('fillBlanksSentence').value = '___';
        }
    }
    if (type === 'matching') {
        document.getElementById('matchingLeft').value = selected.map(item => item.word).join('\n');
        document.getElementById('matchingRight').value = selected.map(item => item.translation).join('\n');
    }
    if (type === 'flashcards') {
        const item = selected[0];
        document.getElementById('flashcardsWord').value = item.word || '';
        document.getElementById('flashcardsTranslation').value = item.translation || '';
    }
}

function addTheoryStep() { currentStepMode = 'theory'; currentEditIndex = null; clearStepForm(); syncTypeOptionsByMode(); toggleStepFields(); openStepModal(); }
function addPracticeStep() { currentStepMode = 'practice'; currentEditIndex = null; clearStepForm(); syncTypeOptionsByMode(); toggleStepFields(); openStepModal(); }

function editStep(index) {
    currentEditIndex = index;
    clearStepForm();
    const step = currentSteps[index];
    const mediaPayload = {
        image_url: step.image_url || '',
        audio_url: step.audio_url || '',
        video_url: step.video_url || '',
        video_file_url: step.video_file_url || '',
        ...(step.media || {})
    };
    currentStepMode = (step.type === 'theory' || step.type === 'dialog') ? 'theory' : 'practice';
    syncTypeOptionsByMode();
    const currentType = (step.type === 'theory' || step.type === 'dialog') ? 'theory' : step.task_type;
    document.getElementById('stepType').value = currentType;
    toggleStepFields();
    fillMediaFields({ media: mediaPayload });
    if (step.type === 'theory' || step.type === 'dialog') {
        document.getElementById('theoryTitle').value = step.title || step.name || '';
        setEditorHtml(step.content || step.text || '');
    }
    if (step.task_type === 'multiple_choice') {
        document.getElementById('multipleChoiceQuestion').value = step.question || '';
        quizOptions = Array.isArray(step.options) ? [...step.options] : [];
        document.getElementById('multipleChoiceCorrect').value = step.correct_idx ?? 0;
        renderQuizOptions();
    }
    if (step.task_type === 'fill_blanks') {
        document.getElementById('fillBlanksQuestion').value = step.question || '';
        document.getElementById('fillBlanksSentence').value = step.sentence || '';
        document.getElementById('fillBlanksOptions').value = Array.isArray(step.options) ? step.options.join(', ') : '';
        document.getElementById('fillBlanksCorrect').value = step.correct_answer || '';
    }
    if (step.task_type === 'matching') {
        const left = Array.isArray(step.left) ? step.left.map(item => item.text || item).join('\n') : '';
        const right = Array.isArray(step.right) ? step.right.map(item => item.text || item).join('\n') : '';
        document.getElementById('matchingQuestion').value = step.question || '';
        document.getElementById('matchingLeft').value = left;
        document.getElementById('matchingRight').value = right;
    }
    if (step.task_type === 'word_order') {
        document.getElementById('wordOrderQuestion').value = step.question || '';
        document.getElementById('wordOrderAnswer').value = Array.isArray(step.correct_answer) ? step.correct_answer.join(' ') : (step.correct_answer || '');
        document.getElementById('wordOrderOptions').value = Array.isArray(step.options) ? step.options.join(', ') : '';
    }
    if (step.task_type === 'audio_pick') {
        document.getElementById('audioPickQuestion').value = step.question || '';
        document.getElementById('audioPickOptions').value = Array.isArray(step.options) ? step.options.join(', ') : '';
        document.getElementById('audioPickCorrect').value = step.correct_idx ?? 0;
    }
    if (step.task_type === 'flashcards') {
        document.getElementById('flashcardsQuestion').value = step.question || '';
        document.getElementById('flashcardsWord').value = step.word || '';
        document.getElementById('flashcardsTranslation').value = step.translation || '';
        document.getElementById('flashcardsHint').value = step.hint || '';
    }
}

function renderQuizOptions() {
    const list = document.getElementById('quizOptionsList');
    if (!list) return;
    list.innerHTML = '';
    quizOptions.forEach((option, index) => {
        const row = document.createElement('div');
        row.className = 'teacher-inline-input';
        row.innerHTML = `<input type="text" value="${option}" data-quiz-index="${index}" class="teacher-input"><button type="button" data-action="remove-quiz-option" data-index="${index}" class="btn btn-muted btn-xs">\u0423\u0434\u0430\u043B\u0438\u0442\u044C</button>`;
        list.appendChild(row);
    });
}

function addQuizOption() {
    const input = document.getElementById('newQuizOptionInput');
    const value = String(input?.value || '').trim();
    if (!value) return;
    quizOptions.push(value);
    input.value = '';
    renderQuizOptions();
}

function removeQuizOption(index) {
    quizOptions = quizOptions.filter((_, idx) => idx !== index);
    renderQuizOptions();
}

function saveStep() {
    const type = document.getElementById('stepType').value;
    const media = collectMediaFields();
    const hasMedia = Object.values(media).some(Boolean);
    let step = { type: 'task', task_type: type };
    if (type === 'theory') {
        step = { type: 'theory', title: document.getElementById('theoryTitle').value.trim(), content: getEditorHtml() };
        if (hasMedia) step.media = media;
    }
    if (type === 'multiple_choice') {
        step.question = document.getElementById('multipleChoiceQuestion').value.trim();
        step.options = quizOptions.map(item => item.trim()).filter(Boolean);
        step.correct_idx = Number(document.getElementById('multipleChoiceCorrect').value || 0);
    }
    if (type === 'fill_blanks') {
        step.question = document.getElementById('fillBlanksQuestion').value.trim();
        step.sentence = document.getElementById('fillBlanksSentence').value.trim();
        step.options = parseDelimitedList(document.getElementById('fillBlanksOptions').value);
        step.correct_answer = document.getElementById('fillBlanksCorrect').value.trim();
    }
    if (type === 'matching') {
        step.question = document.getElementById('matchingQuestion').value.trim();
        step.left = parseDelimitedList(document.getElementById('matchingLeft').value);
        step.right = parseDelimitedList(document.getElementById('matchingRight').value);
    }
    if (type === 'word_order') {
        step.question = document.getElementById('wordOrderQuestion').value.trim();
        step.correct_answer = parseWordList(document.getElementById('wordOrderAnswer').value);
        const options = parseDelimitedList(document.getElementById('wordOrderOptions').value);
        step.options = options.length ? options : [...step.correct_answer];
    }
    if (type === 'audio_pick') {
        step.question = document.getElementById('audioPickQuestion').value.trim();
        step.options = parseDelimitedList(document.getElementById('audioPickOptions').value);
        step.correct_idx = Number(document.getElementById('audioPickCorrect').value || 0);
        if (media.audio_url) step.audio_url = media.audio_url;
    }
    if (type === 'flashcards') {
        step.question = document.getElementById('flashcardsQuestion').value.trim();
        step.word = document.getElementById('flashcardsWord').value.trim();
        step.translation = document.getElementById('flashcardsTranslation').value.trim();
        step.hint = document.getElementById('flashcardsHint').value.trim();
        if (media.image_url) step.image_url = media.image_url;
    }
    if (hasMedia && type !== 'theory') {
        step.media = media;
    }
    if (currentEditIndex === null) {
        currentSteps.push(step);
    } else {
        currentSteps[currentEditIndex] = step;
    }
    closeStepModal();
    renderStepsList();
}

async function saveLesson() {
    const title = document.getElementById('lessonTitle').value.trim();
    const courseId = document.getElementById('courseSelector').value;
    const moduleId = document.getElementById('lessonModule').value;
    if (!title) {
        showToast('Введите название урока.', 'error');
        return;
    }
    if (!courseId || !moduleId) {
        showToast('Сначала выберите курс и модуль.', 'error');
        return;
    }
    const previousLessonId = Number(currentLessonId);
    const payload = { id: previousLessonId, title, course_id: Number(courseId), module_id: Number(moduleId), steps: currentSteps };
    try {
        showToast('\u0421\u043E\u0445\u0440\u0430\u043D\u044F\u0435\u043C \u0443\u0440\u043E\u043A...', 'info');
        const data = await apiRequest(cmsUrls.saveLesson, payload, '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0441\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C \u0443\u0440\u043E\u043A');
        const savedLessonId = Number(data?.lesson_id || 0);
        if (savedLessonId > 0) {
            const selector = document.getElementById('lessonSelector');
            const oldOption = selector
                ? Array.from(selector.options).find((option) => Number(option.value || 0) === previousLessonId)
                : null;

            if (oldOption) {
                oldOption.value = String(savedLessonId);
                oldOption.removeAttribute('data-temporary');
                oldOption.dataset.courseId = String(courseId);
                oldOption.dataset.moduleId = String(moduleId);
                oldOption.textContent = `${title} · ${(courseTitleMap[Number(courseId)] || '')} / ${(moduleTitleMap[Number(moduleId)] || '')}`;
            } else if (selector) {
                appendLessonOption(savedLessonId, title, courseId, moduleId, false);
            }

            currentLessonId = String(savedLessonId);
            if (selector) {
                syncLessonSelector(String(savedLessonId));
                selector.value = String(savedLessonId);
            }
        }
        showToast('\u0423\u0440\u043E\u043A \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D.', 'success');
    } catch (error) {
        showToast(error.message || '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0441\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C \u0443\u0440\u043E\u043A.', 'error');
    }
}

function deleteLesson() {
    if (!currentLessonId) return;
    openConfirmModal({
        title: '\u0423\u0434\u0430\u043B\u0438\u0442\u044C \u0443\u0440\u043E\u043A?',
        description: '\u0423\u0440\u043E\u043A \u0431\u0443\u0434\u0435\u0442 \u0443\u0434\u0430\u043B\u0451\u043D \u0431\u0435\u0437 \u0432\u043E\u0437\u043C\u043E\u0436\u043D\u043E\u0441\u0442\u0438 \u0432\u043E\u0441\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F.',
        confirmLabel: '\u0423\u0434\u0430\u043B\u0438\u0442\u044C',
        onConfirm: async () => {
            try {
                await apiRequest(cmsUrls.deleteLesson, { id: Number(currentLessonId) }, '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0443\u0434\u0430\u043B\u0438\u0442\u044C \u0443\u0440\u043E\u043A');
                removeLessonOption(currentLessonId);
                resetLessonEditorState();
                closeConfirmModal();
                showToast('\u0423\u0440\u043E\u043A \u0443\u0434\u0430\u043B\u0451\u043D.', 'success');
            } catch (error) {
                showToast(error.message || '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0443\u0434\u0430\u043B\u0438\u0442\u044C \u0443\u0440\u043E\u043A.', 'error');
            }
        }
    });
}

function previewLesson() {
    const lessonId = Number(currentLessonId || document.getElementById('lessonSelector')?.value || 0);
    if (!Number.isFinite(lessonId) || lessonId < 1) {
        showToast('Сначала выберите урок для предпросмотра.', 'error');
        return;
    }

    const previewParams = new URLSearchParams({
        page: 'lesson_view',
        id: String(lessonId),
        return_page: 'teacher_panel',
        edit_lesson_id: String(lessonId),
    });
    const previewUrl = `${window.location.pathname}?${previewParams.toString()}`;
    window.open(previewUrl, '_blank', 'noopener');
}

function filterTeacherCourses(query) {
    const normalized = String(query || '').trim().toLowerCase();
    const grid = document.getElementById('teacherCoursesGrid');
    if (!grid) return;

    const cards = Array.from(grid.querySelectorAll('[data-course-title]'));
    let visibleCount = 0;

    cards.forEach((card) => {
        const title = String(card.getAttribute('data-course-title') || '').toLowerCase();
        const isVisible = normalized === '' || title.includes(normalized);
        card.hidden = !isVisible;
        if (isVisible) visibleCount += 1;
    });

    const existingMessage = grid.querySelector('[data-empty-filter-result="true"]');
    if (existingMessage) {
        existingMessage.remove();
    }

    if (cards.length > 0 && visibleCount === 0) {
        const message = document.createElement('div');
        message.className = 'teacher-muted';
        message.setAttribute('data-empty-filter-result', 'true');
        message.textContent = 'По этому запросу курсы не найдены.';
        grid.appendChild(message);
    }
}

function openDictionaryModal() {
    document.getElementById('dictionaryModal').style.display = 'flex';
    renderDictionaryOptions();
}
function closeDictionaryModal() { document.getElementById('dictionaryModal').style.display = 'none'; }

async function deleteWord(buttonEl) {
    const entryId = buttonEl.getAttribute('data-entry-id');
    if (!entryId) {
        showToast('Не удалось определить ID слова.', 'error');
        return;
    }
    setBusyState(buttonEl, true);
    try {
        await apiRequest(cmsUrls.deleteWord, { id: Number(entryId) }, '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0443\u0434\u0430\u043B\u0438\u0442\u044C \u0441\u043B\u043E\u0432\u043E');
        buttonEl.closest('tr')?.remove();
        showToast('\u0421\u043B\u043E\u0432\u043E \u0443\u0434\u0430\u043B\u0435\u043D\u043E.', 'success');
    } catch (error) {
        showToast(error.message || '\u041E\u0448\u0438\u0431\u043A\u0430 \u0443\u0434\u0430\u043B\u0435\u043D\u0438\u044F \u0441\u043B\u043E\u0432\u0430.', 'error');
    } finally {
        setBusyState(buttonEl, false);
    }
}

async function submitDictionaryForm(event) {
    event.preventDefault();
    const word = document.getElementById('newWord').value.trim();
    const translation = document.getElementById('newTranslation').value.trim();
    const submitButton = document.querySelector('#addWordForm button[type="submit"]');
    if (!word || !translation) {
        showToast('\u0417\u0430\u043F\u043E\u043B\u043D\u0438\u0442\u0435 \u0441\u043B\u043E\u0432\u043E \u0438 \u043F\u0435\u0440\u0435\u0432\u043E\u0434.', 'error');
        return;
    }
    setBusyState(submitButton, true);
    try {
        showToast('\u0414\u043E\u0431\u0430\u0432\u043B\u044F\u0435\u043C \u0441\u043B\u043E\u0432\u043E...', 'info');
        const data = await apiRequest(cmsUrls.addWord, { word, translation }, '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0434\u043E\u0431\u0430\u0432\u0438\u0442\u044C \u0441\u043B\u043E\u0432\u043E');
        teacherDictionary.push({ id: data.entry.id, word: data.entry.word, translation: data.entry.translation });
        document.getElementById('newWord').value = '';
        document.getElementById('newTranslation').value = '';
        showToast('\u0421\u043B\u043E\u0432\u043E \u0434\u043E\u0431\u0430\u0432\u043B\u0435\u043D\u043E.', 'success');
    } catch (error) {
        showToast(error.message || '\u041E\u0448\u0438\u0431\u043A\u0430 \u0434\u043E\u0431\u0430\u0432\u043B\u0435\u043D\u0438\u044F \u0441\u043B\u043E\u0432\u0430.', 'error');
    } finally {
        setBusyState(submitButton, false);
    }
}

function syncTypeOptionsByMode() {
    const select = document.getElementById('stepType');
    Array.from(select.options).forEach(option => {
        const isTheory = option.value === 'theory';
        option.hidden = currentStepMode === 'theory' ? !isTheory : isTheory;
        option.disabled = currentStepMode === 'theory' ? !isTheory : isTheory;
    });
    if (currentStepMode === 'theory') select.value = 'theory';
    if (currentStepMode === 'practice' && select.value === 'theory') select.value = 'multiple_choice';
}

function updateQuizOptionInput(event) {
    const input = event.target;
    const index = Number(input.getAttribute('data-quiz-index'));
    if (!Number.isFinite(index)) return;
    quizOptions[index] = input.value;
}

actionHandlers['create-course'] = () => createCourse();
actionHandlers['create-module'] = () => createModule();
actionHandlers['open-lesson-modal'] = () => openLessonModal();
actionHandlers['load-lesson'] = (el) => withButtonLock(el, () => loadLesson());
actionHandlers['add-theory-step'] = () => addTheoryStep();
actionHandlers['add-practice-step'] = () => addPracticeStep();
actionHandlers['close-step-modal'] = () => closeStepModal();
actionHandlers['save-step'] = () => saveStep();
actionHandlers['save-lesson'] = (el) => withButtonLock(el, () => saveLesson());
actionHandlers['delete-lesson'] = (el) => withButtonLock(el, () => deleteLesson());
actionHandlers['preview-lesson'] = () => previewLesson();
actionHandlers['open-dictionary-modal'] = () => openDictionaryModal();
actionHandlers['close-dictionary-modal'] = () => closeDictionaryModal();
actionHandlers['delete-word'] = (el) => withButtonLock(el, () => deleteWord(el));
actionHandlers['add-quiz-option'] = () => addQuizOption();
actionHandlers['remove-quiz-option'] = (el) => removeQuizOption(Number(el.getAttribute('data-index')));
actionHandlers['edit-step'] = (el) => editStep(Number(el.getAttribute('data-index')));
actionHandlers['delete-step'] = (el) => { const idx = Number(el.getAttribute('data-index')); currentSteps.splice(idx,1); renderStepsList(); };
actionHandlers['apply-text-format'] = (el) => applyTextFormat(el.getAttribute('data-value'));
actionHandlers['apply-block-format'] = (el) => applyBlockFormat(el.getAttribute('data-value'));
actionHandlers['insert-editor-link'] = () => insertEditorLink();
actionHandlers['clear-editor-formatting'] = () => clearEditorFormatting();
actionHandlers['apply-dictionary-preset'] = () => applyDictionaryPreset();
actionHandlers['close-ui-input-modal'] = () => closeUiInputModal();
actionHandlers['close-confirm-modal'] = () => closeConfirmModal();

function initTeacherActions() {
    try {
        document.body.classList.add('js-enhanced');
        if (window.teacherIndesDataParseError) {
            safeNotifyError(UI_MESSAGES.brokenPayload);
            return;
        }
        bindGlobalListeners();
        bindMediaUploadInputs();
        setPreviewButtonVisible(false);
        document.getElementById('uiInputSubmit')?.addEventListener('click', submitUiInputModal);
        document.getElementById('confirmSubmit')?.addEventListener('click', submitConfirmModal);
        document.getElementById('uiInputField')?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitUiInputModal();
            }
        });
        renderStageSummaries();
        renderDictionaryOptions();
        const initialLessonId = new URLSearchParams(window.location.search).get('edit_lesson_id');
        if (initialLessonId) {
            const lessonSelector = document.getElementById('lessonSelector');
            const option = lessonSelector
                ? Array.from(lessonSelector.options).find((item) => item.value === String(initialLessonId))
                : null;
            if (lessonSelector && option) {
                lessonSelector.value = option.value;
                syncModuleSelector(option.dataset.moduleId || '');
                lessonSelector.value = option.value;
                loadLesson();
            }
        }
        document.getElementById('addWordForm')?.addEventListener('submit', submitDictionaryForm);
        document.getElementById('quizOptionsList')?.addEventListener('input', (event) => {
            if (event.target?.matches('[data-quiz-index]')) updateQuizOptionInput(event);
        });
        window.teacherIndesActions = {
            createCourse,
            createModule,
            openLessonModal,
            loadLesson,
            saveLesson,
            deleteLesson,
        };
        window.__teacherEditorReady = true;
    } catch (error) {
        safeNotifyError(error);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTeacherActions);
} else {
    initTeacherActions();
}
