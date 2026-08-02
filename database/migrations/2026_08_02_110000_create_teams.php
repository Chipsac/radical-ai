<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Teams become the single grouping concept.
 *
 * Before this there were two half-built ideas: a `departments` table nothing
 * read outside the seeder, and a free-text `department` string on each
 * employee. This migration folds both into `teams`, carrying the existing
 * department names across so nothing is lost, and drops the dead table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained('users')->nullOnDelete();

            // The coverage rule: how many people must be at work on any given
            // working day. 0 turns the check off for this team.
            $table->unsignedSmallInteger('minimum_cover')->default(1);

            $table->timestamps();
            $table->unique(['organization_id', 'slug']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('department')
                ->constrained()->nullOnDelete();
        });

        $this->migrateDepartmentsIntoTeams();

        // The old table was never read by application code; everything it
        // held is now represented by teams.
        Schema::dropIfExists('departments');
    }

    /**
     * Turn each distinct department name into a team, then point employees at
     * the matching team. The `department` string stays for now as a fallback
     * label — dropping a column people may still be reading is a separate,
     * reversible decision.
     */
    private function migrateDepartmentsIntoTeams(): void
    {
        $rows = DB::table('employees')
            ->select('organization_id', 'department')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->get();

        foreach ($rows as $row) {
            $teamId = DB::table('teams')->insertGetId([
                'organization_id' => $row->organization_id,
                'name' => $row->department,
                'slug' => Str::slug($row->department) ?: 'team',
                'minimum_cover' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('employees')
                ->where('organization_id', $row->organization_id)
                ->where('department', $row->department)
                ->update(['team_id' => $teamId]);
        }
    }

    public function down(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
        });

        Schema::dropIfExists('teams');
    }
};
