<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Supervisor;
use App\Http\Controllers\Company;
use App\Http\Controllers\Intern;

// ─── Public ────────────────────────────────────────────────
Route::get('/', [MarketplaceController::class, 'index'])->name('home');
require __DIR__.'/auth.php';

// ─── Redirect dashboard sesuai role ────────────────────────
Route::get('/dashboard', function () {
    return match (Auth::user()->role) {
        'admin'      => redirect()->route('admin.dashboard'),
        'intern'     => redirect()->route('intern.dashboard'),
        'user'       => redirect()->route('intern.dashboard'),
        'supervisor' => redirect()->route('supervisor.dashboard'),
        'company'    => redirect()->route('company.dashboard'),
        default      => redirect()->route('profile.edit'),
    };
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifikasi
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::post('/subscriptions/checkout', 'App\Http\Controllers\SubscriptionController@checkout')->name('subscriptions.checkout');
});

Route::post('/payments/midtrans/callback', 'App\Http\Controllers\MidtransCallbackController')->name('payments.midtrans.callback');

// ─── Admin ─────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', Admin\UserController::class);
    Route::patch('users/{user}/toggle-status', [Admin\UserController::class, 'toggleStatus'])
         ->name('users.toggle-status');
    Route::resource('supervisors', Admin\SupervisorController::class);
    Route::resource('companies', Admin\CompanyController::class);
});

Route::prefix('intern')->name('intern.')->middleware(['auth', 'role:intern,user'])->group(function () {
    Route::get('/dashboard', [Intern\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('applications', Intern\ApplicationController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::get('reports/export', [Intern\ReportController::class, 'export'])->name('reports.export');
    Route::resource('reports', Intern\ReportController::class)
        ->parameters(['reports' => 'dailyReport']);

    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [Intern\AttendanceController::class, 'index'])->name('index');
        Route::get('/today', [Intern\AttendanceController::class, 'today'])->name('today');
        Route::get('/export', [Intern\AttendanceController::class, 'export'])->name('export');
        Route::get('/{attendance}', [Intern\AttendanceController::class, 'show'])->name('show');
        Route::post('/checkin', [Intern\AttendanceController::class, 'checkin'])->name('checkin');
        Route::post('/checkout', [Intern\AttendanceController::class, 'checkout'])->name('checkout');
        Route::post('/leave', [Intern\AttendanceController::class, 'leave'])->name('leave');
    });

    Route::prefix('internship')->name('internship.')->group(function () {
        Route::get('/', [Intern\InternshipController::class, 'show'])->name('show');
        Route::get('/documents', [Intern\InternshipController::class, 'documents'])->name('documents');
        Route::post('/documents', [Intern\InternshipController::class, 'uploadDocument'])->name('documents.upload');
        Route::get('/documents/{document}/download', [Intern\InternshipController::class, 'downloadDocument'])->name('documents.download');
        Route::get('/evaluation', [Intern\InternshipController::class, 'evaluation'])->name('evaluation');
    });
});

// ─── Pembimbing ────────────────────────────────────────────
Route::prefix('supervisor')->name('supervisor.')->middleware(['auth', 'role:supervisor', 'throttle:200,1'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [Supervisor\DashboardController::class, 'index'])->name('dashboard');

    // Daftar intern bimbingan
    Route::resource('internships', Supervisor\InternshipController::class)
         ->only(['index', 'show']);

    // Verifikasi laporan harian
    Route::get('reports/export', [Supervisor\ReportController::class, 'export'])->name('reports.export');
    Route::resource('reports', Supervisor\DailyReportController::class)
         ->only(['index', 'show'])
         ->parameters(['reports' => 'dailyReport']);
    Route::post('reports/{dailyReport}/approve',  [Supervisor\DailyReportController::class, 'approve'])->name('reports.approve');
    Route::post('reports/{dailyReport}/revision', [Supervisor\DailyReportController::class, 'requestRevision'])->name('reports.revision');
    Route::post('reports/bulk-approve',           [Supervisor\DailyReportController::class, 'bulkApprove'])->name('reports.bulk-approve');

    // Penilaian akhir
    Route::get('evaluations',                          [Supervisor\EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('evaluations/{internship}/create',      [Supervisor\EvaluationController::class, 'create'])->name('evaluations.create');
    Route::post('evaluations/{internship}',            [Supervisor\EvaluationController::class, 'store'])->name('evaluations.store');
    Route::get('evaluations/{evaluation}',             [Supervisor\EvaluationController::class, 'show'])->name('evaluations.show');
    Route::get('evaluations/{evaluation}/edit',        [Supervisor\EvaluationController::class, 'edit'])->name('evaluations.edit');
    Route::put('evaluations/{evaluation}',             [Supervisor\EvaluationController::class, 'update'])->name('evaluations.update');

    // Verifikasi dokumen
    Route::resource('documents', Supervisor\DocumentController::class)
         ->only(['index', 'show']);
    Route::get('documents/{document}/download',  [Supervisor\DocumentController::class, 'download'])->name('documents.download');
    Route::post('documents/{document}/approve',  [Supervisor\DocumentController::class, 'approve'])->name('documents.approve');
    Route::post('documents/{document}/reject',   [Supervisor\DocumentController::class, 'reject'])->name('documents.reject');
});

// ─── Perusahaan ────────────────────────────────────────────
Route::prefix('company')->name('company.')->middleware(['auth', 'role:company'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [Company\DashboardController::class, 'index'])->name('dashboard');

    // Company profile
    Route::get('/profile', [Company\CompanyController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [Company\CompanyController::class, 'update'])->name('profile.update');

    Route::middleware('company.verified')->group(function () {
        // Manajemen program magang
        Route::resource('programs', Company\ProgramController::class);
        Route::post('programs/{program}/close', [Company\ProgramController::class, 'close'])->name('programs.close');
        Route::patch('programs/{program}/feature', [Company\ProgramController::class, 'feature'])
            ->middleware('premium:company')
            ->name('programs.feature');

        // Seleksi lamaran
        Route::resource('applications', Company\ApplicationController::class)
             ->only(['index', 'show']);
        Route::post('applications/{application}/accept',   [Company\ApplicationController::class, 'accept'])->name('applications.accept');
        Route::post('applications/{application}/reject',   [Company\ApplicationController::class, 'reject'])->name('applications.reject');
        Route::post('applications/bulk-reject',            [Company\ApplicationController::class, 'bulkReject'])->name('applications.bulk-reject');

        // Pantau peserta magang
        Route::resource('internships', Company\InternshipController::class)
             ->only(['index', 'show']);
        Route::post('internships/{internship}/terminate', [Company\InternshipController::class, 'terminate'])->name('internships.terminate');
        Route::post('internships/{internship}/complete',  [Company\InternshipController::class, 'complete'])->name('internships.complete');

        // Penilaian peserta
        Route::get('evaluations',                     [Company\EvaluationController::class, 'index'])->name('evaluations.index');
        Route::get('evaluations/{internship}/create', [Company\EvaluationController::class, 'create'])->name('evaluations.create');
        Route::post('evaluations/{internship}',       [Company\EvaluationController::class, 'store'])->name('evaluations.store');
        Route::get('evaluations/{evaluation}',        [Company\EvaluationController::class, 'show'])->name('evaluations.show');
        Route::get('evaluations/{evaluation}/edit',   [Company\EvaluationController::class, 'edit'])->name('evaluations.edit');
        Route::put('evaluations/{evaluation}',        [Company\EvaluationController::class, 'update'])->name('evaluations.update');
    });
});
