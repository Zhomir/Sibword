<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('moderation_actions')) {
            Schema::create('moderation_actions', function (Blueprint $table): void {
                $table->id();
                $table->string('entity_type', 64);
                $table->unsignedBigInteger('entity_id');
                $table->text('reason')->nullable();
                $table->string('status', 32)->default('pending');
                $table->unsignedBigInteger('reporter_user_id')->nullable();
                $table->unsignedBigInteger('moderator_user_id')->nullable();
                $table->text('resolution_note')->nullable();
                $table->timestamps();

                $table->index(['entity_type', 'entity_id', 'status'], 'moderation_entity_status_idx');
                $table->index(['status', 'created_at'], 'moderation_status_created_idx');
            });
            return;
        }

        $this->addColumnIfMissing('moderation_actions', 'entity_type', fn (Blueprint $table) => $table->string('entity_type', 64)->nullable());
        $this->addColumnIfMissing('moderation_actions', 'entity_id', fn (Blueprint $table) => $table->unsignedBigInteger('entity_id')->nullable());
        $this->addColumnIfMissing('moderation_actions', 'reason', fn (Blueprint $table) => $table->text('reason')->nullable());
        $this->addColumnIfMissing('moderation_actions', 'status', fn (Blueprint $table) => $table->string('status', 32)->default('pending'));
        $this->addColumnIfMissing('moderation_actions', 'reporter_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('reporter_user_id')->nullable());
        $this->addColumnIfMissing('moderation_actions', 'moderator_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('moderator_user_id')->nullable());
        $this->addColumnIfMissing('moderation_actions', 'resolution_note', fn (Blueprint $table) => $table->text('resolution_note')->nullable());
        $this->addColumnIfMissing('moderation_actions', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('moderation_actions', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
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
};

