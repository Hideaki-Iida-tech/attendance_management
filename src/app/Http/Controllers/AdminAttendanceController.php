<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceIndexRequest;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Http\Requests\AdminStaffMonthlyAttendanceIndexRequest;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\AttendanceChangeRequest;
use App\Enums\ApplicationStatus;
use App\Enums\ActionType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Support\Collection;

class AdminAttendanceController extends Controller
{
    /**
     * 管理者用 勤怠一覧画面を表示する。
     *
     * クエリパラメータ `day`（Y-m-d形式）で指定された日付、
     * または未指定の場合は当日を基準日として、
     * 該当日の勤怠情報をユーザー・休憩情報とともに取得する。
     *
     * - ユーザー情報を eager load することで N+1 問題を回避
     * - 休憩情報は break_start_at 昇順で取得
     * - 一覧は user_id 昇順で表示
     * - 前日・翌日遷移用の日付も併せて生成
     *
     * @param  \App\Http\Requests\AdminAttendanceIndexRequest  $request
     * @return \Illuminate\View\View
     */
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

        $attendances = Attendance::with(
            [
                'user',
                'breaks' => fn($query) => $query->orderBy('break_start_at')
            ]
        )
            ->whereDate('work_date', $current)
            ->orderBy('user_id')
            ->get();

