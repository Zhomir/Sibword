<?php

namespace App\Http\Controllers;

use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\Lesson;
use App\Models\ModerationAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonForumController extends Controller
{
    private function resolveCourseIdForLesson(int $lessonId): int
    {
        return (int) DB::table('lessons as l')
            ->join('course_modules as cm', 'cm.id', '=', 'l.module_id')
            ->where('l.id', $lessonId)
            ->value('cm.course_id');
    }

    private function postOwnerId(ForumPost $post): int
    {
        return (int) ($post->author_id ?? $post->user_id ?? 0);
    }

    private function lessonQueryFromRequest(Request $request): array
    {
        $query = [];

        $threadId = (int) $request->input('redirect_forum_thread_id', 0);
        $threadsPage = (int) $request->input('redirect_threads_page', 0);
        $postsPage = (int) $request->input('redirect_posts_page', 0);
        $commentsPage = (int) $request->input('redirect_comments_page', 0);
        $commentsFilter = (string) $request->input('comments_filter', '');

        if ($threadId > 0) {
            $query['forum_thread_id'] = $threadId;
        }
        if ($threadsPage > 0) {
            $query['forum_threads_page'] = $threadsPage;
        }
        if ($postsPage > 0) {
            $query['forum_posts_page'] = $postsPage;
        }
        if ($commentsPage > 0) {
            $query['lesson_comments_page'] = $commentsPage;
        }
        if (in_array($commentsFilter, ['all', 'mine', 'new'], true)) {
            $query['comments_filter'] = $commentsFilter;
        }

        return $query;
    }

    private function redirectToLesson(Request $request, int $lessonId): RedirectResponse
    {
        return redirect()
            ->route('teacher.indes', array_merge(
                ['page' => 'lesson_view', 'id' => $lessonId],
                $this->lessonQueryFromRequest($request),
            ))
            ->withFragment('lesson-forum');
    }

    public function storeThread(Request $request, int $lessonId): RedirectResponse
    {
        $lesson = Lesson::query()->findOrFail($lessonId);
        $courseId = $this->resolveCourseIdForLesson((int) $lesson->id);
        abort_unless($courseId > 0, 422, 'Не удалось определить курс урока.');

        $data = $request->validate([
            'title' => 'required|string|max:180',
            'body' => 'required|string|max:5000',
        ]);

        $thread = DB::transaction(function () use ($request, $lesson, $courseId, $data): ForumThread {
            $thread = ForumThread::query()->create([
                'course_id' => $courseId,
                'lesson_id' => (int) $lesson->id,
                'author_id' => (int) $request->user()->id,
                'user_id' => (int) $request->user()->id,
                'title' => trim((string) $data['title']),
                'is_locked' => false,
            ]);

            ForumPost::query()->create([
                'thread_id' => (int) $thread->id,
                'author_id' => (int) $request->user()->id,
                'user_id' => (int) $request->user()->id,
                'body' => trim((string) $data['body']),
                'is_hidden' => false,
            ]);

            return $thread;
        });

        return redirect()
            ->route('teacher.indes', [
                'page' => 'lesson_view',
                'id' => (int) $lesson->id,
                'forum_thread_id' => (int) $thread->id,
            ])
            ->with('student_status', 'Тема обсуждения создана.')
            ->withFragment('lesson-forum');
    }

        public function storeLessonComment(Request $request, int $lessonId): RedirectResponse|JsonResponse
    {
        $lesson = Lesson::query()->findOrFail($lessonId);
        $courseId = $this->resolveCourseIdForLesson((int) $lesson->id);
        abort_unless($courseId > 0, 422, 'Не удалось определить курс урока.');

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $thread = ForumThread::query()
            ->where('lesson_id', (int) $lesson->id)
            ->orderBy('id')
            ->first();

        if ($thread === null) {
            $thread = ForumThread::query()->create([
                'course_id' => $courseId,
                'lesson_id' => (int) $lesson->id,
                'author_id' => (int) $request->user()->id,
                'user_id' => (int) $request->user()->id,
                'title' => 'Комментарии к уроку',
                'is_locked' => false,
            ]);
        } elseif ((bool) $thread->is_locked) {
            return $this->redirectToLesson($request, (int) $lesson->id)
                ->withErrors(['forum' => 'Обсуждение к этому уроку временно закрыто преподавателем.']);
        }

        $comment = ForumPost::query()->create([
            'thread_id' => (int) $thread->id,
            'author_id' => (int) $request->user()->id,
            'user_id' => (int) $request->user()->id,
            'body' => trim((string) $data['body']),
            'is_hidden' => false,
        ]);

        $thread->touch();

        if ($request->expectsJson()) {
            $comment->loadMissing('author:id,name,role');

            return response()->json([
                'ok' => true,
                'message' => 'Комментарий добавлен.',
                'comment' => [
                    'id' => (int) $comment->id,
                    'body' => (string) $comment->body,
                    'author_id' => (int) ($comment->author_id ?? $comment->user_id ?? 0),
                    'author_name' => (string) ($comment->author->name ?? 'Пользователь'),
                    'author_role' => (string) ($comment->author->role ?? 'student'),
                    'created_at' => optional($comment->created_at)->format('d.m.Y H:i'),
                ],
            ]);
        }

        return $this->redirectToLesson($request, (int) $lesson->id)
            ->with('student_status', 'Комментарий добавлен.');
    }
    public function storePost(Request $request, int $threadId): RedirectResponse
    {
        $thread = ForumThread::query()->findOrFail($threadId);

        if ((bool) $thread->is_locked) {
            return $this->redirectToLesson($request, (int) $thread->lesson_id)
                ->withErrors(['forum' => 'Тема закрыта для новых сообщений.']);
        }

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        ForumPost::query()->create([
            'thread_id' => (int) $thread->id,
            'author_id' => (int) $request->user()->id,
            'user_id' => (int) $request->user()->id,
            'body' => trim((string) $data['body']),
            'is_hidden' => false,
        ]);

        $thread->touch();

        return $this->redirectToLesson($request, (int) $thread->lesson_id)
            ->with('student_status', 'Сообщение добавлено.');
    }

    public function deletePost(Request $request, int $postId): RedirectResponse
    {
        $post = ForumPost::query()->with('thread')->findOrFail($postId);
        $user = $request->user();

        $canDelete = $this->postOwnerId($post) === (int) $user->id
            || ($user->role ?? '') === 'teacher'
            || ($user->role ?? '') === 'admin';

        abort_unless($canDelete, 403);

        $lessonId = (int) ($post->thread->lesson_id ?? 0);
        $threadId = (int) $post->thread_id;

        $post->delete();

        $threadHasPosts = ForumPost::query()->where('thread_id', $threadId)->exists();
        if (!$threadHasPosts) {
            ForumThread::query()->where('id', $threadId)->delete();
        } else {
            ForumThread::query()->where('id', $threadId)->update(['updated_at' => now()]);
        }

        return $this->redirectToLesson($request, $lessonId)
            ->with('student_status', 'Сообщение удалено.');
    }

    public function editPost(Request $request, int $postId): RedirectResponse
    {
        $post = ForumPost::query()->with('thread')->findOrFail($postId);
        $user = $request->user();

        abort_unless($this->postOwnerId($post) === (int) $user->id, 403);

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $post->body = trim((string) $data['body']);
        $post->save();

        return $this->redirectToLesson($request, (int) ($post->thread->lesson_id ?? 0))
            ->with('student_status', 'Комментарий обновлен.');
    }

    public function togglePinPost(Request $request, int $postId): RedirectResponse
    {
        $post = ForumPost::query()->with('thread')->findOrFail($postId);
        $user = $request->user();
        $role = (string) ($user->role ?? 'student');

        abort_unless(in_array($role, ['teacher', 'admin'], true), 403);

        $lessonId = (int) ($post->thread->lesson_id ?? 0);
        $isPinned = ModerationAction::query()
            ->where('entity_type', 'forum_post')
            ->where('entity_id', (int) $post->id)
            ->where('status', 'pinned')
            ->exists();

        if ($isPinned) {
            ModerationAction::query()
                ->where('entity_type', 'forum_post')
                ->where('entity_id', (int) $post->id)
                ->where('status', 'pinned')
                ->delete();

            return $this->redirectToLesson($request, $lessonId)
                ->with('student_status', 'Комментарий откреплен.');
        }

        $lessonPostIds = ForumPost::query()
            ->whereHas('thread', fn ($query) => $query->where('lesson_id', $lessonId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($lessonPostIds) > 0) {
            ModerationAction::query()
                ->where('entity_type', 'forum_post')
                ->whereIn('entity_id', $lessonPostIds)
                ->where('status', 'pinned')
                ->delete();
        }

        ModerationAction::query()->create([
            'entity_type' => 'forum_post',
            'entity_id' => (int) $post->id,
            'reason' => 'Закрепленный комментарий урока',
            'status' => 'pinned',
            'reporter_user_id' => null,
            'moderator_user_id' => (int) $user->id,
            'resolution_note' => null,
        ]);

        return $this->redirectToLesson($request, $lessonId)
            ->with('student_status', 'Комментарий закреплен.');
    }

    public function toggleThreadLock(Request $request, int $threadId): RedirectResponse
    {
        $thread = ForumThread::query()->findOrFail($threadId);
        $role = (string) ($request->user()->role ?? 'student');

        abort_unless(in_array($role, ['teacher', 'admin'], true), 403);

        $thread->is_locked = !$thread->is_locked;
        $thread->save();

        $status = $thread->is_locked
            ? 'Тема закрыта для новых сообщений.'
            : 'Тема снова открыта для обсуждения.';

        return $this->redirectToLesson($request, (int) $thread->lesson_id)
            ->with('student_status', $status);
    }

    public function toggleLessonCommentsLock(Request $request, int $lessonId): RedirectResponse
    {
        $role = (string) ($request->user()->role ?? 'student');
        abort_unless(in_array($role, ['teacher', 'admin'], true), 403);

        $lesson = Lesson::query()->findOrFail($lessonId);
        $thread = ForumThread::query()
            ->where('lesson_id', (int) $lesson->id)
            ->orderBy('id')
            ->first();

        if ($thread === null) {
            $courseId = $this->resolveCourseIdForLesson((int) $lesson->id);
            abort_unless($courseId > 0, 422, 'Не удалось определить курс урока.');

            $thread = ForumThread::query()->create([
                'course_id' => $courseId,
                'lesson_id' => (int) $lesson->id,
                'author_id' => (int) $request->user()->id,
                'user_id' => (int) $request->user()->id,
                'title' => 'Комментарии к уроку',
                'is_locked' => true,
            ]);

            return $this->redirectToLesson($request, (int) $lesson->id)
                ->with('student_status', 'Обсуждение урока закрыто.');
        }

        $thread->is_locked = !$thread->is_locked;
        $thread->save();

        return $this->redirectToLesson($request, (int) $lesson->id)
            ->with('student_status', $thread->is_locked
                ? 'Обсуждение урока закрыто.'
                : 'Обсуждение урока снова открыто.');
    }

    public function reportPost(Request $request, int $postId): RedirectResponse
    {
        $post = ForumPost::query()->with('thread')->findOrFail($postId);
        $userId = (int) $request->user()->id;

        if ($this->postOwnerId($post) === $userId) {
            return $this->redirectToLesson($request, (int) ($post->thread->lesson_id ?? 0))
                ->withErrors(['forum' => 'Нельзя пожаловаться на собственное сообщение.']);
        }

        $data = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $alreadyReported = ModerationAction::query()
            ->where('entity_type', 'forum_post')
            ->where('entity_id', (int) $post->id)
            ->where('reporter_user_id', $userId)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyReported) {
            return $this->redirectToLesson($request, (int) ($post->thread->lesson_id ?? 0))
                ->with('student_status', 'Жалоба уже отправлена и ожидает модерации.');
        }

        ModerationAction::query()->create([
            'entity_type' => 'forum_post',
            'entity_id' => (int) $post->id,
            'reason' => trim((string) ($data['reason'] ?? 'Нарушение правил обсуждения')),
            'status' => 'pending',
            'reporter_user_id' => $userId,
            'moderator_user_id' => null,
            'resolution_note' => null,
        ]);

        return $this->redirectToLesson($request, (int) ($post->thread->lesson_id ?? 0))
            ->with('student_status', 'Жалоба отправлена. Администратор проверит сообщение.');
    }

    public function togglePostLike(Request $request, int $postId): RedirectResponse|JsonResponse
    {
        $post = ForumPost::query()
            ->where('is_hidden', false)
            ->with('thread')
            ->findOrFail($postId);

        $lessonId = (int) ($post->thread->lesson_id ?? 0);
        $userId = (int) ($request->user()->id ?? 0);

        $existingLike = $post->likes()
            ->where('user_id', $userId)
            ->first();

        if ($existingLike !== null) {
            $existingLike->delete();

            $likesCount = (int) $post->likes()->count();
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'liked' => false,
                    'likes_count' => $likesCount,
                    'post_id' => (int) $post->id,
                ]);
            }

            return $this->redirectToLesson($request, $lessonId)
                ->with('student_status', 'Лайк снят.');
        }

        $post->likes()->create([
            'user_id' => $userId,
        ]);

        $likesCount = (int) $post->likes()->count();
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'liked' => true,
                'likes_count' => $likesCount,
                'post_id' => (int) $post->id,
            ]);
        }

        return $this->redirectToLesson($request, $lessonId)
            ->with('student_status', 'Лайк поставлен.');
    }
}
