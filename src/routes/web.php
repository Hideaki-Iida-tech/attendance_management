<?php

use Illuminate\Support\Facades\Route;

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
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.login');
});

Route::get('/register', function () {
    return view('auth.register');
});

Route::get('email/verify', function () {
    return view('auth.verify-email');
});

Route::get('admin/login', function () {
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

Route::get('/stamp_correction_request/list', function () {
    $layout = 'layouts.user-menu';
    return view('applications.index', compact('layout'));
});

Route::get('attendance/{id}', function () {
    $layout = 'layouts.user-menu';
    return view('attendance.show', compact('layout'));
});
