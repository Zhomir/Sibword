<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureForumThreadsTable();
        $this->ensureForumPostsTable();
    }

    public function down(): void
    {
        // Intentionally non-destructive.
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

    private function ensureForumThreadsTable(): void
    {
        if (!Schema::hasTable('forum_threads')) {
            Schema::create('forum_threads', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('lesson_id')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->string('title', 180);
                $table->boolean('is_locked')->default(false);
                $table->timestamps();

                $table->index(['lesson_id', 'updated_at'], 'forum_threads_lesson_updated_idx');
            });
            return;
        }

        $this->addColumnIfMissing('forum_threads', 'lesson_id', fn (Blueprint $table) => $table->unsignedBigInteger('lesson_id')->nullable());
        $this->addColumnIfMissing('forum_threads', 'user_id', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable());
        $this->addColumnIfMissing('forum_threads', 'title', fn (Blueprint $table) => $table->string('title', 180)->nullable());
        $this->addColumnIfMissing('forum_threads', 'is_locked', fn (Blueprint $table) => $table->boolean('is_locked')->default(false));
        $this->addColumnIfMissing('forum_threads', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('forum_threads', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    private function ensureForumPostsTable(): void
    {
        if (!Schema::hasTable('forum_posts')) {
            Schema::create('forum_posts', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('thread_id');
                $table->unsignedBigInteger('user_id');
                $table->text('body');
                $table->boolean('is_hidden')->default(false);
                $table->timestamps();

                $table->index(['thread_id', 'created_at'], 'forum_posts_thread_created_idx');
            });
            return;
        }

        $this->addColumnIfMissing('forum_posts', 'thread_id', fn (Blueprint $table) => $table->unsignedBigInteger('thread_id')->nullable());
        $this->addColumnIfMissing('forum_posts', 'user_id', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable());
        $this->addColumnIfMissing('forum_posts', 'body', fn (Blueprint $table) => $table->text('body')->nullable());
        $this->addColumnIfMissing('forum_posts', 'is_hidden', fn (Blueprint $table) => $table->boolean('is_hidden')->default(false));
        $this->addColumnIfMissing('forum_posts', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('forum_posts', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }
};

