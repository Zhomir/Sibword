            @php
                $lesson = $lessons[$lessonId] ?? (count($lessons) ? reset($lessons) : null);
            @endphp
            @if($lesson)
            <link rel="stylesheet" href="{{ asset('css/lesson-modern.css') }}">
            @php
                $lessonComments = $lessonForum['comments'] ?? [];
                $commentsPage = $lessonForum['comments_page'] ?? null;
                $commentsTotal = (int) ($lessonForum['comments_total'] ?? 0);
                $commentsFilter = (string) ($lessonForum['comments_filter'] ?? 'all');
                $pinnedComment = $lessonForum['pinned_comment'] ?? null;
                $currentUserId = (int) (auth()->id() ?? 0);
                $currentRole = (string) (auth()->user()?->role ?? 'student');
                $commentsPageNum = (int) request()->query('lesson_comments_page', 1);
            @endphp
            <div id="learning-container" class="learning-wrapper">
                <div class="learning-header">
                    <a href="{{ route('teacher.indes', ['page' => 'student_dashboard']) }}" class="lesson-close-link" aria-label="Закрыть урок">Назад</a>
                    <div class="learning-header-main">
                        <div class="learning-kicker">Прохождение урока</div>
                        <h1 class="learning-title">{{ $lesson['title'] ?? 'Урок' }}</h1>
                        <div class="learning-meta">
                            <span id="lesson-step-counter" class="learning-meta-chip">Шаг 1</span>
                            <span id="lesson-mode-badge" class="learning-meta-chip">Основной проход</span>
                        </div>
                    </div>
                    <div class="learning-header-side">
                        <div class="learning-progress">
                            <div id="lesson-progress" class="learning-progress-bar"></div>
                        </div>
                        <div id="lesson-hp" class="user-hp">❤ 3</div>
                    </div>
                </div>
                <div id="scene-canvas" class="lesson-stage">
                    <div id="theory-block" class="scene-content"></div>
                    <div id="practice-block" class="task-content is-hidden"></div>
                    <aside id="lesson-forum" class="lesson-forum-panel">
                        <div class="lesson-forum-head">
                            <h2 class="lesson-forum-title">Комментарии урока</h2>
                            <div class="lesson-forum-subtitle">Всего комментариев: {{ $commentsTotal }}</div>
                            @if(in_array($currentRole, ['teacher', 'admin'], true))
                                <form method="POST" action="{{ route('lesson.forum.lesson.toggleLock', ['lessonId' => (int) ($lessonForum['lesson_id'] ?? $lessonId)]) }}">
                                    @csrf
                                    <input type="hidden" name="redirect_comments_page" value="{{ $commentsPageNum }}">
                                    <input type="hidden" name="comments_filter" value="{{ $commentsFilter }}">
                                    <button type="submit" class="lesson-forum-btn is-secondary">Открыть/закрыть комментарии</button>
                                </form>
                            @endif
                        </div>

                        <div class="lesson-post-actions" style="margin-bottom: 10px;">
                            <a href="{{ route('teacher.indes', ['page' => 'lesson_view', 'id' => (int) ($lessonForum['lesson_id'] ?? $lessonId), 'comments_filter' => 'all']) }}#lesson-forum" class="lesson-forum-btn is-secondary {{ $commentsFilter === 'all' ? '' : '' }}">Все</a>
                            <a href="{{ route('teacher.indes', ['page' => 'lesson_view', 'id' => (int) ($lessonForum['lesson_id'] ?? $lessonId), 'comments_filter' => 'mine']) }}#lesson-forum" class="lesson-forum-btn is-secondary">Мои</a>
                            <a href="{{ route('teacher.indes', ['page' => 'lesson_view', 'id' => (int) ($lessonForum['lesson_id'] ?? $lessonId), 'comments_filter' => 'new']) }}#lesson-forum" class="lesson-forum-btn is-secondary">Новые (7 дней)</a>
                        </div>

                        @if ($errors->has('forum') || $errors->has('body') || $errors->has('reason'))
                            <div class="lesson-forum-error">{{ $errors->first('forum') ?: $errors->first('body') ?: $errors->first('reason') }}</div>
                        @endif

                        <form method="POST" action="{{ route('lesson.comment.store', ['lessonId' => (int) ($lessonForum['lesson_id'] ?? $lessonId)]) }}" class="lesson-forum-form js-comment-form">
                            @csrf
                            <input type="hidden" name="redirect_comments_page" value="{{ $commentsPageNum }}">
                            <input type="hidden" name="comments_filter" value="{{ $commentsFilter }}">
                            <textarea name="body" rows="3" maxlength="5000" required placeholder="Оставить комментарий по уроку..." class="lesson-forum-textarea"></textarea>
                            <button type="submit" class="lesson-forum-btn">Отправить комментарий</button>
                        </form>

                        @if($pinnedComment)
                            <div class="lesson-post" style="border-color: rgba(250, 204, 21, 0.5);">
                                <div class="lesson-post-meta">
                                    <span class="lesson-thread-lock">Закреплено</span>
                                    <span class="lesson-post-author">{{ $pinnedComment['author_name'] }}</span>
                                    <span>{{ $pinnedComment['created_at'] }}</span>
                                </div>
                                <p class="lesson-post-body">{{ $pinnedComment['body'] }}</p>
                            </div>
                        @endif

                        <div class="lesson-posts">
                            @forelse($lessonComments as $post)
                                <div class="lesson-post">
                                    <div class="lesson-post-meta">
                                        <span class="lesson-post-author">{{ $post['author_name'] }}</span>
                                        <span>{{ $post['created_at'] }}</span>
                                    </div>
                                    <p class="lesson-post-body">{{ $post['body'] }}</p>

                                    <div class="lesson-post-actions">
                                        <form method="POST" action="{{ route('lesson.forum.post.like', ['postId' => $post['id']]) }}" class="js-like-form" data-post-id="{{ (int) $post['id'] }}">
                                            @csrf
                                            <input type="hidden" name="redirect_comments_page" value="{{ $commentsPageNum }}">
                                            <input type="hidden" name="comments_filter" value="{{ $commentsFilter }}">
                                            <button type="submit" class="lesson-post-like js-like-button {{ !empty($post['liked_by_me']) ? 'is-liked' : '' }}">
                                                👍 <span class="js-like-count">{{ (int) ($post['likes_count'] ?? 0) }}</span>
                                            </button>
                                        </form>

                                        @if(($post['author_id'] ?? 0) !== $currentUserId)
                                            <form method="POST" action="{{ route('lesson.forum.post.report', ['postId' => $post['id']]) }}">
                                                @csrf
                                                <input type="hidden" name="redirect_comments_page" value="{{ $commentsPageNum }}">
                                                <input type="hidden" name="comments_filter" value="{{ $commentsFilter }}">
                                                <input type="hidden" name="reason" value="Нарушение правил обсуждения">
                                                <button type="submit" class="lesson-post-report">Пожаловаться</button>
                                            </form>
                                        @endif

                                        @if(($post['author_id'] ?? 0) === $currentUserId || in_array((string) (auth()->user()?->role ?? 'student'), ['teacher', 'admin'], true))
                                            <form method="POST" action="{{ route('lesson.forum.post.delete', ['postId' => $post['id']]) }}">
                                                @csrf
                                                <input type="hidden" name="redirect_comments_page" value="{{ $commentsPageNum }}">
                                                <input type="hidden" name="comments_filter" value="{{ $commentsFilter }}">
                                                <button type="submit" class="lesson-post-delete">Удалить</button>
                                            </form>
                                        @endif

                                        @if(($post['author_id'] ?? 0) === $currentUserId)
                                            <details>
                                                <summary class="lesson-post-report">Редактировать</summary>
                                                <form method="POST" action="{{ route('lesson.forum.post.edit', ['postId' => $post['id']]) }}" class="lesson-reply-form">
                                                    @csrf
                                                    <input type="hidden" name="redirect_comments_page" value="{{ $commentsPageNum }}">
                                                    <input type="hidden" name="comments_filter" value="{{ $commentsFilter }}">
                                                    <textarea name="body" rows="2" maxlength="5000" required class="lesson-forum-textarea">{{ $post['body'] }}</textarea>
                                                    <button type="submit" class="lesson-forum-btn is-secondary">Сохранить</button>
                                                </form>
                                            </details>
                                        @endif

                                        @if(in_array($currentRole, ['teacher', 'admin'], true))
                                            <form method="POST" action="{{ route('lesson.forum.post.pin', ['postId' => $post['id']]) }}">
                                                @csrf
                                                <input type="hidden" name="redirect_comments_page" value="{{ $commentsPageNum }}">
                                                <input type="hidden" name="comments_filter" value="{{ $commentsFilter }}">
                                                <button type="submit" class="lesson-post-report">Закрепить</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="lesson-forum-empty">Пока нет комментариев. Будьте первым.</div>
                            @endforelse
                        </div>

                        @if($commentsPage && $commentsPage->hasPages())
                            <div class="lesson-pagination">
                                @if($commentsPage->onFirstPage())
                                    <span class="lesson-page-disabled">Назад</span>
                                @else
                                    <a href="{{ $commentsPage->previousPageUrl() }}#lesson-forum">Назад</a>
                                @endif
                                <span>Комментарии {{ $commentsPage->currentPage() }} / {{ $commentsPage->lastPage() }}</span>
                                @if($commentsPage->hasMorePages())
                                    <a href="{{ $commentsPage->nextPageUrl() }}#lesson-forum">Вперед</a>
                                @else
                                    <span class="lesson-page-disabled">Вперед</span>
                                @endif
                            </div>
                        @endif
                    </aside>
                </div>
                <div class="lesson-controls">
                    <button id="next-step" class="main-btn lesson-btn" type="button">Продолжить</button>
                    <button id="skip-step" class="main-btn lesson-btn lesson-btn-secondary is-hidden" type="button">Не могу сейчас</button>
                </div>
            </div>
            @else
                <div class="teacher-card">
                    <h3 class="teacher-section-title">Урок не найден</h3>
                    <p class="teacher-muted">Сначала преподавателю нужно создать курс, модуль и урок.</p>
                    <a href="{{ route('teacher.indes', ['page' => 'teacher_panel']) }}" class="btn btn-primary">Перейти в редактор</a>
                </div>
            @endif
