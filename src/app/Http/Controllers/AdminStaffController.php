<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;

class AdminStaffController extends Controller
{
    /**
     * 管理者用のスタッフ一覧画面を表示する。
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = User::where('role', UserRole::USER)->orderBy('id')->get();
        $layout = 'layouts.admin-menu';
        return view('staff.admin.index', compact('layout', 'users'));
    }
}
