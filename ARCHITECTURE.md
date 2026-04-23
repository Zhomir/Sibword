# MVPSibword Architecture (Anti-Spaghetti Baseline)

## Current Layering
- `routes/web.php` and `routes/api.php`: HTTP entrypoints and role-based access.
- `app/Http/Controllers/*`: request orchestration and validation.
- `app/Services/*` (if present) and helper classes: domain logic.
- `resources/views/*`: Blade UI templates.
- `public/js/*` and `public/css/*`: frontend behavior and styles.
- `storage/app/*`: JSON-backed runtime content (curriculum, lessons, dictionary), used instead of relational storage for key learning entities.

## Rules to Prevent Spaghetti
- Keep controllers thin: no large data mutation blocks directly in controller actions.
- Move reusable business logic into service classes (`app/Services`).
- Split large Blade files by page/feature. Target: one file = one responsibility.
- Keep JS feature-based (`teacher-indes.*`, `lesson-engine.js`) and avoid cross-file globals except a single bootstrap payload.
- Pass server data to JS only via explicit JSON payload script tags.
- Avoid duplicate route+action flows that do the same mutation in different endpoints.

## Completed in This Step
- Split `resources/views/teacher/indes.blade.php` into page partials:
  - `resources/views/teacher/pages/student-dashboard.blade.php`
  - `resources/views/teacher/pages/teacher-courses.blade.php`
  - `resources/views/teacher/pages/teacher-panel.blade.php`
  - `resources/views/teacher/pages/lesson-view.blade.php`
- Moved scripts branch logic into `resources/views/teacher/partials/page-scripts.blade.php`.
- Reduced `indes.blade.php` to shell + page dispatch only.

## Next Refactor Queue
1. Introduce `CurriculumService` and `LessonEditorService` to centralize JSON read/write operations.
2. Add FormRequest validation for teacher editor actions.
3. Extract repeated text/status formatting helpers (lesson/module counters) into view models/presenters.
4. Add smoke tests for page rendering and lesson save endpoints.
