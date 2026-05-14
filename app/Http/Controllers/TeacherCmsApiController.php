<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\DictionaryEntry;
use App\Models\Lesson;
use App\Models\LessonStep;
use App\Services\LessonStepMapperService;
use App\Services\TeacherCourseAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class TeacherCmsApiController extends Controller
{
    public function __construct(
        private readonly TeacherCourseAccessService $courseAccess,
        private readonly LessonStepMapperService $stepMapper,
    ) {
    }

    public function getLesson(Request $request, int $id)
    {
        try {
            $lesson = Lesson::query()->with(['module.course', 'steps'])->find($id);

            if (!$lesson || !$lesson->module || !$lesson->module->course) {
                return response()->json(['success' => false, 'message' => 'Lesson not found'], 404);
            }

            $this->courseAccess->findTeacherCourseOrFail($request->user(), (int) $lesson->module->course->id);

            return response()->json([
                'success' => true,
                'title' => $lesson->title ?? '',
                'course_id' => (int) $lesson->module->course->id,
                'module_id' => (int) $lesson->module_id,
                'steps' => $lesson->steps->sortBy('order_num')->values()->map(
                    fn (LessonStep $step) => $this->stepMapper->mapDbStepToFrontend($step)
                )->all(),
            ]);
        } catch (Throwable $e) {
            return $this->handleApiException($e, 'get_lesson', [
                'lesson_id' => $id,
                'user_id' => (int) ($request->user()?->id ?? 0),
            ]);
        }
    }

    public function saveLesson(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|integer',
                'title' => 'required|string|max:255',
                'course_id' => 'required|integer|min:1',
                'module_id' => 'required|integer|min:1',
                'steps' => 'required|array',
            ]);

            $sanitizedSteps = array_values(array_map(
                fn ($step) => is_array($step) ? $this->stepMapper->sanitizeStep($step) : [],
                $data['steps']
            ));

            if (count($sanitizedSteps) === 0) {
                throw ValidationException::withMessages([
                    'steps' => 'Lesson must contain at least one step.',
                ]);
            }

            $course = $this->courseAccess->findTeacherCourseOrFail($request->user(), (int) $data['course_id']);
            $module = CourseModule::query()
                ->where('id', (int) $data['module_id'])
                ->where('course_id', (int) $course->id)
                ->first();

            if (!$module) {
                return response()->json(['success' => false, 'message' => 'Module not found'], 404);
            }

            $lesson = DB::transaction(function () use ($data, $module, $sanitizedSteps) {
                $requestedId = (int) $data['id'];

                $lesson = Lesson::query()
                    ->where('id', $requestedId)
                    ->where('module_id', (int) $module->id)
                    ->first();

                if (!$lesson) {
                    $lesson = new Lesson();
                    $lesson->module_id = (int) $module->id;
                    $lesson->order_num = ((int) Lesson::query()->where('module_id', (int) $module->id)->max('order_num')) + 1;
                    $lesson->status = 'draft';
                    $lesson->lesson_type = 'standard';
                }

                $firstTheory = collect($sanitizedSteps)
                    ->first(fn ($step) => in_array(($step['type'] ?? ''), ['theory', 'dialog'], true));

                $lesson->title = $this->stepMapper->sanitizePlainText($data['title'], 255);
                $lesson->theory_content = is_array($firstTheory) ? ($firstTheory['content'] ?? null) : null;
                $lesson->status = count($sanitizedSteps) > 0 ? 'published' : 'draft';
                $lesson->save();

                $lesson->steps()->delete();
                foreach ($sanitizedSteps as $index => $step) {
                    $payload = $this->stepMapper->mapFrontendStepToDb($step);
                    $lesson->steps()->create([
                        'step_type' => $payload['step_type'],
                        'title' => $payload['title'],
                        'prompt' => $payload['prompt'],
                        'config_json' => $payload['config_json'],
                        'order_num' => $index + 1,
                    ]);
                }

                return $lesson;
            });

            return response()->json([
                'success' => true,
                'message' => 'Lesson saved',
                'lesson_id' => (int) $lesson->id,
            ]);
        } catch (Throwable $e) {
            return $this->handleApiException($e, 'save_lesson', [
                'user_id' => (int) ($request->user()?->id ?? 0),
            ]);
        }
    }

    public function deleteLesson(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|integer|min:1',
            ]);

            $lesson = Lesson::query()->with('module.course')->find((int) $data['id']);
            if (!$lesson || !$lesson->module || !$lesson->module->course) {
                return response()->json(['success' => false, 'message' => 'Lesson not found'], 404);
            }

            $this->courseAccess->findTeacherCourseOrFail($request->user(), (int) $lesson->module->course->id);
            $lesson->delete();

            return response()->json(['success' => true, 'message' => 'Lesson deleted']);
        } catch (Throwable $e) {
            return $this->handleApiException($e, 'delete_lesson', [
                'user_id' => (int) ($request->user()?->id ?? 0),
            ]);
        }
    }

    public function addCourse(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => 'required|string|max:255',
            ]);

            $title = $this->stepMapper->sanitizePlainText($data['title'], 255);

            $course = \App\Models\Course::query()->create([
                'language_id' => $this->courseAccess->ensureDefaultLanguageId(),
                'title' => $title,
                'status' => 'published',
                'visibility' => 'public',
                'created_by' => (int) $request->user()->id,
            ]);

            return response()->json(['success' => true, 'course' => ['id' => (int) $course->id, 'title' => $course->title]]);
        } catch (Throwable $e) {
            return $this->handleApiException($e, 'add_course', [
                'user_id' => (int) ($request->user()?->id ?? 0),
            ]);
        }
    }

    public function addModule(Request $request)
    {
        try {
            $data = $request->validate([
                'course_id' => 'required|integer|min:1',
                'title' => 'required|string|max:255',
            ]);

            $title = $this->stepMapper->sanitizePlainText($data['title'], 255);

            $course = $this->courseAccess->findTeacherCourseOrFail($request->user(), (int) $data['course_id']);
            $module = $course->modules()->create([
                'title' => $title,
                'order_num' => ((int) $course->modules()->max('order_num')) + 1,
            ]);

            return response()->json(['success' => true, 'module' => ['id' => (int) $module->id, 'title' => $module->title]]);
        } catch (Throwable $e) {
            return $this->handleApiException($e, 'add_module', [
                'user_id' => (int) ($request->user()?->id ?? 0),
            ]);
        }
    }

    public function addWord(Request $request)
    {
        try {
            $data = $request->validate([
                'word' => 'required|string|max:255',
                'translation' => 'required|string|max:255',
            ]);

            $entry = DictionaryEntry::query()->create([
                'word' => $this->stepMapper->sanitizePlainText($data['word'], 255),
                'translation' => $this->stepMapper->sanitizePlainText($data['translation'], 255),
                'complexity_index' => 0,
            ]);

            return response()->json([
                'success' => true,
                'entry' => [
                    'id' => (int) $entry->id,
                    'word' => $entry->word,
                    'translation' => $entry->translation,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->handleApiException($e, 'add_word', [
                'user_id' => (int) ($request->user()?->id ?? 0),
            ]);
        }
    }

    public function deleteWord(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|integer|min:1',
            ]);

            $entry = DictionaryEntry::query()->find((int) $data['id']);

            if (!$entry) {
                return response()->json(['success' => false, 'message' => 'Word not found'], 404);
            }

            $entry->delete();

            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return $this->handleApiException($e, 'delete_word', [
                'user_id' => (int) ($request->user()?->id ?? 0),
            ]);
        }
    }

    private function handleApiException(Throwable $e, string $action, array $context = [])
    {
        if ($e instanceof ValidationException) {
            throw $e;
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
                'debug_action' => $action,
            ], 404);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'debug_action' => $action,
            ], 403);
        }

        if ($e instanceof HttpExceptionInterface) {
            return response()->json([
                'success' => false,
                'message' => 'Request failed.',
                'debug_action' => $action,
            ], $e->getStatusCode());
        }

        Log::error('teacher_cms_api_error', array_merge($context, [
            'action' => $action,
            'message' => $e->getMessage(),
            'exception' => get_class($e),
        ]));

        return response()->json($this->safeUtf8([
            'success' => false,
            'message' => 'Server error during operation.',
            'debug_action' => $action,
        ]), 500);
    }

    private function safeUtf8(array $payload): array
    {
        array_walk_recursive($payload, function (&$value): void {
            if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8, Windows-1251, CP866, ISO-8859-1');
            }
        });

        return $payload;
    }
}


