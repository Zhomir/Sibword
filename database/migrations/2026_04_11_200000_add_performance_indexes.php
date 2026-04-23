<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_course_progress', function (Blueprint $table): void {
            $table->index(['user_id', 'course_id'], 'ucp_user_course_idx');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->index(['status', 'created_by'], 'courses_status_created_by_idx');
        });

        Schema::table('course_modules', function (Blueprint $table): void {
            $table->index(['course_id', 'order_num'], 'course_modules_course_order_idx');
        });

        Schema::table('lessons', function (Blueprint $table): void {
            $table->index(['module_id', 'order_num'], 'lessons_module_order_idx');
        });

        Schema::table('lesson_steps', function (Blueprint $table): void {
            $table->index(['lesson_id', 'order_num'], 'lesson_steps_lesson_order_idx');
        });

        Schema::table('lexemes', function (Blueprint $table): void {
            $table->index(['language_id', 'status', 'word'], 'lexemes_lang_status_word_idx');
        });
    }

    public function down(): void
    {
        Schema::table('user_course_progress', function (Blueprint $table): void {
            $table->dropIndex('ucp_user_course_idx');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropIndex('courses_status_created_by_idx');
        });

        Schema::table('course_modules', function (Blueprint $table): void {
            $table->dropIndex('course_modules_course_order_idx');
        });

        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropIndex('lessons_module_order_idx');
        });

        Schema::table('lesson_steps', function (Blueprint $table): void {
            $table->dropIndex('lesson_steps_lesson_order_idx');
        });

        Schema::table('lexemes', function (Blueprint $table): void {
            $table->dropIndex('lexemes_lang_status_word_idx');
        });
    }
};
