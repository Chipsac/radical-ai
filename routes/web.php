<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveOverviewController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskProgressController;
use App\Http\Controllers\TeamAccessController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

// Demo login helper for local tooling and prototype preview
if (app()->environment('local', 'testing')) {
    Route::post('/demo-login/{role}', DemoLoginController::class)->name('demo.login');
    Route::get('/demo-login/{role}', DemoLoginController::class);
}

// ---- Public marketing site --------------------------------------------
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::post('/contact', [LandingController::class, 'contact'])
    ->middleware('throttle:10,60')->name('contact.store');

// Referenced by the signup consent checkbox and the landing page, so they
// must resolve — a consent tickbox pointing at nothing is not consent.
Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');

// ---- Public invitation acceptance (no tenant context yet) --------------
Route::get('/invitations/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');
Route::post('/invitations/{token}', [InvitationController::class, 'complete'])->name('invitations.complete');

// ---- Health probes for Cloud Run --------------------------------------
// /healthz is the liveness probe and checks nothing external, so a database
// blip cannot cause every instance to be restarted mid-incident.
Route::get('/healthz', [HealthController::class, 'live'])->name('health');
// /health is the deep check, for readiness and uptime monitoring.
Route::get('/health', [HealthController::class, 'ready'])
    ->middleware('throttle:30,1')->name('health.ready');

// Everything past this point requires a verified email address (step 2 of the
// customer journey). The verification screens themselves live in auth.php.
Route::middleware(['auth', 'verified'])->group(function () {
    // ---- Onboarding wizard --------------------------------------------
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [OnboardingController::class, 'show'])->name('show');
        Route::post('/personal', [OnboardingController::class, 'savePersonal'])->name('personal');
        Route::post('/organisation', [OnboardingController::class, 'saveOrganisation'])->name('organisation');
        Route::post('/team', [OnboardingController::class, 'saveTeam'])->name('team');
        Route::get('/skip', [OnboardingController::class, 'skip'])->name('skip');
    });

    // ---- Contextual help ----------------------------------------------
    Route::post('/help/seen', [HelpController::class, 'markSeen'])->name('help.seen');
    Route::get('/help/articles', [HelpController::class, 'articles'])->name('help.articles');
    Route::post('/help/reset', [HelpController::class, 'reset'])->name('help.reset');

    // ---- Two-factor -----------------------------------------------------
    Route::prefix('two-factor')->name('two-factor.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Auth\TwoFactorController::class, 'show'])->name('show');
        Route::post('/', [\App\Http\Controllers\Auth\TwoFactorController::class, 'confirm'])->name('confirm');
        Route::delete('/', [\App\Http\Controllers\Auth\TwoFactorController::class, 'destroy'])->name('destroy');
        Route::post('/recovery-codes', [\App\Http\Controllers\Auth\TwoFactorController::class, 'regenerateRecoveryCodes'])
            ->name('recovery');
    });

    // ---- Team & invitations --------------------------------------------
    Route::get('/team', [InvitationController::class, 'index'])
        ->middleware('role:admin|manager')->name('team.index');
    Route::post('/team/invitations', [InvitationController::class, 'store'])
        ->middleware('role:admin|manager')->name('invitations.store');
    Route::delete('/team/invitations/{invitation}', [InvitationController::class, 'destroy'])
        ->middleware('role:admin|manager')->name('invitations.destroy');

    // Step 10 — granting feature access
    Route::patch('/team/{user}/modules', [TeamAccessController::class, 'updateModules'])
        ->middleware('role:admin|manager')->name('team.modules');
    Route::patch('/team/{user}/role', [TeamAccessController::class, 'updateRole'])
        ->middleware('role:admin|manager')->name('team.role');

    // The signed-in workspace. `/` belongs to the public landing page now.
    Route::get('/dashboard', [HomeController::class, 'index'])->name('home');
    Route::get('/app', fn () => redirect()->route('home'))->name('dashboard');

    // ---- CRM ----------------------------------------------------------
    Route::prefix('crm')->name('crm.')->middleware('module:crm')->group(function () {
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::get('/deals', [DealController::class, 'index'])->name('deals.index');
        Route::post('/deals', [DealController::class, 'store'])->name('deals.store');
        Route::get('/deals/{deal}', [DealController::class, 'show'])->name('deals.show');
        Route::patch('/deals/{deal}/stage', [DealController::class, 'updateStage'])->name('deals.stage');
        Route::post('/deals/{deal}/win', [DealController::class, 'markWon'])->name('deals.win');
    });

    // ---- Tasks --------------------------------------------------------
    Route::prefix('tasks')->name('tasks.')->middleware('module:tasks')->group(function () {
        Route::get('/', [TaskController::class, 'dashboard'])->name('dashboard');
        Route::get('/board', [TaskController::class, 'board'])->name('board');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])
            ->middleware('role:admin|manager')->name('categories.store');
        Route::get('/{task}', [TaskController::class, 'show'])->name('show');
        Route::post('/{task}/subtasks', [TaskController::class, 'storeSubtask'])->name('subtasks.store');
        Route::patch('/{task}/move', [TaskController::class, 'move'])->name('move');
        Route::patch('/{task}/status', [TaskController::class, 'updateStatus'])->name('status');
        Route::post('/{task}/updates', [TaskProgressController::class, 'store'])->name('updates.store');
        Route::post('/{task}/time', [TaskController::class, 'logTime'])->name('time.store');
    });

    // ---- HR -----------------------------------------------------------
    Route::prefix('hr')->name('hr.')->middleware('module:hr')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::get('/leave/overview', [LeaveOverviewController::class, 'index'])
            ->middleware('role:admin|manager')->name('leave.overview');
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::patch('/leave/{leaveRequest}/decide', [LeaveController::class, 'decide'])
            ->middleware('role:admin|manager')->name('leave.decide');
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');

        // ---- Teams ------------------------------------------------------
        Route::get('/teams', [TeamController::class, 'index'])
            ->middleware('role:admin|manager')->name('teams.index');
        Route::post('/teams', [TeamController::class, 'store'])
            ->middleware('role:admin|manager')->name('teams.store');
        Route::patch('/teams/{team}', [TeamController::class, 'update'])
            ->middleware('role:admin|manager')->name('teams.update');
        Route::delete('/teams/{team}', [TeamController::class, 'destroy'])
            ->middleware('role:admin|manager')->name('teams.destroy');
        Route::patch('/teams/members/{employee}', [TeamController::class, 'assign'])
            ->middleware('role:admin|manager')->name('teams.assign');
        Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
        Route::post('/payslips', [PayslipController::class, 'store'])
            ->middleware('role:admin')->name('payslips.store');
        Route::get('/payslips/{payslip}/download', [PayslipController::class, 'download'])->name('payslips.download');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
    });

    // ---- Reports & settings ------------------------------------------
    // Reports are built from task progress, so they follow the tracker's access.
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('module:tasks')->name('reports.index');
    Route::post('/reports/generate', [ReportController::class, 'generate'])
        ->middleware('module:tasks')->name('reports.generate');
    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware('role:admin')->name('settings.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
