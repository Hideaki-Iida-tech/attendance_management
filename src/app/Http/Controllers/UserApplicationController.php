<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserApplicationIndexRequest;
use App\Models\AttendanceChangeRequest;
use App\Enums\ApplicationStatus;

class UserApplicationController extends Controller
{
    /** 
     * 一般ユーザー/管理者用申請一覧画面を表示するメソッド。
     *
     * @param UserApplicationRequest $request
     * @return \Illuminate\View\View
     */
    public function index(UserApplicationIndexRequest $request)
    {
        $userId = auth()->user()->id;

        $isAdminContext = (bool)$request->attributes->get('is_admin_context', false);

        $tabValue = $request->query('tab');

        if (is_null($tabValue) || $tabValue === '') {
            $status = ApplicationStatus::PENDING->value;
        } elseif ($tabValue === ApplicationStatus::PENDING->name) {
            $status = ApplicationStatus::PENDING->value;
        } elseif ($tabValue === ApplicationStatus::APPROVED->name) {
            $status = ApplicationStatus::APPROVED->value;
        } else {
            // 想定外の値は安全側にフォールバック
            $status = ApplicationStatus::PENDING->value;
        }

        $layout = $isAdminContext ? 'layouts.admin-menu' : 'layouts.user-menu';

        $view = $isAdminContext ? 'applications.admin.index' : 'applications.index';

        $query = AttendanceChangeRequest::query()
            ->with('user')
            ->where('status', $status)
            ->orderBy('created_at', 'asc');

        // 一般ユーザーの場合
        if (!$isAdminContext) {
            $query->where('user_id', $userId);
        }

        $attendanceChangeRequests = $query->get();

        return view(
            $view,
            compact(
                'layout',
                'attendanceChangeRequests',
                'status'
            )
        );
    }
}
