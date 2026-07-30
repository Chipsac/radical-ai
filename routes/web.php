<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskProgressController;
use Illuminate\Support\Facades\Route;

// Demo login helper for local tooling and prototype preview
if (app()->environment('local', 'testing')) {
    Route::post('/demo-login/{role}', DemoLoginController::class)->name('demo.login');
    Route::get('/demo-login/{role}', DemoLoginController::class);
}

Route::middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', fn () => redirect()->route('home'))->name('dashboard');

    // ---- CRM ----------------------------------------------------------
    Route::prefix('crm')->name('crm.')->group(function () {
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
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'dashboard'])->name('dashboard');
        Route::get('/board', [TaskController::class, 'board'])->name('board');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])
            ->middleware('role:admin|manager')->name('categories.store');
        Route::get('/{task}', [TaskController::class, 'show'])->name('show');
        Route::patch('/{task}/status', [TaskController::class, 'updateStatus'])->name('status');
        Route::post('/{task}/updates', [TaskProgressController::class, 'store'])->name('updates.store');
        Route::post('/{task}/time', [TaskController::class, 'logTime'])->name('time.store');
    });

    // ---- HR -----------------------------------------------------------
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::patch('/leave/{leaveRequest}/decide', [LeaveController::class, 'decide'])
            ->middleware('role:admin|manager')->name('leave.decide');
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
        Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
        Route::post('/payslips', [PayslipController::class, 'store'])
            ->middleware('role:admin')->name('payslips.store');
        Route::get('/payslips/{payslip}/download', [PayslipController::class, 'download'])->name('payslips.download');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
    });

    // ---- Reports & settings ------------------------------------------
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware('role:admin')->name('settings.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
