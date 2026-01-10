<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserApplicationIndexRequest;
use App\Models\AttendanceChangeRequest;
use App\Enums\ApplicationStatus;

class UserApplicationController extends Controller
{
    public function index(UserApplicationIndexRequest $request)
    {
        $userId = auth()->user()->id;

        $isAdminContext = $request->input('isAdminContext');

        // 管理者の場合
        if ($isAdminContext) {
            //一般ユーザーの場合
        } else {
            $layout = 'layouts.user-menu';

            $tabValue = $request->input('page');

            if (is_null($tabValue) || $tabValue === '') {
                $status = ApplicationStatus::PENDING->value;
            } elseif ($tabValue === ApplicationStatus::PENDING->name) {
                $status = ApplicationStatus::PENDING->value;
            } elseif ($tabValue === ApplicationStatus::APPROVED->name) {
                $status = ApplicationStatus::APPROVED->value;
            }

            $attendanceChangeRequests = AttendanceChangeRequest::with('user')
                ->where('user_id', $userId)
                ->where('status', $status)
                ->orderBy('created_at', 'asc')
                ->get();

            return view('applications.index', compact('layout', 'attendanceChangeRequests', 'status'));
        }
    }
}
