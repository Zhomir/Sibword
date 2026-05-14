<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LessonForumController;
use App\Http\Controllers\OpenDayController;
use App\Http\Controllers\QuestApiController;
use App\Http\Controllers\TeacherAssetController;
use App\Http\Controllers\TeacherCmsApiController;
use App\Http\Controllers\TeacherPrototypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/open-day', [OpenDayController::class, 'index'])->name('open.day');
Route::view('/quest', 'open-day.quest')->name('quest');
Route::get('/api/quest/{code?}', [QuestApiController::class, 'show'])->name('api.quest.show');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();

        return match ($user?->role) {
            'admin' => redirect()->route('admin.index'),
            'teacher' => redirect()->route('teacher.home'),
            default => redirect()->route('student.dashboard'),
        };
    })->name('dashboard');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/courses', function () {
        return redirect()->route('student.catalog');
    })->name('courses.index');

    Route::post('/courses/{course}/select', [TeacherPrototypeController::class, 'enrollCourse'])
        ->name('courses.enroll');
    Route::post('/courses/{course}/unselect', [TeacherPrototypeController::class, 'leaveCourse'])
        ->name('courses.leave');
    Route::post('/courses/{course}/review', [TeacherPrototypeController::class, 'storeCourseReview'])
        ->name('courses.review.store');

    Route::get('/lesson/{id}', [TeacherPrototypeController::class, 'lessonView'])->name('lessons.show');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/admin/users', [AdminController::class, 'usersPage'])->name('admin.users.page');
    Route::get('/admin/knowledge-page', [AdminController::class, 'knowledgePage'])->name('admin.knowledge.page');
    Route::get('/admin/moderation', [AdminController::class, 'moderationPage'])->name('admin.moderation.page');
    Route::post('/admin/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('admin.users.role');
    Route::post('/admin/knowledge', [AdminController::class, 'storeKnowledge'])->name('admin.knowledge.store');
    Route::post('/admin/knowledge/{dictionaryEntry}/delete', [AdminController::class, 'destroyKnowledge'])->name('admin.knowledge.delete');
    Route::post('/admin/knowledge/import', [AdminController::class, 'importKnowledge'])->name('admin.knowledge.import');
    Route::get('/admin/knowledge/export', [AdminController::class, 'exportKnowledge'])->name('admin.knowledge.export');
    Route::post('/admin/reviews/{courseReview}/approve', [AdminController::class, 'approveReview'])->name('admin.reviews.approve');
    Route::post('/admin/reviews/{courseReview}/reject', [AdminController::class, 'rejectReview'])->name('admin.reviews.reject');
    Route::post('/admin/moderation/{moderationAction}/hide', [AdminController::class, 'hideReportedPost'])->name('admin.moderation.hide');
    Route::post('/admin/moderation/{moderationAction}/restore', [AdminController::class, 'restoreReportedPost'])->name('admin.moderation.restore');
});

