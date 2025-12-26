<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

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
Route::middleware(['auth', 'verified'])->group(function () {

    // 勤怠登録画面（一般ユーザー）の表示ルート
    Route::get('/attendance', function () {
        $layout = 'layouts.user-menu';
        $status = 3; // 0:勤務外 1:出勤中 2:休憩中 3:退勤済
        return view('attendance.create', compact('layout', 'status'));
    })->name('attendance.index');

    // 勤怠一覧画面（一般ユーザー）の表示ルート
    Route::get('/attendance/list', function () {
        $layout = 'layouts.user-menu';
        return view('attendance.index', compact('layout'));
    })->name('attendance.list');
});

// パスにadmin/が含まれている場合のルート（すべて管理者の場合）
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'verified'])->group(function () {

    // 勤怠一覧画面（管理者）の表示ルート
    Route::get('/attendance/list', function () {
        $layout = 'layouts.admin-menu';
        return view('attendance.admin.index', compact('layout'));
    });

    // スタッフ一覧画面（管理者）の表示ルート
    Route::get('/staff/list', function () {
        $layout = 'layouts.admin-menu';
        return view('staff.admin.index', compact('layout'));
    });

    // スタッフ別勤怠一覧画面（管理者）の表示ルート
    Route::get('/attendance/staff/{id}', function () {
        $layout = 'layouts.admin-menu';
        return view('attendance/admin/staff', compact('layout'));
    });
});

// パスにadmin/が含まれていない場合のルート（すべて管理者の場合）
Route::middleware(['auth', 'admin', 'verified'])->group(function () {

    // 修正申請承認画面（管理者）の表示ルート
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', function () {
        $layout = 'layouts.admin-menu';
        $isApproved = false;
        return view('applications/admin/approve', compact('layout', 'isApproved'));
    });
});

// 管理者かどうかによって、勤怠詳細画面（管理者/一般ユーザー）の表示を切り替えるルート
Route::middleware(['auth', 'role.view', 'verified'])->get('/attendance/{id}', fn() => abort(500));

// 管理者かどうかによって、申請一覧画面（管理者/一般ユーザー）の表示を切り替えるルート
Route::middleware(['auth', 'role.view', 'verified'])->get('/stamp_correction_request/list', fn() => abort(500));

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
