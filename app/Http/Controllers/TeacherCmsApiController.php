<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\DictionaryEntry;
use App\Models\Lesson;
use App\Models\LessonStep;
use App\Services\LessonStepMapperService;
use App\Services\TeacherCourseAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherCmsApiController extends Controller
{
    public function __construct(
        private readonly TeacherCourseAccessService $courseAccess,
        private readonly LessonStepMapperService $stepMapper,
    ) {
    }

    public function getLesson(Request $request, int $id)
    {
        $lesson = Lesson::query()->with(['module.course', 'steps'])->find($id);

        if (!$lesson || !$lesson->module || !$lesson->module->course) {
            return response()->json(['success' => false, 'message' => 'Урок не найден'], 404);
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
    }

    public function saveLesson(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'title' => 'required|string|max:255',
            'course_id' => 'required|integer|min:1',
            'module_id' => 'required|integer|min:1',
            'steps' => 'required|array',
        ]);

        $course = $this->courseAccess->findTeacherCourseOrFail($request->user(), (int) $data['course_id']);
        $module = CourseModule::query()
            ->where('id', (int) $data['module_id'])
            ->where('course_id', (int) $course->id)
            ->first();

        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Модуль не найден'], 404);
        }

        $lesson = DB::transaction(function () use ($data, $module) {
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

            $sanitizedSteps = array_values(array_map(
                fn ($step) => is_array($step) ? $this->stepMapper->sanitizeStep($step) : [],
                $data['steps']
            ));

            $firstTheory = collect($sanitizedSteps)
                ->first(fn ($step) => in_array(($step['type'] ?? ''), ['theory', 'dialog'], true));

            $lesson->title = $this->stepMapper->sanitizePlainText($data['title'], 255);
            $lesson->theory_content = is_array($firstTheory) ? ($firstTheory['content'] ?? null) : null;
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
            'message' => 'Урок сохранен',
            'lesson_id' => (int) $lesson->id,
        ]);
    }

    public function deleteLesson(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|min:1',
        ]);

        $lesson = Lesson::query()->with('module.course')->find((int) $data['id']);
        if (!$lesson || !$lesson->module || !$lesson->module->course) {
            return response()->json(['success' => false, 'message' => 'Урок не найден'], 404);
        }

        $this->courseAccess->findTeacherCourseOrFail($request->user(), (int) $lesson->module->course->id);
        $lesson->delete();

        return response()->json(['success' => true, 'message' => 'Урок удален']);
    }

    public function addCourse(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $title = $this->stepMapper->sanitizePlainText($data['title'], 255);

        $course = \App\Models\Course::query()->create([
            'language_id' => $this->courseAccess->ensureDefaultLanguageId(),
            'title' => $title,
            'status' => 'draft',
            'visibility' => 'private',
            'created_by' => (int) $request->user()->id,
        ]);

        return response()->json(['success' => true, 'course' => ['id' => (int) $course->id, 'title' => $course->title]]);
    }

    public function addModule(Request $request)
    {
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
    }

    public function addWord(Request $request)
    {
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
    }

    public function deleteWord(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|min:1',
        ]);

        $entry = DictionaryEntry::query()->find((int) $data['id']);

        if (!$entry) {
            return response()->json(['success' => false, 'message' => 'Слово не найдено'], 404);
        }

        $entry->delete();

        return response()->json(['success' => true]);
    }
}
