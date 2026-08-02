<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Self-referencing parent. Nesting is unbounded by design; the
            // Task model guards against a task becoming its own ancestor,
            // which is the failure mode that actually matters.
            $table->foreignId('parent_id')
                ->nullable()
                ->after('project_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            // Denormalised depth, so listings can indent without walking the
            // tree for every row. Maintained by the model on save.
            $table->unsignedSmallInteger('depth')->default(0)->after('parent_id');

            $table->index(['organization_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'parent_id']);
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('depth');
        });
    }
};
