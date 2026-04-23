# Client-Server Transition (Start)

## Current State
- Web routes and API routes are mixed in `routes/web.php`.
- Main storage for runtime editor data is still partially session-based.
- Database backend is SQLite for local testing.

## What Was Added Now
1. API routing layer:
- Added `routes/api.php`.
- Enabled API route loading in `bootstrap/app.php`.
- Added versioned prefix: `/api/v1/...`.

2. API controllers:
- `app/Http/Controllers/Api/AuthApiController.php`
- `app/Http/Controllers/Api/HealthApiController.php`

3. CORS baseline:
- Added `config/cors.php` for local frontend hosts (`:5173`, `:3000`, `:8000`).

4. Existing teacher API mapped into new namespace:
- `/api/v1/teacher/*` points to current `TeacherCmsApiController`.
- Role restrictions kept via middleware (`auth`, `role:teacher`).

## Phase Plan
### Phase 1 (now)
- Keep legacy endpoints (`/api/*` in web routes) for compatibility.
- Introduce new versioned API routes and start frontend migration to `/api/v1`.

### Phase 2
- Move session-based curriculum/lesson runtime structures fully into DB tables.
- Add dedicated API resources (`CourseApiController`, `LessonApiController`, `DictionaryApiController`).
- Add request DTO/validation classes for API contracts.

### Phase 3
- Introduce stateless auth for external clients (Sanctum/JWT).
- Add API docs (OpenAPI/Swagger).
- Add rate-limits, audit logs, and background jobs for heavy operations.

## Immediate Next Technical Tasks
1. Create `course_modules` table migration and migrate lesson linkage from `course_id` to `module_id`.
2. Create `lesson_steps` + `lesson_step_options` migrations to replace JSON-only step payloads.
3. Build `user_lesson_attempts` + `user_step_answers` for persistent progress and error analytics.
4. Deprecate legacy `/api/*` web routes after frontend switches to `/api/v1/*`.

