<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table): void {
            if (!Schema::hasColumn('achievements', 'is_system')) {
                $table->boolean('is_system')->default(true)->after('xp_reward');
            }
            if (!Schema::hasColumn('achievements', 'course_id')) {
                $table->unsignedBigInteger('course_id')->nullable()->after('is_system');
                $table->index('course_id', 'achievements_course_id_idx');
            }
            if (!Schema::hasColumn('achievements', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('course_id');
                $table->index('created_by', 'achievements_created_by_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table): void {
            if (Schema::hasColumn('achievements', 'created_by')) {
                $table->dropIndex('achievements_created_by_idx');
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('achievements', 'course_id')) {
                $table->dropIndex('achievements_course_id_idx');
                $table->dropColumn('course_id');
            }
            if (Schema::hasColumn('achievements', 'is_system')) {
                $table->dropColumn('is_system');
            }
        });
    }
};

