<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Organizations become real tenants -------------------------
        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('slug')->constrained('users')->nullOnDelete();
            $table->string('industry')->nullable()->after('plan_tier');
            $table->string('team_size')->nullable()->after('industry');
            $table->string('country', 2)->nullable()->after('team_size');
            $table->string('onboarding_step')->default('profile')->after('country');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
            $table->timestamp('trial_ends_at')->nullable()->after('onboarding_completed_at');
            $table->softDeletes();
        });

        // ---- Enterprise auth on users ----------------------------------
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->json('ui_state')->nullable(); // dismissed tips, completed tours
        });

        // ---- Team invitations ------------------------------------------
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role')->default('employee');
            $table->string('job_title')->nullable();
            $table->string('token', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'email']);
        });

        // ---- Audit trail -------------------------------------------------
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('invitations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
                'last_login_at', 'last_login_ip', 'ui_state',
            ]);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn([
                'industry', 'team_size', 'country',
                'onboarding_step', 'onboarding_completed_at', 'trial_ends_at', 'deleted_at',
            ]);
        });
    }
};