Route::middleware(['auth', 'role:student,teacher'])->group(function () {
    Route::get('/student/dashboard', [TeacherPrototypeController::class, 'studentDashboard'])->name('student.dashboard');
    Route::get('/student/profile', [TeacherPrototypeController::class, 'studentProfilePage'])->name('student.profile');
    Route::post('/student/profile/update', [TeacherPrototypeController::class, 'updateStudentProfile'])->name('student.profile.update');
    Route::get('/student/courses', [TeacherPrototypeController::class, 'studentCoursesPage'])->name('student.courses');
    Route::get('/student/progress', [TeacherPrototypeController::class, 'studentProgressPage'])->name('student.progress');
    Route::get('/student/catalog', [TeacherPrototypeController::class, 'studentCatalog'])->name('student.catalog');
    Route::get('/lesson-view/{id}', [TeacherPrototypeController::class, 'lessonView'])->name('lesson.view');
    Route::get('/teacher/index', [TeacherPrototypeController::class, 'index'])->name('teacher.index');
    Route::post('/teacher/index', [TeacherPrototypeController::class, 'handle'])->name('teacher.index.handle');
    Route::get('/teacher/indes', fn () => redirect()->route('teacher.index'))->name('teacher.indes');
    Route::post('/teacher/indes', [TeacherPrototypeController::class, 'handle'])->name('teacher.indes.handle');
    Route::post('/lesson/{lessonId}/comment', [LessonForumController::class, 'storeLessonComment'])->name('lesson.comment.store');
    Route::post('/lesson/{lessonId}/forum/toggle-lock', [LessonForumController::class, 'toggleLessonCommentsLock'])->name('lesson.forum.lesson.toggleLock');
    Route::post('/lesson/{lessonId}/forum/thread', [LessonForumController::class, 'storeThread'])->name('lesson.forum.thread.store');
    Route::post('/forum/thread/{threadId}/post', [LessonForumController::class, 'storePost'])->name('lesson.forum.post.store');
    Route::post('/forum/thread/{threadId}/toggle-lock', [LessonForumController::class, 'toggleThreadLock'])->name('lesson.forum.thread.toggleLock');
    Route::post('/forum/post/{postId}/edit', [LessonForumController::class, 'editPost'])->name('lesson.forum.post.edit');
    Route::post('/forum/post/{postId}/pin', [LessonForumController::class, 'togglePinPost'])->name('lesson.forum.post.pin');
    Route::post('/forum/post/{postId}/delete', [LessonForumController::class, 'deletePost'])->name('lesson.forum.post.delete');
    Route::post('/forum/post/{postId}/like', [LessonForumController::class, 'togglePostLike'])
        ->middleware('throttle:40,1')
        ->name('lesson.forum.post.like');
    Route::post('/forum/post/{postId}/report', [LessonForumController::class, 'reportPost'])->name('lesson.forum.post.report');
});

Route::middleware(['auth', 'role:teacher'])->group(function () {
    Route::get('/teacher', [TeacherPrototypeController::class, 'teacherHomePage'])->name('teacher.home');
    Route::get('/teacher/analytics', [TeacherPrototypeController::class, 'teacherAnalyticsPage'])->name('teacher.analytics.page');
    Route::get('/teacher/courses', [TeacherPrototypeController::class, 'teacherCoursesPage'])->name('teacher.courses.page');
    Route::get('/teacher/editor', [TeacherPrototypeController::class, 'teacherPanelPage'])->name('teacher.panel.page');
    Route::get('/teacher/achievements', [TeacherPrototypeController::class, 'teacherAchievementsPage'])->name('teacher.achievements.page');

    Route::post('/teacher/courses/{course}/toggle-publication', [TeacherPrototypeController::class, 'toggleCoursePublication'])
        ->name('teacher.courses.togglePublication');
    Route::post('/teacher/courses/{course}/delete', [TeacherPrototypeController::class, 'deleteCourse'])
        ->name('teacher.courses.delete');

    Route::post('/teacher/assets/upload', [TeacherAssetController::class, 'upload'])
        ->name('teacher.assets.upload');

    Route::get('/api/lesson/{id}', [TeacherCmsApiController::class, 'getLesson'])->name('api.lesson.get');
    Route::post('/api/lesson/save', [TeacherCmsApiController::class, 'saveLesson'])->name('api.lesson.save');
    Route::post('/api/lesson/delete', [TeacherCmsApiController::class, 'deleteLesson'])->name('api.lesson.delete');
    Route::post('/api/course/add', [TeacherCmsApiController::class, 'addCourse'])->name('api.course.add');
    Route::post('/api/module/add', [TeacherCmsApiController::class, 'addModule'])->name('api.module.add');
    Route::post('/api/dictionary/add', [TeacherCmsApiController::class, 'addWord'])->name('api.dictionary.add');
    Route::post('/api/dictionary/delete', [TeacherCmsApiController::class, 'deleteWord'])->name('api.dictionary.delete');
    Route::post('/api/achievement/add', [TeacherPrototypeController::class, 'addCourseAchievement'])->name('api.achievement.add');
    Route::post('/api/achievement/delete', [TeacherPrototypeController::class, 'deleteCourseAchievement'])->name('api.achievement.delete');
});
