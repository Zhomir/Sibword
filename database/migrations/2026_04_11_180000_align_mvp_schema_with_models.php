<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureLanguagesTable();
        $this->ensureLexemesTable();
        $this->ensureCoursesTable();
        $this->ensureCourseTeachersTable();
        $this->ensureCourseModulesTable();
        $this->ensureLessonsTable();
        $this->ensureLessonStepsTable();
        $this->ensureUserCourseProgressTable();
    }

    public function down(): void
    {
        // Intentionally non-destructive: this migration aligns legacy schemas.
    }

    private function addColumnIfMissing(string $table, string $column, callable $callback): void
    {
        if (Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($callback): void {
            $callback($tableBlueprint);
        });
    }

    private function ensureLanguagesTable(): void
    {
        if (!Schema::hasTable('languages')) {
            Schema::create('languages', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 16)->unique();
                $table->string('name', 120);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            return;
        }

        $this->addColumnIfMissing('languages', 'code', fn (Blueprint $table) => $table->string('code', 16)->nullable());
        $this->addColumnIfMissing('languages', 'name', fn (Blueprint $table) => $table->string('name', 120)->nullable());
        $this->addColumnIfMissing('languages', 'is_active', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        $this->addColumnIfMissing('languages', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('languages', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    private function ensureLexemesTable(): void
    {
        if (!Schema::hasTable('lexemes')) {
            Schema::create('lexemes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('language_id')->nullable();
                $table->string('word');
                $table->string('translation');
                $table->string('transcription')->nullable();
                $table->decimal('complexity_index', 4, 2)->default(0);
                $table->enum('status', ['draft', 'published', 'archived'])->default('published');
                $table->foreignId('created_by')->nullable();
                $table->foreignId('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
            return;
        }

        $this->addColumnIfMissing('lexemes', 'language_id', fn (Blueprint $table) => $table->foreignId('language_id')->nullable());
        $this->addColumnIfMissing('lexemes', 'word', fn (Blueprint $table) => $table->string('word')->nullable());
        $this->addColumnIfMissing('lexemes', 'translation', fn (Blueprint $table) => $table->string('translation')->nullable());
        $this->addColumnIfMissing('lexemes', 'transcription', fn (Blueprint $table) => $table->string('transcription')->nullable());
        $this->addColumnIfMissing('lexemes', 'complexity_index', fn (Blueprint $table) => $table->decimal('complexity_index', 4, 2)->default(0));
        $this->addColumnIfMissing('lexemes', 'status', fn (Blueprint $table) => $table->string('status', 32)->default('published'));
        $this->addColumnIfMissing('lexemes', 'created_by', fn (Blueprint $table) => $table->foreignId('created_by')->nullable());
        $this->addColumnIfMissing('lexemes', 'approved_by', fn (Blueprint $table) => $table->foreignId('approved_by')->nullable());
        $this->addColumnIfMissing('lexemes', 'approved_at', fn (Blueprint $table) => $table->timestamp('approved_at')->nullable());
        $this->addColumnIfMissing('lexemes', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('lexemes', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    private function ensureCoursesTable(): void
    {
        if (!Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('language_id')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('level', 32)->nullable();
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
                $table->enum('visibility', ['private', 'group', 'public'])->default('private');
                $table->foreignId('created_by')->nullable();
                $table->foreignId('moderated_by')->nullable();
                $table->timestamp('moderated_at')->nullable();
                $table->timestamps();
            });
            return;
        }

        $this->addColumnIfMissing('courses', 'language_id', fn (Blueprint $table) => $table->foreignId('language_id')->nullable());
        $this->addColumnIfMissing('courses', 'title', fn (Blueprint $table) => $table->string('title')->nullable());
        $this->addColumnIfMissing('courses', 'description', fn (Blueprint $table) => $table->text('description')->nullable());
        $this->addColumnIfMissing('courses', 'level', fn (Blueprint $table) => $table->string('level', 32)->nullable());
        $this->addColumnIfMissing('courses', 'status', fn (Blueprint $table) => $table->string('status', 32)->default('draft'));
        $this->addColumnIfMissing('courses', 'visibility', fn (Blueprint $table) => $table->string('visibility', 32)->default('private'));
        $this->addColumnIfMissing('courses', 'created_by', fn (Blueprint $table) => $table->foreignId('created_by')->nullable());
        $this->addColumnIfMissing('courses', 'moderated_by', fn (Blueprint $table) => $table->foreignId('moderated_by')->nullable());
        $this->addColumnIfMissing('courses', 'moderated_at', fn (Blueprint $table) => $table->timestamp('moderated_at')->nullable());
    }

    private function ensureCourseTeachersTable(): void
    {
        if (!Schema::hasTable('course_teachers')) {
            Schema::create('course_teachers', function (Blueprint $table): void {
                $table->foreignId('course_id');
                $table->foreignId('teacher_id');
                $table->boolean('can_edit')->default(true);
                $table->timestamps();
                $table->primary(['course_id', 'teacher_id']);
            });
            return;
        }

        $this->addColumnIfMissing('course_teachers', 'course_id', fn (Blueprint $table) => $table->foreignId('course_id')->nullable());
        $this->addColumnIfMissing('course_teachers', 'teacher_id', fn (Blueprint $table) => $table->foreignId('teacher_id')->nullable());
        $this->addColumnIfMissing('course_teachers', 'can_edit', fn (Blueprint $table) => $table->boolean('can_edit')->default(true));
        $this->addColumnIfMissing('course_teachers', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('course_teachers', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    private function ensureCourseModulesTable(): void
    {
        if (!Schema::hasTable('course_modules')) {
            Schema::create('course_modules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('course_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedInteger('order_num')->default(1);
                $table->timestamps();
            });
            return;
        }

        $this->addColumnIfMissing('course_modules', 'course_id', fn (Blueprint $table) => $table->foreignId('course_id')->nullable());
        $this->addColumnIfMissing('course_modules', 'title', fn (Blueprint $table) => $table->string('title')->nullable());
        $this->addColumnIfMissing('course_modules', 'description', fn (Blueprint $table) => $table->text('description')->nullable());
        $this->addColumnIfMissing('course_modules', 'order_num', fn (Blueprint $table) => $table->unsignedInteger('order_num')->default(1));
        $this->addColumnIfMissing('course_modules', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('course_modules', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    private function ensureLessonsTable(): void
    {
        if (!Schema::hasTable('lessons')) {
            Schema::create('lessons', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('module_id')->nullable();
                $table->string('title');
                $table->longText('theory_content')->nullable();
                $table->string('lesson_type', 64)->default('standard');
                $table->unsignedInteger('order_num')->default(1);
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
                $table->unsignedInteger('estimated_minutes')->nullable();
                $table->timestamps();
            });
            return;
        }

        $this->addColumnIfMissing('lessons', 'module_id', fn (Blueprint $table) => $table->foreignId('module_id')->nullable());
        $this->addColumnIfMissing('lessons', 'title', fn (Blueprint $table) => $table->string('title')->nullable());
        $this->addColumnIfMissing('lessons', 'theory_content', fn (Blueprint $table) => $table->longText('theory_content')->nullable());
        $this->addColumnIfMissing('lessons', 'lesson_type', fn (Blueprint $table) => $table->string('lesson_type', 64)->default('standard'));
        $this->addColumnIfMissing('lessons', 'order_num', fn (Blueprint $table) => $table->unsignedInteger('order_num')->default(1));
        $this->addColumnIfMissing('lessons', 'status', fn (Blueprint $table) => $table->string('status', 32)->default('draft'));
        $this->addColumnIfMissing('lessons', 'estimated_minutes', fn (Blueprint $table) => $table->unsignedInteger('estimated_minutes')->nullable());
    }

    private function ensureLessonStepsTable(): void
    {
        if (!Schema::hasTable('lesson_steps')) {
            Schema::create('lesson_steps', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('lesson_id');
                $table->string('step_type', 64);
                $table->string('title')->nullable();
                $table->text('prompt')->nullable();
                $table->json('config_json')->nullable();
                $table->unsignedInteger('order_num')->default(1);
                $table->timestamps();
            });
            return;
        }

        $this->addColumnIfMissing('lesson_steps', 'lesson_id', fn (Blueprint $table) => $table->foreignId('lesson_id')->nullable());
        $this->addColumnIfMissing('lesson_steps', 'step_type', fn (Blueprint $table) => $table->string('step_type', 64)->nullable());
        $this->addColumnIfMissing('lesson_steps', 'title', fn (Blueprint $table) => $table->string('title')->nullable());
        $this->addColumnIfMissing('lesson_steps', 'prompt', fn (Blueprint $table) => $table->text('prompt')->nullable());
        $this->addColumnIfMissing('lesson_steps', 'config_json', fn (Blueprint $table) => $table->json('config_json')->nullable());
        $this->addColumnIfMissing('lesson_steps', 'order_num', fn (Blueprint $table) => $table->unsignedInteger('order_num')->default(1));
        $this->addColumnIfMissing('lesson_steps', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('lesson_steps', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    private function ensureUserCourseProgressTable(): void
    {
        if (!Schema::hasTable('user_course_progress')) {
            Schema::create('user_course_progress', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id');
                $table->foreignId('course_id');
                $table->decimal('progress_percent', 5, 2)->default(0);
                $table->unsignedInteger('completed_lessons')->default(0);
                $table->unsignedInteger('xp_earned')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
            return;
        }

        $this->addColumnIfMissing('user_course_progress', 'user_id', fn (Blueprint $table) => $table->foreignId('user_id')->nullable());
        $this->addColumnIfMissing('user_course_progress', 'course_id', fn (Blueprint $table) => $table->foreignId('course_id')->nullable());
        $this->addColumnIfMissing('user_course_progress', 'progress_percent', fn (Blueprint $table) => $table->decimal('progress_percent', 5, 2)->default(0));
        $this->addColumnIfMissing('user_course_progress', 'completed_lessons', fn (Blueprint $table) => $table->unsignedInteger('completed_lessons')->default(0));
        $this->addColumnIfMissing('user_course_progress', 'xp_earned', fn (Blueprint $table) => $table->unsignedInteger('xp_earned')->default(0));
        $this->addColumnIfMissing('user_course_progress', 'started_at', fn (Blueprint $table) => $table->timestamp('started_at')->nullable());
        $this->addColumnIfMissing('user_course_progress', 'completed_at', fn (Blueprint $table) => $table->timestamp('completed_at')->nullable());
        $this->addColumnIfMissing('user_course_progress', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('user_course_progress', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }
};
