<?php

namespace App\Http\Controllers;

use App\Models\AttendanceChangeRequest;
use App\Models\Attendance;
use App\Http\Requests\AdminApplicationShowRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\ActionType;
use App\Enums\ApplicationStatus;

class AdminApplicationController extends Controller
{
    public function show(AdminApplicationShowRequest $request)
    {
        $layout = 'layouts.admin-menu';
        $requestId = $request->route('attendance_correct_request');
        $attendanceChangeRequest = AttendanceChangeRequest
            ::with('user', 'attendance', 'breaks')
            ->findOrFail($requestId);
        $isApproved = AttendanceChangeRequest::isApproved(
            $attendanceChangeRequest->attendance_id
        );
        return view('applications/admin/approve', compact(
            'layout',
            'attendanceChangeRequest',
            'isApproved'
        ));
    }

    public function update(AdminApplicationShowRequest $request)
    {

        $requestId = $request->route('attendance_correct_request');
        $attendanceChangeRequest = AttendanceChangeRequest
            ::with('attendance', 'breaks')
            ->findOrFail($requestId);
        $isApproved = $attendanceChangeRequest->status === ApplicationStatus::APPROVED;

        if ($isApproved) {
            return redirect()->back()->withErrors(['error' => 'すでに承認済みです。']);
        }

        $attendance = Attendance::with('breaks')
            ->findOrFail($attendanceChangeRequest->attendance_id);

        // トランザクション開始
        DB::beginTransaction();

        try {

            $attendanceData = [
                'clock_in_at' => $attendanceChangeRequest->attendance->new_clock_in_at,
                'clock_out_at' => $attendanceChangeRequest->attendance->new_clock_out_at,
            ];

            $attendance->update($attendanceData);

            foreach ($attendanceChangeRequest->breaks as $break) {
                $breakData = [
                    'break_start_at' => $break->new_break_start_at,
                    'break_end_at' => $break->new_break_end_at,
                ];
                $targetBreak = $attendance->breaks->where('id', $break->target_break_id)->first();

                if (in_array($break->action, [ActionType::UPDATE, ActionType::DELETE], true)) {
                    if (!$targetBreak) {
                        throw new \RuntimeException('Target break not found: ' . $break->target_break_id);
                    }
                }

                if ($break->action === ActionType::UPDATE) {
                    $targetBreak->update($breakData);
                } elseif ($break->action === ActionType::DELETE) {
                    $targetBreak->delete();
                } elseif ($break->action === ActionType::ADD) {
                    $attendance->breaks()->create($breakData);
                }
            }

            $changeRequestData = [
                'status' => ApplicationStatus::APPROVED,
                'reviewed_by' => auth()->user()->id,
                'reviewed_at' => now(),
            ];

            $attendanceChangeRequest->update($changeRequestData);

            // トランザクションを確定
            DB::commit();
            return redirect()->back();
        } catch (Exception $e) {
            // DBをロールバック
            DB::rollback();
            Log::error('DB処理で例外が発生', [
                'exception' => $e,
            ]);
            return redirect()->back()->withErrors(['error' => '承認処理に失敗しました。もう一度お試しください。']);
        }
    }
}
