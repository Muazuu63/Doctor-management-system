<?php

use App\Http\Controllers\CronController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\Staff\AuthController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\MedicineController;
use App\Http\Controllers\Staff\PatientController;
use App\Http\Controllers\Staff\PrescriptionController;
use App\Http\Controllers\Staff\QueueController;
use App\Http\Controllers\Staff\ScheduleController;
use App\Http\Controllers\Staff\SettingsController;
use App\Http\Controllers\Staff\SmsController;
use App\Http\Controllers\Staff\UserController;
use App\Http\Controllers\Staff\WalkinController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/about', [PublicSiteController::class, 'about'])->name('about');
Route::get('/book', [PublicSiteController::class, 'book'])->name('book');
Route::post('/book', [PublicSiteController::class, 'storeBooking'])->middleware('throttle:10,1')->name('book.store');
Route::get('/ticket/{token}', [PublicSiteController::class, 'ticket'])->name('ticket');
Route::get('/status', [PublicSiteController::class, 'status'])->name('status');
Route::get('/portal', [PublicSiteController::class, 'portal'])->name('portal');
Route::post('/portal', [PublicSiteController::class, 'portalLogin'])->middleware('throttle:8,1')->name('portal.login');
Route::get('/portal/logout', [PublicSiteController::class, 'portalLogout'])->name('portal.logout');
Route::get('/display', [PublicSiteController::class, 'display'])->name('display');
Route::get('/prescription/{prescription}', [PublicSiteController::class, 'prescription'])->name('prescription.show');
Route::get('/cron/remind', [CronController::class, 'remind'])->name('cron.remind');

Route::prefix('staff')->name('staff.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:8,1')->name('login.attempt');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/2fa', [AuthController::class, 'showTwoFactor'])->name('2fa.challenge');
        Route::post('/2fa', [AuthController::class, 'verifyTwoFactor'])->middleware('throttle:10,1')->name('2fa.verify');
        Route::post('/2fa/resend', [AuthController::class, 'resendOtp'])->name('2fa.resend');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });

    Route::middleware(['auth', '2fa'])->group(function () {
        Route::get('/', fn () => redirect()->route('staff.dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/delay', [DashboardController::class, 'delay'])->name('delay');

        Route::get('/queue', [QueueController::class, 'index'])->name('queue');
        Route::post('/queue/{appointment}', [QueueController::class, 'update'])->name('queue.update');

        Route::get('/walkin', [WalkinController::class, 'create'])->name('walkin');
        Route::post('/walkin', [WalkinController::class, 'store'])->name('walkin.store');

        Route::get('/prescribe/{appointment}', [PrescriptionController::class, 'edit'])->name('prescribe');
        Route::post('/prescribe/{appointment}', [PrescriptionController::class, 'store'])->name('prescribe.store');

        Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');
        Route::post('/schedule', [ScheduleController::class, 'store'])->middleware('role:super_admin,admin')->name('schedule.store');
        Route::post('/schedule/exception', [ScheduleController::class, 'storeException'])->middleware('role:super_admin,admin')->name('schedule.exception');
        Route::post('/schedule/exception/{exception}/delete', [ScheduleController::class, 'destroyException'])->middleware('role:super_admin,admin')->name('schedule.exception.delete');

        Route::get('/patients', [PatientController::class, 'index'])->name('patients');
        Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
        Route::post('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');

        Route::get('/medicines', [MedicineController::class, 'index'])->name('medicines');
        Route::post('/medicines', [MedicineController::class, 'store'])->name('medicines.store');

        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::post('/security', [SettingsController::class, 'updateSecurity'])->name('security.update');

        Route::middleware('role:super_admin,admin')->group(function () {
            Route::get('/sms', [SmsController::class, 'index'])->name('sms');
            Route::post('/sms', [SmsController::class, 'update'])->name('sms.update');
            Route::post('/sms/test', [SmsController::class, 'test'])->name('sms.test');
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        });

        Route::middleware('role:super_admin')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::post('/users/{user}', [UserController::class, 'update'])->name('users.update');
        });
    });
});
