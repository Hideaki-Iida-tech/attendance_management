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

        $isAdminContext = $request->attributes->get('is_admin_context', false);

        $tabValue = $request->query('page');

        if (is_null($tabValue) || $tabValue === '') {
            $status = ApplicationStatus::PENDING->value;
        } elseif ($tabValue === ApplicationStatus::PENDING->name) {
            $status = ApplicationStatus::PENDING->value;
        } elseif ($tabValue === ApplicationStatus::APPROVED->name) {
            $status = ApplicationStatus::APPROVED->value;
        }

        // 管理者の場合
        if ($isAdminContext) {

            $layout = 'layouts.admin-menu';

            $attendanceChangeRequests = AttendanceChangeRequest::with('user')
                ->where('status', $status)
                ->orderBy('created_at', 'asc')
                ->get();

            return view('applications.admin.index', compact(
                'layout',
                'attendanceChangeRequests',
                'status'
            ));

            //一般ユーザーの場合
        } else {
            $layout = 'layouts.user-menu';



            $attendanceChangeRequests = AttendanceChangeRequest::with('user')
                ->where('user_id', $userId)
                ->where('status', $status)
                ->orderBy('created_at', 'asc')
                ->get();

            return view('applications.index', compact(
                'layout',
                'attendanceChangeRequests',
                'status'
            ));
        }
    }
}
