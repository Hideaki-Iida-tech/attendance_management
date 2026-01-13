<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Enums\UserRole;

class AdminStaffController extends Controller
{
    public function index()
    {
        $users = User::where('role', UserRole::USER)->get();
        $layout = 'layouts.admin-menu';
        return view('staff.admin.index', compact('layout', 'users'));
    }
}
