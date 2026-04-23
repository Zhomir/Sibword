let currentLessonId = null;
let currentSteps = [];
let quizOptions = [];
let currentEditIndex = null;
let currentStepMode = 'theory';
let currentCourseId = null;
let currentModuleId = null;
let confirmSubmitHandler = null;
let toastTimer = null;
const teacherIndesData = window.teacherIndesData || {};
const teacherDictionary = Array.isArray(teacherIndesData.dictionary) ? teacherIndesData.dictionary : [];
const curriculum = teacherIndesData.curriculum || { courses: [] };
const cmsUrls = teacherIndesData.cmsUrls || {};
let courseTitleMap = teacherIndesData.courseTitleMap || {};
let moduleTitleMap = teacherIndesData.moduleTitleMap || {};
let uiInputSubmitHandler = null;

if (!Object.keys(courseTitleMap).length) {
    courseTitleMap = Object.fromEntries(((curriculum.courses || [])).map(course => [course.id, course.title]));
}
if (!Object.keys(moduleTitleMap).length) {
    moduleTitleMap = Object.fromEntries(((curriculum.courses || [])).flatMap(course => (course.modules || []).map(module => [module.id, module.title])));
}

function openUiInputModal({ title, description = '', label = '\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435', value = '', placeholder = '', submitLabel = '\u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C', onSubmit }) {
    const modal = document.getElementById('uiInputModal');
    const titleEl = document.getElementById('uiInputTitle');
    const descriptionEl = document.getElementById('uiInputDescription');
    const labelEl = document.getElementById('uiInputLabel');
    const inputEl = document.getElementById('uiInputField');
    const submitEl = document.getElementById('uiInputSubmit');
    const errorEl = document.getElementById('uiInputError');
    if (!modal || !inputEl || !submitEl || !errorEl) return;

    titleEl.textContent = title;
    descriptionEl.textContent = description;
    descriptionEl.style.display = description ? '' : 'none';
    labelEl.textContent = label;
    inputEl.value = value;
    inputEl.placeholder = placeholder;
    submitEl.textContent = submitLabel;
    errorEl.textContent = '';
    errorEl.style.display = 'none';
    uiInputSubmitHandler = onSubmit;
    modal.style.display = 'flex';

    setTimeout(() => {
        inputEl.focus();
        inputEl.select();
    }, 0);
}

function closeUiInputModal() {
    const modal = document.getElementById('uiInputModal');
    const errorEl = document.getElementById('uiInputError');
    if (modal) modal.style.display = 'none';
    if (errorEl) {
        errorEl.textContent = '';
        errorEl.style.display = 'none';
    }
    uiInputSubmitHandler = null;
}

function showToast(message, type = 'info') {
    const toast = document.getElementById('teacherToast');
    if (!toast) return;
    toast.textContent = message;
    toast.className = `teacher-toast is-${type}`;
    toast.style.display = 'block';
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.style.display = 'none';
    }, 2800);
}

function setUiInputError(message = '') {
    const errorEl = document.getElementById('uiInputError');
    if (!errorEl) return;
    errorEl.textContent = message;
    errorEl.style.display = message ? 'block' : 'none';
}

function submitUiInputModal() {
    const inputEl = document.getElementById('uiInputField');
    const submitEl = document.getElementById('uiInputSubmit');
    if (!uiInputSubmitHandler || !inputEl) return;
    try {
        const result = uiInputSubmitHandler(inputEl.value);
        if (result && typeof result.then === 'function' && submitEl) {
            submitEl.disabled = true;
            submitEl.setAttribute('aria-busy', 'true');
            result.finally(() => {
                submitEl.disabled = false;
                submitEl.removeAttribute('aria-busy');
            });
        }
    } catch (error) {
        if (submitEl) {
            submitEl.disabled = false;
            submitEl.removeAttribute('aria-busy');
        }
        throw error;
    }
}

