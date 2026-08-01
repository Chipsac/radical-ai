<?php

use App\Support\Modules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Which product modules this user may open. Null is treated as
            // "not yet decided" and resolves to the role default.
            $table->json('modules')->nullable()->after('role');
        });

        Schema::table('invitations', function (Blueprint $table) {
            // Modules can be pre-granted at invite time, so a new joiner has
            // a usable workspace the moment they accept.
            $table->json('modules')->nullable()->after('role');
        });

        // Existing users keep everything they had before this change —
        // introducing access control must not lock anyone out.
        DB::table('users')->update(['modules' => json_encode(Modules::ALL)]);
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn('modules');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('modules');
        });
    }
};
