<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceIndexRequest;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminAttendanceController extends Controller
{
    public function index(AdminAttendanceIndexRequest $request)
    {
        $layout = 'layouts.admin-menu';

        $day = $request->query('day'); //"2026-01-01"

        $current = $day ?
            Carbon::createFromFormat('Y-m-d', $day)
            : now();

        $yearMonthDay = $current->format('Y/m/d');
        $titleYearMonthDay = $current->format('Y年m月d日');

        $preDay = $current->copy()->subDay()->format('Y-m-d');
        $nextDay = $current->copy()->addDay()->format('Y-m-d');

        $attendances = Attendance::with('user', 'breaks')
            ->whereDate('work_date', $current)->get();

        return view('attendance.admin.index', compact(
            'layout',
            'attendances',
            'yearMonthDay',
            'titleYearMonthDay',
            'preDay',
            'nextDay',
        ));
    }

    public function update(AdminAttendanceUpdateRequest $request)
    {
        $attendance = Attendance::with('breaks')->findOrFail($request->route('id'));

        if (!$attendance) {
            return;
        };

        // トランザクション開始
        DB::beginTransaction();

        try {

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
                'clock_in_at' => $clockInAt,
                'clock_out_at' => $clockOutAt,
            ];

            // 片方だけ入力はNGにするために例外を投げる
            if (!$clockInAt xor !$clockOutAt) {
                throw ValidationException::withMessages([
                    'clock_in_at' => '出勤・退勤は両方入力してください。',
                ]);
            }

            // 出退勤時刻を更新
            if ($clockInAt && $clockOutAt) {
                $attendance->update($attendanceData);
            }

            // 休憩開始・終了時刻関係の処理
            $breakInputs = $request->input('breaks', []);
            // 既存休憩をIDキーのマップに（更新対象の参照が速い）
            $existingBreaks = $attendance->breaks->keyBy('id');

            foreach ($breakInputs as $idx => $row) {
                $start = $this->toDateTime(
                    $row['start'] ?? null,
                    $attendance->work_date
                );

                $end = $this->toDateTime(
                    $row['end'] ?? null,
                    $attendance->work_date
                );

                $hasInput = $start && $end;
                $hasNone = !$start && !$end;

                // 片方だけ入力は例外を投げる
                if (!$start xor !$end) {
                    throw ValidationException::withMessages([
                        "breaks.$idx.start" => '休憩開始・終了は両方入力してください。',
                    ]);
                }

                $breakId = $row['id'] ?? null;

                // 両方未入力
                if ($hasNone) {
                    // フォームのidが存在する場合 → レコード削除
                    if ($breakId) {

                        $break = $existingBreaks->get((int)$breakId);

                        if (!$break) {
                            // 想定外＝不整合なので例外にしてロールバック
                            throw ValidationException::withMessages([
                                "breaks.$idx.start" => '指定された休憩データが見つかりません。'
                            ]);
                        }

                        // レコードを削除
                        $break->delete();
                    }

                    continue; // 新規枠で未入力は何もしない or 削除後は以下のコードをスキップ
                }

                // 両方入力　→　UPDATE or CREATE
                if (!$breakId) {
                    // レコード追加
                    $attendance->breaks()->create(
                        [
                            'break_start_at' => $start,
                            'break_end_at' => $end,
                        ]
                    );
                } else {

                    $break = $existingBreaks->get((int) $breakId);

                    if (!$break) {
                        throw ValidationException::withMessages([
                            "breaks.$idx.start" => '指定された休憩データが見つかりません。'
                        ]);
                    }
                    // レコード更新
                    $break->update(
                        [
                            'break_start_at' => $start,
                            'break_end_at' => $end,
                        ]
                    );
                }
            }

            // トランザクションを確定
            DB::commit();

            return redirect()->back();
        } catch (ValidationException $e) {
            // DBをロールバック
            DB::rollback();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            // DBをロールバック
            DB::rollback();
            Log::error('DB処理で例外が発生', [
                'exception' => $e,
            ]);
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
