<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceChangeRequestController;
use App\Http\Controllers\UserApplicationController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
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

// 一般ユーザー用ログイン画面の表示ルート
Route::get('/', function () {
    return view('auth.login');
});

// 一般ユーザー用ログイン画面の表示ルート
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// 一般ユーザーの会員登録画面表示ルート
Route::get('/register', function () {
    return view('auth.register');
});

// メール認証誘導画面表示用ルート
Route::get('/email/verify', function () {
    return view('auth.verify-email');
});

// 管理者用ログイン画面表示ルート
Route::get('/admin/login', function () {
    return view('auth.admin.login');
});

// 一般ユーザーの場合の画面表示ルート
Route::middleware(['auth', 'user', 'verified'])->group(function () {

    // 勤怠登録画面（一般ユーザー）の表示ルート
    Route::get('/attendance', [AttendanceController::class, 'showAttendanceForm'])->name('attendance.index');

    // 出退勤のスタンプ機能
    Route::post('/attendance', [AttendanceController::class, 'stamp']);

    // 勤怠一覧画面（一般ユーザー）の表示ルート
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.list');
});

// パスにadmin/が含まれている場合のルート（すべて管理者の場合）
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'verified'])->group(function () {

    // 勤怠一覧画面（管理者）の表示ルート
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index']);

    // スタッフ一覧画面（管理者）の表示ルート
    Route::get('/staff/list', [AdminStaffController::class, 'index']);

    // スタッフ別勤怠一覧画面（管理者）の表示ルート
    Route::get('/attendance/staff/{id}', function () {
        $layout = 'layouts.admin-menu';
        return view('attendance/admin/staff', compact('layout'));
    });

    // 勤怠詳細画面（管理者）から呼び出されるPOST（修正機能用のルート）
    Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])->where('id', '[0-9]+');
});

// パスにadmin/が含まれていない場合のルート（すべて管理者の場合）
Route::middleware(['auth', 'admin', 'verified'])->group(function () {

    // 修正申請承認画面（管理者）の表示ルート
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', function () {
        $layout = 'layouts.admin-menu';
        $isApproved = false;
        return view('applications/admin/approve', compact('layout', 'isApproved'));
    })->where('attendance_correct_request', '[0-9]+');
});

// 管理者かどうかによって、勤怠詳細画面（管理者/一般ユーザー）の表示を切り替えるルート
Route::middleware(['auth', 'role.view', 'verified'])->group(
    function () {
        Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->where('id', '[0-9]+');
        Route::post('/attendance/{id}', [AttendanceChangeRequestController::class, 'store'])->where('id', '[0-9]+');
    }
);

// 管理者かどうかによって、申請一覧画面（管理者/一般ユーザー）の表示を切り替えるルート
Route::middleware(['auth', 'role.view', 'verified'])->get('/stamp_correction_request/list', [UserApplicationController::class, 'index']);

// 認証必須 & まだ未認証ユーザーが来るページ（通知画面）
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

// 署名付きリンクからの検証（メールの[Verify]ボタンが叩くURL）
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    /** @var \App\Models\User $user */
    $user = auth()->user();
    // 管理者でログインしている場合
    if ($user->isAdmin()) {
        return redirect('/admin/attendance/list')->with('verified', true); // 勤怠一覧画面（管理者）にリダイレクト
        // 一般ユーザーでログインしている場合
    } else {
        // 勤怠登録画面（一般ユーザー）にリダイレクト
        return redirect('/attendance')->with('verified', true);
    }
})->middleware(['auth', 'signed'])->name('verification.verify');

// 検証メールの再送信
Route::get('/email/verification-notification', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return back();
    }
    $request->user()->sendEmailVerificationNotification();
    return back();
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
