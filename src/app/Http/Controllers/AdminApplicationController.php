<?php

namespace App\Http\Controllers;

use App\Models\AttendanceChangeRequest;
use App\Models\Attendance;
use App\Http\Requests\AdminApplicationShowRequest;
use App\Http\Requests\AdminApplicationUpdateRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\ActionType;
use App\Enums\ApplicationStatus;

class AdminApplicationController extends Controller
{
    /**
     * 修正申請承認画面を表示する（管理者用）。
     *
     * パスパラメータで渡された勤怠修正申請IDを基に、
     * 対象の申請レコードおよび関連するユーザー・勤怠・休憩情報を取得し、
     * 管理者向けの承認／確認画面を表示する。
     *
     * 併せて、表示対象の勤怠修正申請が
     * 既に「承認済み」状態であるかを判定し、
     * 画面上の操作制御（承認ボタンの表示可否など）に利用する。
     *
     * 申請レコードが存在しない場合は 404 エラーを返す。
     *
     * @param  AdminApplicationShowRequest  $request  管理者用勤怠修正申請詳細表示リクエスト
     * @return \Illuminate\View\View
     */
    public function show(AdminApplicationShowRequest $request)
    {
        $layout = 'layouts.admin-menu';
        $requestId = $request->route('attendance_correct_request');
        $attendanceChangeRequest = AttendanceChangeRequest
            ::with('user', 'attendance', 'breaks')
            ->findOrFail($requestId);
        $isApproved = $attendanceChangeRequest->isApproved();
        return view('applications.admin.approve', compact(
            'layout',
            'attendanceChangeRequest',
            'isApproved'
        ));
    }

    /**
     * 勤怠修正申請を承認し、勤怠情報へ反映する（管理者用）。
     *
     * 指定された勤怠修正申請に対して、申請内容（出退勤時刻・休憩情報）を
     * 元の勤怠データへ反映した上で、申請ステータスを「承認済み」に更新する。
     *
     * 既に承認済みの申請については処理を行わず、
     * エラーメッセージを付与して元の画面へリダイレクトする。
     *
     * 処理はトランザクション内で実行し、
     * 承認処理の競合（同一申請の二重承認）を防止するため、
     * 対象の勤怠修正申請レコードに対して行ロック（lockForUpdate）を行う。
     *
     * 申請に含まれる休憩情報については、
     * 追加・更新・削除の各アクション種別に応じて元勤怠の休憩データを更新する。
     *
     * 途中で例外が発生した場合は全ての変更をロールバックし、
     * 承認処理失敗のエラーメッセージを表示する。
     *
     * @param  AdminApplicationUpdateRequest  $request  管理者用勤怠修正申請承認リクエスト
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(AdminApplicationUpdateRequest $request)
    {

        $requestId = $request->route('attendance_correct_request');
        $attendanceChangeRequest = AttendanceChangeRequest
            ::with('attendance', 'breaks')
            ->findOrFail($requestId);
        $isApproved = $attendanceChangeRequest->isApproved();
        if ($isApproved) {
            return redirect()->back()->withErrors(['error' => 'すでに承認済みです。']);
        }

        $attendance = Attendance::with('breaks')
            ->findOrFail($attendanceChangeRequest->attendance_id);

        // トランザクション開始
        DB::beginTransaction();

        try {

            // 二重承認の防止のため、lockForUpdate()を実行
            $attendanceChangeRequest = AttendanceChangeRequest::query()
                ->whereKey($requestId)
                ->lockForUpdate()
                ->with('attendance', 'breaks')
                ->firstOrFail();


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
