<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceChangeRequest;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Enums\ApplicationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Enums\ActionType;

class AttendanceChangeRequestController extends Controller
{
    public function store(AttendanceUpdateRequest $request)
    {
        $attendance = Attendance::with('breaks')
            ->findOrFail($request->route('id'));

        if ($attendance->user_id !== auth()->user()->id) {
            return redirect('/attendance/list');
        }

        $requestItem = AttendanceChangeRequest::where(
            'attendance_id',
            $attendance->id
        )->with('attendance', 'breaks')->first();

        // 既に承認待ちがあるなら更新させない
        if ($requestItem && $requestItem->status === ApplicationStatus::PENDING) {
            return back()->withErrors([
                'error' => 'この勤怠は現在承認待ちの申請があるため、修正申請を更新できません。',
            ]);
        }

        // トランザクション開始
        DB::beginTransaction();

        try {
            // 申請ヘッダーに保存するデータをセット
            $headerData = [
                'user_id' => auth()->user()->id,
                'attendance_id' => $attendance->id,
                'work_date' => $attendance->work_date,
                'status' => ApplicationStatus::PENDING,
                'reason' => $request->input('reason'),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_comment' => null,
            ];

            $targetRequest = $requestItem ? tap($requestItem)->update($headerData) :
                AttendanceChangeRequest::create($headerData);

            // 出退勤時刻関係のデータ処理
            $clockInAt = $this->toDateTime(
                $request->input('clock_in_at'),
                $attendance->work_date
            );
            $clockOutAt = $this->toDateTime(
                $request->input('clock_out_at'),
                $attendance->work_date
            );

            // 出退勤時刻のデータをセット
            $attendanceData = [
                'new_clock_in_at' => $clockInAt,
                'new_clock_out_at' => $clockOutAt,
                'old_clock_in_at' => $attendance->clock_in_at,
                'old_clock_out_at' => $attendance->clock_out_at,
            ];

            // 出退勤時刻をDBに保存
            if ($clockInAt && $clockOutAt) {
                $targetRequest->attendance()->updateOrCreate(
                    [
                        'request_id' => $targetRequest->id
                    ],
                    $attendanceData
                );
            }

            // 申請に紐づく休憩明細を一旦すべて削除
            $targetRequest->breaks()->delete();

            // 休憩開始・終了時刻関係の処理
            $breakInputs = $request->input('breaks', []);
            $existingBreaks = $attendance->breaks->keyBy('id');

            foreach ($breakInputs as $row) {
                $start = $this->toDateTime(
                    $row['start'] ?? null,
                    $attendance->work_date
                );

                $end = $this->toDateTime(
                    $row['end'] ?? null,
                    $attendance->work_date
                );

                $hasNone = !$start && !$end;


                // 片方だけ入力は無視
                // ここに来る時点で「片方だけ入力」は存在しない
                //（フォームリクエストですでに弾かれている）
                // 念のためスキップ
                if (!$start xor !$end) {
                    continue;
                }

                if ($hasNone) {
                    if (!empty($row['id'])) {
                        $action = ActionType::DELETE;
                    } else {
                        continue; // 新規枠で未入力は何もしない
                    }
                } else {
                    // 両方入力　→　UPDATE or CREATE
                    $action = empty($row['id']) ?
                        ActionType::ADD :
                        ActionType::UPDATE;
                }

                $old = !empty($row['id']) ? $existingBreaks->get((int)$row['id']) : null;

                $breakData = [
                    'action' => $action,
                    'target_break_id' => $row['id'] ?? null,
                    'new_break_start_at' => $start,
                    'new_break_end_at' => $end,
                    'old_break_start_at' => $old?->break_start_at,
                    'old_break_end_at' => $old?->break_end_at,
                ];

                $targetRequest->breaks()->create($breakData);
            }

            // トランザクションを確定
            DB::commit();

            return redirect()->back();
        } catch (\Exception $e) {
            // DBをロールバック
            DB::rollback();
            Log::error('DB処理で例外が発生', [
                'exception' => $e,
            ]);
            return back()->withErrors(['error' => '申請の保存に失敗しました。もう一度お試しください。']);
        }
    }

    private function toDateTime(?string $time, Carbon $workDate): ?Carbon
    {
        if (!$time) {
            return null;
        }

        // "H:i" を想定。FormRequestでバリデーションしておく前提
        return Carbon::createFromFormat(
            'Y-m-d H:i',
            $workDate->toDateString() . ' ' . $time
        );
    }
}