function openConfirmModal({ title = '\u041F\u043E\u0434\u0442\u0432\u0435\u0440\u0436\u0434\u0435\u043D\u0438\u0435', description = '', confirmLabel = '\u041F\u043E\u0434\u0442\u0432\u0435\u0440\u0434\u0438\u0442\u044C', onConfirm }) {
    const modal = document.getElementById('confirmModal');
    const titleEl = document.getElementById('confirmTitle');
    const descriptionEl = document.getElementById('confirmDescription');
    const submitEl = document.getElementById('confirmSubmit');
    if (!modal || !titleEl || !descriptionEl || !submitEl) return;
    titleEl.textContent = title;
    descriptionEl.textContent = description;
    submitEl.textContent = confirmLabel;
    confirmSubmitHandler = onConfirm;
    modal.style.display = 'flex';
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    if (modal) modal.style.display = 'none';
    confirmSubmitHandler = null;
}

function submitConfirmModal() {
    const submitEl = document.getElementById('confirmSubmit');
    if (!confirmSubmitHandler) return;
    try {
        const result = confirmSubmitHandler();
        if (result && typeof result.then === 'function' && submitEl) {
            submitEl.disabled = true;
            submitEl.setAttribute('aria-busy', 'true');
            result.finally(() => {
                submitEl.disabled = false;
                submitEl.removeAttribute('aria-busy');
            });
        }
    } catch (error) {
        if (submitEl) {
            submitEl.disabled = false;
            submitEl.removeAttribute('aria-busy');
        }
        throw error;
    }
}

