(function () {
    function parseJsonScript(id) {
        var el = document.getElementById(id);
        if (!el) return null;
        try {
            return JSON.parse(el.textContent || '{}');
        } catch (error) {
            console.error('Failed to parse JSON script:', id, error);
            if (id === 'teacher-indes-data') {
                window.teacherIndesDataParseError = true;
            }
            return null;
        }
    }

    var teacherData = parseJsonScript('teacher-indes-data');
    window.teacherIndesData = teacherData || {};

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-progress-fill').forEach(function (el) {
            var value = Number(el.getAttribute('data-progress') || 0);
            var clamped = Math.max(0, Math.min(100, value));
            el.style.width = clamped + '%';
        });

        var lessonData = parseJsonScript('teacher-lesson-view-data');
        if (!lessonData || !window.LessonEngine) return;

        document.body.classList.add('lesson-active');
        new LessonEngine(lessonData);
    });
})();
