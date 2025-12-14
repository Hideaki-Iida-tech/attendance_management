<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/login', function () {
    return view('auth.login');
});

Route::get('/register', function () {
    return view('auth.register');
});

Route::get('/email/verify', function () {
    return view('auth.verify-email');
});

Route::get('/admin/login', function () {
    return view('auth.admin.login');
});

Route::get('/attendance', function () {
    $layout = 'layouts.user-menu';
    $status = 3; // 0:勤務外 1:出勤中 2:休憩中 3:退勤済
    return view('attendance.create', compact('layout', 'status'));
});

Route::get('/attendance/list', function () {
    $layout = 'layouts.user-menu';
    return view('attendance.index', compact('layout'));
});

/*Route::get('/stamp_correction_request/list', function () {
    $layout = 'layouts.user-menu';
    return view('applications.index', compact('layout'));
});*/

Route::get('/attendance/{id}', function () {
    $layout = 'layouts.user-menu';
    return view('attendance.show', compact('layout'));
});

Route::get('/admin/attendance/list', function () {
    $layout = 'layouts.admin-menu';
    return view('attendance.admin.index', compact('layout'));
});

/*Route::get('/attendance/{id}', function () {
    $layout = 'layouts.admin-menu';
    return view('attendance.admin.show', compact('layout'));
});*/

Route::get('/admin/staff/list', function () {
    $layout = 'layouts.admin-menu';
    return view('staff.admin.index', compact('layout'));
});

Route::get('stamp_correction_request/list', function () {
    $layout = 'layouts.admin-menu';
    return view('applications.admin.index', compact('layout'));
});

Route::get('admin/attendance/staff/{id}', function () {
    $layout = 'layouts.admin-menu';
    return view('attendance/admin/staff', compact('layout'));
});

Route::get('stamp_correction_request/approve/{attendance_correct_request}', function () {
    $layout = 'layouts.admin-menu';
    $isApproved = false;
    return view('applications/admin/approve', compact('layout', 'isApproved'));
});

Route::prefix('admin')->name('admin')->group(function () {
    // 管理者としてログインしていない場合
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'login'])->name('login.store');
    });

    // 管理者としてログインしている場合のログアウト処理
    Route::post('/logout', [AdminLoginController::class, 'logout'])->middleware('auth:admin')->name('logout');

    // /にアクセスした場合
    Route::middleware('auth:admin')->group(function () {
        Route::get('/', []);
    });
});