function getWysiwygEditor() { return document.getElementById('wysiwygEditor'); }
function focusWysiwygEditor() { const editor = getWysiwygEditor(); if (editor) editor.focus(); }
function applyTextFormat(command) { focusWysiwygEditor(); document.execCommand(command, false, null); }
function applyBlockFormat(tagName) { focusWysiwygEditor(); document.execCommand('formatBlock', false, tagName); }
function insertEditorLink() {
    openUiInputModal({
        title: '\u0412\u0441\u0442\u0430\u0432\u0438\u0442\u044C \u0441\u0441\u044B\u043B\u043A\u0443',
        label: '\u0421\u0441\u044B\u043B\u043A\u0430',
        placeholder: 'https://example.com',
        submitLabel: '\u0412\u0441\u0442\u0430\u0432\u0438\u0442\u044C',
        onSubmit: (value) => {
            const url = String(value || '').trim();
            if (!url) {
                setUiInputError('\u0412\u0432\u0435\u0434\u0438\u0442\u0435 \u0441\u0441\u044B\u043B\u043A\u0443.');
                return;
            }
            focusWysiwygEditor();
            document.execCommand('createLink', false, url);
            closeUiInputModal();
        }
    });
}
function clearEditorFormatting() { focusWysiwygEditor(); document.execCommand('removeFormat', false, null); }
function setEditorHtml(html) { const editor = getWysiwygEditor(); if (editor) editor.innerHTML = (html || '').trim() || '<p><br></p>'; }
function getEditorHtml() { const editor = getWysiwygEditor(); if (!editor) return ''; const html = editor.innerHTML.trim(); return html === '<p><br></p>' ? '' : html; }
function stripHtml(html) { const temp = document.createElement('div'); temp.innerHTML = html || ''; return (temp.textContent || temp.innerText || '').trim(); }
function escapeHtml(value) { return (value || '').replace(/[&<>'"]+/g, function (char) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char] || char; }); }
function parseCommaList(value) { return (value || '').split(',').map(item => item.trim()).filter(Boolean); }
function parseLineList(value) { return (value || '').split(/\r?\n/).map(item => item.trim()).filter(Boolean); }
function normalizeMedia(step) { const media = step && step.media ? step.media : {}; return { image_url: (media.image_url || '').trim(), audio_url: (media.audio_url || '').trim(), video_url: (media.video_url || '').trim(), video_file_url: (media.video_file_url || '').trim() }; }
function collectMediaFields() { return { image_url: document.getElementById('stepImageUrl').value.trim(), audio_url: document.getElementById('stepAudioUrl').value.trim(), video_file_url: document.getElementById('stepVideoFileUrl').value.trim(), video_url: document.getElementById('stepVideoUrl').value.trim() }; }
function renderTextStepPreview(rawHtml) { if (!rawHtml) return '\u041F\u0443\u0441\u0442\u043E\u0439 \u0442\u0435\u043A\u0441\u0442\u043E\u0432\u044B\u0439 \u0431\u043B\u043E\u043A'; const plain = stripHtml(rawHtml); return plain.length > 70 ? `${plain.substring(0, 70)}...` : plain; }
function getStepTypeLabel(step) {
    if (step.type === 'theory' || step.type === 'dialog') return '\u0422\u0435\u043E\u0440\u0438\u044F';
    const map = {
        multiple_choice: '\u0422\u0435\u0441\u0442',
        fill_blanks: '\u041F\u0440\u043E\u043F\u0443\u0441\u043A\u0438',
        matching: '\u0421\u043E\u043F\u043E\u0441\u0442\u0430\u0432\u043B\u0435\u043D\u0438\u0435',
        word_order: '\u0421\u0431\u043E\u0440\u043A\u0430 \u0444\u0440\u0430\u0437\u044B',
        audio_pick: '\u0410\u0443\u0434\u0438\u043E',
        flashcards: '\u0424\u043B\u044D\u0448-\u043A\u0430\u0440\u0442\u044B',
    };
    return map[step.task_type] || '\u0417\u0430\u0434\u0430\u043D\u0438\u0435';
}
function renderStepCardPreview(step) {
    const media = normalizeMedia(step);
    const mediaCount = [media.image_url, media.audio_url, media.video_url, media.video_file_url].filter(Boolean).length;
    let base = '';
    if (step.type === 'theory' || step.type === 'dialog') base = `${step.title || '\u0422\u0435\u043E\u0440\u0438\u044F'}: ${renderTextStepPreview(step.content || step.text || '')}`;
    if (step.type === 'task' && step.task_type === 'flashcards') base = `${step.word || ''} - ${step.translation || ''}`;
    if (step.type === 'task' && !base) base = step.question || '\u0411\u0435\u0437 \u0432\u043E\u043F\u0440\u043E\u0441\u0430';
    return mediaCount ? `${base} | \u041C\u0435\u0434\u0438\u0430: ${mediaCount}` : base;
}
function setUploadStatus(message, isError = false) { const el = document.getElementById('mediaUploadStatus'); if (!el) return; el.textContent = message || ''; el.style.color = isError ? '#fca5a5' : '#93c5fd'; }
function setFileMeta(id, text) { const el = document.getElementById(id); if (el) el.textContent = text; }

function buildMediaPreviewHtml(media) {
    const items = [];
    if (media.image_url) items.push(`<div style="margin-bottom:12px;"><div style="font-size:0.8rem;color:#94a3b8;margin-bottom:6px;">\u0418\u0437\u043E\u0431\u0440\u0430\u0436\u0435\u043D\u0438\u0435</div><img src="${escapeHtml(media.image_url)}" alt="" style="display:block;max-width:100%;max-height:180px;border-radius:10px;border:1px solid #334155;object-fit:cover;"></div>`);
    if (media.audio_url) items.push(`<div style="margin-bottom:12px;"><div style="font-size:0.8rem;color:#94a3b8;margin-bottom:6px;">\u0410\u0443\u0434\u0438\u043E</div><audio controls preload="none" style="width:100%;"><source src="${escapeHtml(media.audio_url)}"></audio></div>`);
    if (media.video_file_url) items.push(`<div style="margin-bottom:12px;"><div style="font-size:0.8rem;color:#94a3b8;margin-bottom:6px;">\u0412\u0438\u0434\u0435\u043E\u0444\u0430\u0439\u043B</div><video controls preload="metadata" style="display:block;max-width:100%;max-height:220px;border-radius:10px;border:1px solid #334155;background:#020617;"><source src="${escapeHtml(media.video_file_url)}"></video></div>`);
    if (media.video_url) items.push(`<div><div style="font-size:0.8rem;color:#94a3b8;margin-bottom:6px;">\u0421\u0441\u044B\u043B\u043A\u0430 \u043D\u0430 \u0432\u0438\u0434\u0435\u043E</div><a href="${escapeHtml(media.video_url)}" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:8px;color:#93c5fd;text-decoration:none;padding:10px 14px;border:1px solid #334155;border-radius:10px;background:#111827;">\u041E\u0442\u043A\u0440\u044B\u0442\u044C \u0432\u0438\u0434\u0435\u043E</a></div>`);
    return items.length ? items.join('') : '<div style="color:#94a3b8;font-size:0.9rem;">\u041D\u0435\u0442 \u043C\u0435\u0434\u0438\u0430 \u0434\u043B\u044F \u043F\u0440\u0435\u0434\u043F\u0440\u043E\u0441\u043C\u043E\u0442\u0440\u0430</div>';
}

function buildStepMediaHtml(media) {
    const normalized = normalizeMedia({ media });
    const items = [];
    if (normalized.image_url) items.push(`<img src="${escapeHtml(normalized.image_url)}" alt="" style="display:block;max-width:100%;max-height:260px;border-radius:12px;margin-bottom:12px;object-fit:cover;">`);
    if (normalized.audio_url) items.push(`<audio controls preload="none" style="display:block;width:100%;margin-bottom:12px;"><source src="${escapeHtml(normalized.audio_url)}"></audio>`);
    if (normalized.video_file_url) items.push(`<video controls preload="metadata" style="display:block;max-width:100%;max-height:320px;border-radius:12px;margin-bottom:12px;background:#020617;"><source src="${escapeHtml(normalized.video_file_url)}"></video>`);
    if (normalized.video_url) items.push(`<a href="${escapeHtml(normalized.video_url)}" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:8px;color:#93c5fd;text-decoration:none;padding:10px 14px;border:1px solid #334155;border-radius:10px;background:#111827;">\u041E\u0442\u043A\u0440\u044B\u0442\u044C \u0432\u0438\u0434\u0435\u043E</a>`);
    return items.join('');
}

function renderStepMediaPreview() { const preview = document.getElementById('stepMediaPreview'); if (preview) preview.innerHTML = buildMediaPreviewHtml(collectMediaFields()); }
function resetMediaFields() {
    ['stepImageUrl', 'stepAudioUrl', 'stepVideoFileUrl', 'stepVideoUrl'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    ['stepImageFile', 'stepAudioFile', 'stepVideoFile'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    setFileMeta('stepImageMeta', '\u0424\u0430\u0439\u043B \u043D\u0435 \u0437\u0430\u0433\u0440\u0443\u0436\u0435\u043D');
    setFileMeta('stepAudioMeta', '\u0424\u0430\u0439\u043B \u043D\u0435 \u0437\u0430\u0433\u0440\u0443\u0436\u0435\u043D');
    setFileMeta('stepVideoFileMeta', '\u0424\u0430\u0439\u043B \u043D\u0435 \u0437\u0430\u0433\u0440\u0443\u0436\u0435\u043D');
    setUploadStatus('');
    renderStepMediaPreview();
}
function fillMediaFields(step) {
    const media = normalizeMedia(step);
    document.getElementById('stepImageUrl').value = media.image_url;
    document.getElementById('stepAudioUrl').value = media.audio_url;
    document.getElementById('stepVideoFileUrl').value = media.video_file_url;
    document.getElementById('stepVideoUrl').value = media.video_url;
    setFileMeta('stepImageMeta', media.image_url ? '\u0424\u0430\u0439\u043B \u043F\u0440\u0438\u043A\u0440\u0435\u043F\u043B\u0435\u043D' : '\u0424\u0430\u0439\u043B \u043D\u0435 \u0437\u0430\u0433\u0440\u0443\u0436\u0435\u043D');
    setFileMeta('stepAudioMeta', media.audio_url ? '\u0424\u0430\u0439\u043B \u043F\u0440\u0438\u043A\u0440\u0435\u043F\u043B\u0435\u043D' : '\u0424\u0430\u0439\u043B \u043D\u0435 \u0437\u0430\u0433\u0440\u0443\u0436\u0435\u043D');
    setFileMeta('stepVideoFileMeta', media.video_file_url ? '\u0424\u0430\u0439\u043B \u043F\u0440\u0438\u043A\u0440\u0435\u043F\u043B\u0435\u043D' : '\u0424\u0430\u0439\u043B \u043D\u0435 \u0437\u0430\u0433\u0440\u0443\u0436\u0435\u043D');
    setUploadStatus('');
    renderStepMediaPreview();
}
async function uploadMediaFile(kind, file, hiddenInputId, metaId) {
    if (!file) return;
    if (!cmsUrls.uploadMedia) {
        setUploadStatus('Адрес загрузки медиа не настроен', true);
        return;
    }
    const formData = new FormData();
    formData.append('kind', kind);
    formData.append('file', file);
    setUploadStatus(`\u0417\u0430\u0433\u0440\u0443\u0437\u043A\u0430 \u0444\u0430\u0439\u043B\u0430: ${file.name}`);
    try {
        const response = await fetch(cmsUrls.uploadMedia, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        });
        const text = await response.text();
        let data = {};
        try { data = JSON.parse(text); } catch (error) { data = { message: text || '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0440\u0430\u0441\u043F\u043E\u0437\u043D\u0430\u0442\u044C \u043E\u0442\u0432\u0435\u0442 \u0441\u0435\u0440\u0432\u0435\u0440\u0430' }; }
        if (!response.ok || !data.success) {
            setUploadStatus(data.message || '\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0437\u0430\u0433\u0440\u0443\u0437\u0438\u0442\u044C \u0444\u0430\u0439\u043B', true);
            return;
        }
        document.getElementById(hiddenInputId).value = data.url || '';
        setFileMeta(metaId, `\u0424\u0430\u0439\u043B \u0437\u0430\u0433\u0440\u0443\u0436\u0435\u043D: ${data.name || file.name}`);
        setUploadStatus('\u0424\u0430\u0439\u043B \u0443\u0441\u043F\u0435\u0448\u043D\u043E \u0437\u0430\u0433\u0440\u0443\u0436\u0435\u043D');
        renderStepMediaPreview();
    } catch (error) {
        setUploadStatus('\u041E\u0448\u0438\u0431\u043A\u0430 \u0437\u0430\u0433\u0440\u0443\u0437\u043A\u0438 \u0444\u0430\u0439\u043B\u0430', true);
    }
}
function bindMediaUploadInputs() {
    document.getElementById('stepImageFile')?.addEventListener('change', (event) => uploadMediaFile('image', event.target.files[0], 'stepImageUrl', 'stepImageMeta'));
    document.getElementById('stepAudioFile')?.addEventListener('change', (event) => uploadMediaFile('audio', event.target.files[0], 'stepAudioUrl', 'stepAudioMeta'));
    document.getElementById('stepVideoFile')?.addEventListener('change', (event) => uploadMediaFile('video', event.target.files[0], 'stepVideoFileUrl', 'stepVideoFileMeta'));
}
function clearStepForm() {
    setEditorHtml('');
    ['theoryTitle', 'multipleChoiceQuestion', 'fillBlanksQuestion', 'fillBlanksSentence', 'fillBlanksOptions', 'fillBlanksCorrect', 'matchingQuestion', 'matchingLeft', 'matchingRight', 'wordOrderQuestion', 'wordOrderAnswer', 'wordOrderOptions', 'audioPickQuestion', 'audioPickOptions', 'flashcardsQuestion', 'flashcardsWord', 'flashcardsTranslation', 'flashcardsHint'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('multipleChoiceCorrect').value = 0;
    document.getElementById('audioPickCorrect').value = 0;
    document.getElementById('contentSourceMode').value = 'manual';
    quizOptions = [];
    renderQuizOptions();
    resetMediaFields();
}
function normalizeId(value) {
    if (value === null || value === undefined) return '';
    return String(value);
}
function getSelectedCourse() {
    const courseId = normalizeId(document.getElementById('courseSelector')?.value || '');
    return (curriculum.courses || []).find(course => normalizeId(course.id) === courseId) || null;
}
function getSelectedModule() {
    const moduleId = normalizeId(document.getElementById('lessonModule')?.value || '');
    const course = getSelectedCourse();
    return (course?.modules || []).find(module => normalizeId(module.id) === moduleId) || null;
}
function renderStageSummaries() {
    const course = getSelectedCourse();
    const module = getSelectedModule();
    const courseSummary = document.getElementById('courseStageSummary');
    const moduleSummary = document.getElementById('moduleStageSummary');
    const lessonSummary = document.getElementById('lessonStageSummary');
    const moduleStage = document.getElementById('moduleStage');
    const lessonStage = document.getElementById('lessonStage');

    if (courseSummary) {
        courseSummary.textContent = course
            ? `\u041E\u0442\u043A\u0440\u044B\u0442 \u043A\u0443\u0440\u0441: ${course.title}`
            : '\u0421\u043D\u0430\u0447\u0430\u043B\u0430 \u0441\u043E\u0437\u0434\u0430\u0439\u0442\u0435 \u043A\u0443\u0440\u0441 \u0438\u043B\u0438 \u0432\u044B\u0431\u0435\u0440\u0438\u0442\u0435 \u0443\u0436\u0435 \u0441\u0443\u0449\u0435\u0441\u0442\u0432\u0443\u044E\u0449\u0438\u0439.';
    }

    if (moduleStage) {
        moduleStage.style.display = course ? 'block' : 'none';
    }

    if (moduleSummary) {
        moduleSummary.textContent = !course
            ? '\u041C\u043E\u0434\u0443\u043B\u0438 \u0441\u0442\u0430\u043D\u0443\u0442 \u0434\u043E\u0441\u0442\u0443\u043F\u043D\u044B \u043F\u043E\u0441\u043B\u0435 \u0432\u044B\u0431\u043E\u0440\u0430 \u043A\u0443\u0440\u0441\u0430.'
            : module
                ? `\u041E\u0442\u043A\u0440\u044B\u0442 \u043C\u043E\u0434\u0443\u043B\u044C: ${module.title}`
                : '\u0421\u043E\u0437\u0434\u0430\u0439\u0442\u0435 \u043C\u043E\u0434\u0443\u043B\u044C \u0432 \u044D\u0442\u043E\u043C \u043A\u0443\u0440\u0441\u0435 \u0438\u043B\u0438 \u0432\u044B\u0431\u0435\u0440\u0438\u0442\u0435 \u0443\u0436\u0435 \u0441\u0443\u0449\u0435\u0441\u0442\u0432\u0443\u044E\u0449\u0438\u0439.';
    }

    if (lessonStage) {
        lessonStage.style.display = module ? 'block' : 'none';
    }

    if (lessonSummary) {
        const lessonSelector = document.getElementById('lessonSelector');
        const visibleLessons = lessonSelector
            ? Array.from(lessonSelector.options).filter(option => option.value && option.hidden !== true).length
            : 0;
        lessonSummary.textContent = !module
            ? '\u0423\u0440\u043E\u043A\u0438 \u0441\u0442\u0430\u043D\u0443\u0442 \u0434\u043E\u0441\u0442\u0443\u043F\u043D\u044B \u043F\u043E\u0441\u043B\u0435 \u0432\u044B\u0431\u043E\u0440\u0430 \u043C\u043E\u0434\u0443\u043B\u044F.'
            : visibleLessons
                ? `\u0412 \u044D\u0442\u043E\u043C \u043C\u043E\u0434\u0443\u043B\u0435 \u0434\u043E\u0441\u0442\u0443\u043F\u043D\u043E \u0443\u0440\u043E\u043A\u043E\u0432: ${visibleLessons}`
                : '\u0412 \u044D\u0442\u043E\u043C \u043C\u043E\u0434\u0443\u043B\u0435 \u043F\u043E\u043A\u0430 \u043D\u0435\u0442 \u0443\u0440\u043E\u043A\u043E\u0432. \u041C\u043E\u0436\u043D\u043E \u0441\u043E\u0437\u0434\u0430\u0442\u044C \u043F\u0435\u0440\u0432\u044B\u0439.';
    }
}
function syncModuleSelector(preferredModuleId = '') {
    const moduleSelect = document.getElementById('lessonModule');
    const course = getSelectedCourse();
    if (!moduleSelect) return;
    moduleSelect.innerHTML = '';

    if (!course) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = '\u0421\u043D\u0430\u0447\u0430\u043B\u0430 \u0432\u044B\u0431\u0435\u0440\u0438\u0442\u0435 \u043A\u0443\u0440\u0441';
        moduleSelect.appendChild(option);
        currentCourseId = null;
        currentModuleId = null;
        syncLessonSelector();
        renderStageSummaries();
        return;
    }

    currentCourseId = course.id;
    const modules = course.modules || [];
    if (!modules.length) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = '\u0421\u043D\u0430\u0447\u0430\u043B\u0430 \u0441\u043E\u0437\u0434\u0430\u0439\u0442\u0435 \u043C\u043E\u0434\u0443\u043B\u044C';
        moduleSelect.appendChild(option);
        currentModuleId = null;
        syncLessonSelector();
        renderStageSummaries();
        return;
    }

    modules.forEach(module => {
        const option = document.createElement('option');
        option.value = normalizeId(module.id);
        option.textContent = module.title;
        moduleSelect.appendChild(option);
    });

    const normalizedPreferredModuleId = normalizeId(preferredModuleId);
    moduleSelect.value = normalizedPreferredModuleId && modules.some(module => normalizeId(module.id) === normalizedPreferredModuleId)
        ? normalizedPreferredModuleId
        : normalizeId(modules[0].id);
    currentModuleId = moduleSelect.value;
    syncLessonSelector();
    renderStageSummaries();
}
function syncLessonSelector(preferredLessonId = '') {
    const lessonSelector = document.getElementById('lessonSelector');
    const courseId = document.getElementById('courseSelector')?.value || '';
    const moduleId = document.getElementById('lessonModule')?.value || '';
    if (!lessonSelector) return;

    lessonSelector.value = '';
    Array.from(lessonSelector.options).forEach(option => {
        if (!option.value) {
            option.hidden = false;
            return;
        }
        const matches = option.dataset.courseId === courseId && option.dataset.moduleId === moduleId;
        option.hidden = !matches;
    });

    if (preferredLessonId) {
        const preferred = Array.from(lessonSelector.options).find(option => option.value === String(preferredLessonId) && option.hidden !== true);
        if (preferred) lessonSelector.value = preferred.value;
    }
    renderStageSummaries();
}
function getDefaultModuleId() {
    const moduleSelect = document.getElementById('lessonModule');
    return moduleSelect?.options?.[0]?.value || '';
}
function getDefaultCourseId() {
    const courseSelect = document.getElementById('courseSelector');
    return courseSelect?.options?.[1]?.value || courseSelect?.options?.[0]?.value || '';
}
function appendCourseOption(course) {
    const courseSelector = document.getElementById('courseSelector');
    if (!courseSelector || !course?.id) return;
    const option = document.createElement('option');
    option.value = normalizeId(course.id);
    option.textContent = course.title;
    courseSelector.appendChild(option);
}
function appendLessonOption(lessonId, title, courseId, moduleId, isTemporary = false) {
    const selector = document.getElementById('lessonSelector');
    if (!selector) return null;
    if (selector.querySelector(`option[value="${lessonId}"]`)) {
        return selector.querySelector(`option[value="${lessonId}"]`);
    }
    const option = document.createElement('option');
    option.value = String(lessonId);
    option.textContent = `${title} \u00B7 ${courseTitleMap[courseId] || '\u0411\u0435\u0437 \u043A\u0443\u0440\u0441\u0430'} / ${moduleTitleMap[moduleId] || '\u0411\u0435\u0437 \u043C\u043E\u0434\u0443\u043B\u044F'}`;
    option.dataset.courseId = courseId;
    option.dataset.moduleId = moduleId;
    if (isTemporary) option.dataset.temporary = 'true';
    selector.appendChild(option);
    return option;
}
function removeLessonOption(lessonId) {
    document.querySelector(`#lessonSelector option[value="${lessonId}"]`)?.remove();
}
function resetLessonEditorState() {
    currentLessonId = null;
    currentSteps = [];
    currentEditIndex = null;
    document.getElementById('lessonSelector').value = '';
    document.getElementById('lessonTitle').value = '';
    document.getElementById('lessonEditor')?.classList.add('is-hidden');
    renderStepsList();
    renderStageSummaries();
}