        return view('attendance.admin.index', compact(
            'layout',
            'attendances',
            'yearMonthDay',
            'titleYearMonthDay',
            'preDay',
            'nextDay',
        ));
    }

    /**
     * 管理者用：特定スタッフの月次勤怠一覧を表示する。
     *
     * ルートパラメータで指定されたスタッフ（user）について、
     * クエリパラメータ `month`（Y-m形式）で指定された月、
     * または未指定の場合は当月を対象として勤怠情報を取得する。
     *
     * 対象月の全日付を生成し、各日付に対して該当する勤怠データを
     * 紐付けることで、勤怠が存在しない日も含めた月次一覧を構築する。
     *
     * - 勤怠データは work_date 昇順で取得
     * - 前月・翌月遷移用の月情報も併せて生成
     *
     * @param  \App\Http\Requests\AdminStaffMonthlyAttendanceIndexRequest  $request
     * @return \Illuminate\View\View
     */
    public function staffMonthlyIndex(AdminStaffMonthlyAttendanceIndexRequest $request)
    {
        $layout = 'layouts.admin-menu';

        $user = User::findOrFail($request->route('id'));
        $userId = $user->id;

        $target = $this->resolveTargetMonth($request);

        $startOfMonth = $target['start'];
        $endOfMonth = $target['end'];
        $current = $target['current'];

        $yearMonth = $current->format('Y/m');
        $currentMonth = $current->format('Y-m');

        $prevMonth = $current->copy()->subMonth()->format('Y-m');
        $nextMonth = $current->copy()->addMonth()->format('Y-m');

        $dates = $this->buildMonthlyDates($startOfMonth, $endOfMonth);

        $attendanceMap = $this->getMonthlyAttendanceMap($userId, $startOfMonth, $endOfMonth);

        $dates = $this->attachAttendancesToDates($dates, $attendanceMap);

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

    /**
     * 管理者用：特定スタッフの月次勤怠データを CSV 形式でエクスポートする。
     *
     * ルートパラメータで指定されたスタッフについて、
     * クエリパラメータ `month`（Y-m形式）で指定された月、
     * または未指定の場合は当月を対象として勤怠データを取得する。
     *
     * 対象月の全日付を生成し、各日付に対応する勤怠情報を紐付けた上で、
     * CSV 形式のストリームレスポンスとして出力する。
     *
     * - 勤怠が存在しない日も含めて出力
     * - CSV は UTF-8（BOM付き）で生成し、Excelでの文字化けを防止
     * - ダウンロード完了検知用に Cookie（csv_downloaded）を付与
     *
     * @param  \App\Http\Requests\AdminStaffMonthlyAttendanceIndexRequest  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportStaffMonthlyCsv(AdminStaffMonthlyAttendanceIndexRequest $request)
    {
        $user = User::findOrFail($request->route('id'));
        $userId = $user->id;

        $target = $this->resolveTargetMonth($request);

        $currentMonth = $target['month'];
        $startOfMonth = $target['start'];
        $endOfMonth = $target['end'];

        $dates = $this->buildMonthlyDates($startOfMonth, $endOfMonth);

        $attendanceMap = $this->getMonthlyAttendanceMap($userId, $startOfMonth, $endOfMonth);

        $dates = $this->attachAttendancesToDates($dates, $attendanceMap);

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

    /**
     * 勤務日と時刻文字列から Carbon の日時オブジェクトを生成する。
     *
     * 勤務日（workDate）に対して、"H:i" 形式の時刻文字列を結合し、
     * "Y-m-d H:i" 形式の日時として Carbon インスタンスを生成する。
     *
     * 時刻が未指定（null または空文字）の場合は null を返す。
     * 時刻形式の妥当性は FormRequest にて事前にバリデーションされている前提。
     *
     * @param  string|null  $time     "H:i" 形式の時刻文字列（例: "09:00"）
     * @param  \Carbon\Carbon  $workDate 勤務日
     * @return \Carbon\Carbon|null
     */
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

    /**
     * 月指定クエリ（month=Y-m）から対象月情報を解決する。
     *
     * - month があればその月の月初を基準日として採用
     * - month がなければ当月の月初を採用
     * - 返り値は CSV/画面で使いやすいように current/start/end/月文字列をまとめる
     *
     * ※ month の妥当性（date_format:Y-m）は FormRequest 側で担保する前提
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array{
     *   current:\Carbon\Carbon,
     *   start:\Carbon\Carbon,
     *   end:\Carbon\Carbon,
     *   month:string
     * }
     */
    private function resolveTargetMonth(Request $request): array
    {
        $month = $request->query('month'); // "2026-01" or null

        $current = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        $startOfMonth = $current->copy()->startOfMonth();
        $endOfMonth   = $current->copy()->endOfMonth();

        return [
            'current' => $current,                     // 月初(Carbon)
            'start'   => $startOfMonth,                // 月初(Carbon)
            'end'     => $endOfMonth,                  // 月末(Carbon)
            'month'   => $current->format('Y-m'),       // "2026-01"
        ];
    }


    /**
     * 指定された期間（月内想定）の全日付リストを生成する。
     *
     * 各要素は view/CSV で扱いやすいように
     * - date: "Y-m-d"
     * - label: "m/d(D)"（例: "01/01(木)"）
     * を持つ配列として返す。
     *
     * @param  \Carbon\Carbon  $start  期間開始日（通常は月初）
     * @param  \Carbon\Carbon  $end    期間終了日（通常は月末）
     * @return \Illuminate\Support\Collection<int, array{date:string, label:string}>
     */
    private function buildMonthlyDates(Carbon $start, Carbon $end): Collection
    {
        return collect(CarbonPeriod::create($start->copy()->startOfMonth(), $end->copy()->endOfMonth()))
            ->map(fn(Carbon $date) => [
                'date'  => $date->toDateString(),
                'label' => $date->translatedFormat('m/d(D)'),
            ]);
    }

    /**
     * 指定ユーザーの対象期間（月内想定）の勤怠を取得し、
     * work_date（Y-m-d）をキーにしたマップ（連想配列）にして返す。
     *
     * 例: "2026-01-05" => Attendanceモデル
     *
     * ※ work_date は日付単位で一意（ユーザー×日付で1件）である前提。
     *
     * @param  int|string     $userId 対象ユーザーID
     * @param  \Carbon\Carbon $start  期間開始日（通常は月初）
     * @param  \Carbon\Carbon $end    期間終了日（通常は月末）
     * @return \Illuminate\Support\Collection<string, \App\Models\Attendance>
     */
    private function getMonthlyAttendanceMap(int|string $userId, Carbon $start, Carbon $end): Collection
    {
        $attendances = Attendance::query()
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        return $attendances->keyBy(
            fn(Attendance $attendance) => $attendance->work_date->toDateString()
        );
    }

    /**
     * 日付配列に勤怠情報を紐付ける。
     *
     * 日付ごとの配列（date, label）に対して、
     * work_date をキーとした勤怠マップから該当日の勤怠を取得し、
     * 'attendance' 要素として追加する。
     *
     * 勤怠が存在しない日は attendance に null が設定される。
     *
     * @param  \Illuminate\Support\Collection<int, array{date:string, label:string}>  $dates
     * @param  \Illuminate\Support\Collection<string, \App\Models\Attendance>         $attendanceMap
     * @return \Illuminate\Support\Collection<int, array{date:string, label:string, attendance:?Attendance}>
     */
    private function attachAttendancesToDates(
        Collection $dates,
        Collection $attendanceMap
    ): Collection {
        return $dates->map(function (array $date) use ($attendanceMap) {
            return $date + [
                'attendance' => $attendanceMap->get($date['date']),
            ];
        });
    }
}
