<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherCmsApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_teacher_can_add_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $response = $this->actingAs($teacher)->post(route('api.course.add'), [
            'title' => 'РўРµСЃС‚РѕРІС‹Р№ РєСѓСЂСЃ',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $courseId = (int) $response->json('course.id');

        $this->assertDatabaseHas('courses', [
            'id' => $courseId,
            'title' => 'РўРµСЃС‚РѕРІС‹Р№ РєСѓСЂСЃ',
            'created_by' => (int) $teacher->id,
        ]);
    }

    public function test_teacher_cannot_add_module_to_foreign_course(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $languageId = (int) (
            DB::table('languages')->where('code', 'bxr')->value('id')
            ?? DB::table('languages')->insertGetId([
                'code' => 'bxr',
                'name' => 'Buryat language (Buryat)',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ])
        );

        $course = Course::query()->create([
            'language_id' => $languageId,
            'title' => 'Р§СѓР¶РѕР№ РєСѓСЂСЃ',
            'status' => 'draft',
            'visibility' => 'private',
            'created_by' => (int) $owner->id,
        ]);

        $this->actingAs($otherTeacher)
            ->post(route('api.module.add'), [
                'course_id' => (int) $course->id,
                'title' => 'РџРѕРїС‹С‚РєР° РґРѕР±Р°РІРёС‚СЊ РјРѕРґСѓР»СЊ',
            ])
            ->assertStatus(404);
    }

    public function test_teacher_lesson_crud_flow_works(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $courseResponse = $this->actingAs($teacher)->post(route('api.course.add'), [
            'title' => 'РљСѓСЂСЃ РґР»СЏ СѓСЂРѕРєР°',
        ]);
        $courseResponse->assertOk()->assertJson(['success' => true]);
        $courseId = (int) $courseResponse->json('course.id');

        $moduleResponse = $this->actingAs($teacher)->post(route('api.module.add'), [
            'course_id' => $courseId,
            'title' => 'РњРѕРґСѓР»СЊ 1',
        ]);
        $moduleResponse->assertOk()->assertJson(['success' => true]);
        $moduleId = (int) $moduleResponse->json('module.id');

        $saveResponse = $this->actingAs($teacher)->post(route('api.lesson.save'), [
            'id' => 0,
            'title' => 'РЈСЂРѕРє 1',
            'course_id' => $courseId,
            'module_id' => $moduleId,
            'steps' => [
                [
                    'type' => 'theory',
                    'title' => 'Р’СЃС‚СѓРїР»РµРЅРёРµ',
                    'content' => '<b>РўРµРєСЃС‚</b>',
                ],
                [
                    'type' => 'task',
                    'task_type' => 'multiple_choice',
                    'question' => 'Р’РѕРїСЂРѕСЃ?',
                    'options' => ['A', 'B', 'C'],
                    'correct_idx' => 1,
                ],
            ],
        ]);

        $saveResponse->assertOk()->assertJson(['success' => true]);
        $lessonId = (int) $saveResponse->json('lesson_id');

        $this->actingAs($teacher)
            ->get(route('api.lesson.get', ['id' => $lessonId]))
            ->assertOk()
            ->assertJson(['success' => true, 'title' => 'РЈСЂРѕРє 1']);

        $this->actingAs($teacher)
            ->post(route('api.lesson.delete'), ['id' => $lessonId])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('lessons', ['id' => $lessonId]);
    }
}
