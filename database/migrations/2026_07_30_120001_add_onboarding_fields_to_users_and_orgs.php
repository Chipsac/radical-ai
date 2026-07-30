<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('onboarding_completed_at')->nullable()->after('remember_token');
            $table->unsignedTinyInteger('onboarding_step')->default(1)->after('onboarding_completed_at');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('industry')->nullable()->after('plan_tier');
            $table->string('size_range')->nullable()->after('industry');
            $table->string('billing_status')->default('active')->after('size_range');
            $table->timestamp('trial_ends_at')->nullable()->after('billing_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['onboarding_completed_at', 'onboarding_step']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['industry', 'size_range', 'billing_status', 'trial_ends_at']);
        });
    }
};
