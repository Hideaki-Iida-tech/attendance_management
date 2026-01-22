<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceIndexRequest;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Http\Requests\AdminStaffMonthlyAttendanceIndexRequest;
use App\Models\Attendance;
use App\Models\User;
use App\Models\AttendanceChangeRequest;
use App\Enums\ApplicationStatus;
use App\Enums\ActionType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Cookie;

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

    public function staffMonthlyIndex(AdminStaffMonthlyAttendanceIndexRequest $request)
    {
        $layout = 'layouts.admin-menu';

        $user = User::findOrFail($request->route('id'));
        $userId = $user->id;

        $month = $request->query('month'); // "2026-01" or null

        $current = $month ?
            Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        $yearMonth = $current->format('Y/m');
        $currentMonth = $current->format('Y-m');

        $prevMonth = $current->copy()->subMonth()->format('Y-m');
        $nextMonth = $current->copy()->addMonth()->format('Y-m');

        $baseDate = Carbon::parse($current); // 表示したい年月
        $startOfMonth = $baseDate->copy()->startOfMonth();
        $endOfMonth = $baseDate->copy()->endOfMonth();

        $dates = collect(
            CarbonPeriod::create(
                $baseDate->copy()->startOfMonth(),
                $baseDate->copy()->endOfMonth()
            )
        )->map(function (Carbon $date) {
            return [
                'date' => $date->toDateString(),
                'label' => $date->translatedFormat('m/d(D)'),
            ];
        });

        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->orderBy('work_date')
            ->get();

        $attendanceMap = $attendances->keyBy(fn($key)
        => $key->work_date?->toDateString());

        $dates = $dates->map(function ($date) use ($attendanceMap) {
            $attendance = $attendanceMap->get($date['date']);

            return array_merge($date, [
                'attendance' => $attendance,
            ]);
        });

        return view('attendance/admin/staff', compact(
            'layout',
            'user',
            'yearMonth',
            'currentMonth',
            'prevMonth',
            'nextMonth',
            'dates',
        ));
    }

    public function exportStaffMonthlyCsv(AdminStaffMonthlyAttendanceIndexRequest $request)
    {
        $user = User::findOrFail($request->route('id'));
        $userId = $user->id;

        $month = $request->query('month'); // "2026-01" or null

        $current = $month ?
            Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        $currentMonth = $current->format('Y-m');

        $baseDate = Carbon::parse($current); // 表示したい年月
        $startOfMonth = $baseDate->copy()->startOfMonth();
        $endOfMonth = $baseDate->copy()->endOfMonth();

        $dates = collect(
            CarbonPeriod::create(
                $baseDate->copy()->startOfMonth(),
                $baseDate->copy()->endOfMonth()
            )
        )->map(function (Carbon $date) {
            return [
                'date' => $date->toDateString(),
                'label' => $date->translatedFormat('m/d(D)'),
            ];
        });

        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->orderBy('work_date')
            ->get();

        $attendanceMap = $attendances->keyBy(fn($key)
        => $key->work_date?->toDateString());

        $dates = $dates->map(function ($date) use ($attendanceMap) {
            $attendance = $attendanceMap->get($date['date']);

            return array_merge($date, [
                'attendance' => $attendance,
            ]);
        });

        // エクスポート先のファイル名を設定
        $filename = 'attendance_staff_' . $user->id . '_'
            . $currentMonth . '_' . now()->format('Ymd_His') . '.csv';

        // エクスポートするデータのヘッダを設定
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ];

        // CSV 用の「カラム定義」を配列で持つ
        $csvColumns = [
            'label' => '日付',
            'clock_in' => '出勤',
            'clock_out' => '退勤',
            'break' => '休憩',
            'total' => '合計',
        ];

        // $dates から「CSV1行分の配列」を作る
        $csvRows = $dates->map(function (array $row) {
            $attendance = $row['attendance'];

            return [
                'label' => $row['label'], // 01/01(木)など
                'clock_in' => $attendance?->clock_in_time, // アクセサ設定
                'clock_out' => $attendance?->clock_out_time, // アクセサ設定
                'break' => $attendance?->formated_break_time, // アクセサ設定
                'total' => $attendance?->formated_working_time, // アクセサ設定
            ];
        });

        $response = response()->streamDownload(function () use ($csvRows, $csvColumns) {

            $out = fopen('php://output', 'w');

            // Excel対策: UTF-8 BOM
            fwrite($out, "\xEF\xBB\xBF");

            // ヘッダ行（表示名）
            fputcsv($out, array_values($csvColumns));

            // データ行
            foreach ($csvRows as $row) {
                // 並び順を固定して出力
                $line = array_map(
                    fn($key) => data_get($row, $key),
                    array_keys($csvColumns)
                );

                fputcsv($out, $line);
            }

            fclose($out);
        }, $filename, $headers);

        // Cookie を直接セット
        $response->headers->setCookie(
            new Cookie(
                'csv_downloaded', // name
                '1', // value
                time() + 60, // expires(今から60秒後)
                '/', // path
                null, // domain
                false, // secure（localhostなら false）
                false, // httpOnly（JSで読むのでfalse）
                false, // raw
                'Lax' // sameSite         
            )
        );
        return $response;
    }

    public function update(AdminAttendanceUpdateRequest $request)
    {
        $attendance = Attendance::with('breaks')->findOrFail($request->route('id'));

        if (!$attendance) {
            return;
        };

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

            // 申請ヘッダーに保存するデータをセット
            $headerData = [
                'user_id' => $attendance->user_id,
                'attendance_id' => $attendance->id,
                'work_date' => $attendance->work_date,
                'status' => ApplicationStatus::APPROVED,
                'reason' => $request->input('reason'),
                'reviewed_by' => auth()->user()->id,
                'reviewed_at' => now(),
                'review_comment' => null,
            ];

            $targetRequest = $requestItem ? tap($requestItem)->update($headerData) :
                AttendanceChangeRequest::create($headerData);

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
                        $action = ActionType::DELETE;
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
                    $action = ActionType::ADD;
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
                    $action = ActionType::UPDATE;
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
            return back()->withErrors(['error' => '修正処理に失敗しました。もう一度お試しください。']);
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
