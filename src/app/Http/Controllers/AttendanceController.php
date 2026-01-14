<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceStampRequest;
use App\Http\Requests\AttendanceIndexRequest;
use App\Http\Requests\AttendanceShowRequest;
use App\Enums\AttendanceState;
use App\Models\Attendance;
use App\Models\AttendanceChangeRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面を表示する。
     *
     * ログイン中ユーザーの当日の勤怠状態を判定し、
     * 状態に応じた画面表示用データを生成して勤怠登録ビューを返す。
     *
     * AttendanceState が FINISHED の場合は、
     * 当日の勤怠レコードを取得し、勤務日および退勤時刻を表示用に整形する。
     * 勤怠レコードが存在しない場合は、退勤時刻として現在時刻を設定する。
     *
     * 画面側での未定義変数エラーを防ぐため、
     * 勤務日および退勤時刻は初期値として null を設定した上で view に渡す。
     *
     * @return \Illuminate\View\View
     */
    public function showAttendanceForm()
    {
        $layout = 'layouts.user-menu';
        $state = $this->attendanceStateResolver();

        // 初期化
        $workDate = null;
        $clockOutAt = null;

        if ($state === AttendanceState::FINISHED) {
            $attendance = Attendance::where('user_id', auth()->user()->id)
                ->whereDate('work_date', today())
                ->first();
            $workDate = today()->translatedFormat('Y年n月j日(D)');
            if ($attendance) {
                $clockOutAt = optional($attendance->clock_out_at)->format('H:i');
            } else {
                $clockOutAt = now()->format('H:i');
            }
        }

        return view('attendance.create', compact(
            'layout',
            'state',
            'workDate',
            'clockOutAt',
        ));
    }

    /**
     * 勤怠の打刻処理を行う。
     *
     * リクエストで指定された action（出勤・退勤・休憩開始・休憩終了）に応じて、
     * 現在の勤怠状態を判定し、許可された操作のみを実行する。
     *
     * 不正な状態遷移（例：勤務外での退勤、休憩中以外での休憩終了など）の場合は、
     * 対応する処理を実行せず、画面をリダイレクトする。
     *
     * action に想定外の値が指定された場合は、400 Bad Request を返す。
     *
     * @param \App\Http\Requests\AttendanceStampRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function stamp(AttendanceStampRequest $request)
    {
        $validated = $request->validated('action');
        $action = $validated['action']; // 'clock_in'など
        switch ($action) {
            case 'clock_in':
                if ($this->attendanceStateResolver() === AttendanceState::OFF_DUTY) {
                    $this->clockIn();
                }
                break;
            case 'clock_out':
                if ($this->attendanceStateResolver() === AttendanceState::WORKING) {
                    $this->clockOut();
                }
                break;
            case 'break_start':
                if ($this->attendanceStateResolver() === AttendanceState::WORKING) {
                    $this->breakStart();
                }
                break;
            case 'break_end':
                if ($this->attendanceStateResolver() === AttendanceState::ON_BREAK) {
                    $this->breakEnd();
                }
                break;
            default:
                abort(400, 'Invalid action');
        }
        return redirect()->back();
    }

    /**
     * ログイン中ユーザーの当日の勤怠状態を判定する。
     *
     * 勤怠テーブルおよび休憩情報を基に、
     * 当日の勤怠状態を AttendanceState（OFF_DUTY / WORKING / ON_BREAK / FINISHED）
     * のいずれかとして判定・返却する。
     *
     * 判定は以下の優先順で行う：
     * - OFF_DUTY（勤務外）
     * - WORKING（勤務中）
     * - ON_BREAK（休憩中）
     * - FINISHED（勤務終了）
     *
     * いずれの条件にも該当しない場合は、
     * 安全側のフォールバックとして OFF_DUTY を返す。
     * 
     * 未ログイン状態で呼び出された場合は、
     * 不正なリクエストとして 400 Bad Request を返す。
     *
     * @return \App\Enums\AttendanceState
     */
    private function attendanceStateResolver(): AttendanceState
    {

        if (!auth()->check()) {
            abort(400, 'Invalid action');
        }

        $user = auth()->user();
        $userId = $user->id;

        if (Attendance::isOffDuty($userId, Carbon::parse(today()))) {
            return attendanceState::OFF_DUTY;
        }

        if (Attendance::isWorking($userId, Carbon::parse(today()))) {
            return attendanceState::WORKING;
        }

        if (Attendance::isOnBreak($userId, Carbon::parse(today()))) {
            return attendanceState::ON_BREAK;
        }

        if (Attendance::isFinished($userId, Carbon::parse(today()))) {
            return attendanceState::FINISHED;
        }

        return AttendanceState::OFF_DUTY;
    }

    /**
     * 出勤打刻を行う。
     *
     * ログイン中ユーザーの当日の勤怠レコードを新規作成し、
     * 出勤時刻（clock_in_at）を現在時刻で登録する。
     *
     * 未ログイン状態で呼び出された場合は、
     * 不正なリクエストとして 400 Bad Request を返す。
     * 
     * データベース処理中に例外が発生した場合は、
     * 例外を握りつぶさずにログへ記録し、呼び出し元へは伝播させない。
     *
     * @return void
     */
    private function clockIn()
    {

        if (!auth()->check()) {
            abort(400, 'Invalid action');
        }

        try {
            $data = [
                'user_id' => auth()->user()->id,
                'work_date' => today(),
                'clock_in_at' => now(),
            ];
            Attendance::create($data);
        } catch (Exception $e) {
            Log::error('DB処理で例外が発生', [
                'exception' => $e,
            ]);
        }
    }

    /**
     * 退勤打刻を行う。
     *
     * ログイン中ユーザーの当日の勤怠レコードを取得し、
     * 存在する場合にのみ退勤時刻（clock_out_at）を現在時刻で更新する。
     *
     * 当日の勤怠レコードが存在しない場合は、
     * 何も処理を行わずにメソッドを終了する。
     *
     * 未ログイン状態で呼び出された場合は、
     * 不正なリクエストとして 400 Bad Request を返す。
     * 
     * データベース処理中に例外が発生した場合は、
     * 例外内容をログへ記録し、呼び出し元へは伝播させない。
     *
     * @return void
     */
    private function clockOut()
    {

        if (!auth()->check()) {
            abort(400, 'Invalid action');
        }

        try {
            $attendance = Attendance::where('user_id', auth()->user()->id)
                ->whereDate('work_date', today())->first();

            if (!$attendance) {
                return;
            }

            $data = [
                'clock_out_at' => now(),
            ];
            $attendance->update($data);
        } catch (Exception $e) {
            Log::error('DB処理で例外が発生', [
                'exception' => $e,
            ]);
        }
    }

    /**
     * 休憩開始打刻を行う。
     *
     * ログイン中ユーザーの当日の勤怠レコードを取得し、
     * 存在する場合にのみ休憩開始時刻（break_start_at）を
     * breaks テーブルへ新規登録する。
     *
     * 当日の勤怠レコードが存在しない場合は、
     * 何も処理を行わずにメソッドを終了する。
     *
     * 未ログイン状態で呼び出された場合は、
     * 不正なリクエストとして 400 Bad Request を返す。
     * 
     * データベース処理中に例外が発生した場合は、
     * 例外内容をログへ記録し、呼び出し元へは伝播させない。
     *
     * @return void
     */
    private function breakStart()
    {

        if (!auth()->check()) {
            abort(400, 'Invalid action');
        }

        try {
            $attendance = Attendance::with('breaks')
                ->where('user_id', auth()->user()->id)
                ->whereDate('work_date', today())->first();

            if (!$attendance) {
                return;
            }

            $data = ['break_start_at' => now()];
            $attendance->breaks()->create($data);
        } catch (Exception $e) {
            Log::error('DB処理で例外が発生', [
                'exception' => $e,
            ]);
        }
    }

    /**
     * 休憩終了打刻を行う。
     *
     * ログイン中ユーザーの当日の勤怠レコードを取得し、
     * 休憩開始時刻は存在し、休憩終了時刻が未設定の
     * 最新の休憩レコードを対象として休憩終了時刻を更新する。
     *
     * 当日の勤怠レコード、または対象となる進行中の休憩レコードが
     * 存在しない場合は、何も処理を行わずにメソッドを終了する。
     *
     * 未ログイン状態で呼び出された場合は、
     * 不正なリクエストとして 400 Bad Request を返す。
     * 
     * データベース処理中に例外が発生した場合は、
     * 例外内容をログへ記録し、呼び出し元へは伝播させない。
     *
     * @return void
     */
    private function breakEnd()
    {

        if (!auth()->check()) {
            abort(400, 'Invalid action');
        }

        try {
            $attendance = Attendance::where('user_id', auth()->user()->id)
                ->whereDate('work_date', today())->first();

            if (!$attendance) {
                return;
            }

            $break = $attendance->breaks()
                ->whereNotNull('break_start_at')
                ->whereNull('break_end_at')
                ->orderByDesc('break_start_at')
                ->first();

            if (!$break) {
                return;
            }

            $data = ['break_end_at' => now()];
            $break->update($data);
        } catch (Exception $e) {
            Log::error('DB処理で例外が発生', [
                'exception' => $e,
            ]);
        }
    }

    /**
     * 一般ユーザー向けの勤怠一覧画面を表示する。
     *
     * クエリパラメータ `month`（Y-m 形式）を受け取り、
     * 指定された年月の勤怠データを一覧表示する。
     * `month` が未指定の場合は、現在の年月を対象とする。
     *
     * 表示対象の年月に対して、
     * - 前月 / 次月の年月文字列を生成
     * - 月初〜月末の日付一覧を作成
     * - ログインユーザーの勤怠情報を日付単位でマッピング
     *
     * @param  \App\Http\Requests\Attendance\AttendanceIndexRequest  $request
     * @return \Illuminate\Contracts\View\View
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *         未ログイン時に不正なアクセスが行われた場合
     */
    public function index(AttendanceIndexRequest $request)
    {
        $layout = 'layouts.user-menu';

        $user = auth()->user();
        $userId = $user->id;

        $month = $request->query('month'); // "2026-01" or null

        $current = $month ?
            Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        $yearMonth = $current->format('Y/m');

        $prevMonth = $current->copy()->subMonth()->format('Y-m');
        $nextMonth = $current->copy()->addMonth()->format('Y-m');

        $baseDate = Carbon::parse($current); // 表示したい年月
        $startOfMonth = $baseDate->copy()->startOfMonth();
        $endOfMonth = $baseDate->copy()->endOfMOnth();

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

        return view('attendance.index', compact(
            'layout',
            'yearMonth',
            'prevMonth',
            'nextMonth',
            'dates'
        ));
    }

    public function show(AttendanceShowRequest $request)
    {
        $attendance = Attendance::with('breaks', 'user')
            ->findOrFail($request->route('id'));

        $isAdminContext = (bool)$request->attributes->get('is_admin_context', false);

        if ($isAdminContext) {
            $layout = 'layouts.admin-menu';

            $isPending = AttendanceChangeRequest::existsPending($attendance->id);

            return view(
                'attendance.admin.show',
                compact('layout', 'attendance', 'isPending')
            );
        } else {
            $layout = 'layouts.user-menu';

            $pendingRequest = null;

            $isPending = AttendanceChangeRequest::existsPending($attendance->id);
            if ($isPending) {
                $pendingRequest =
                    AttendanceChangeRequest::where('attendance_id', $attendance->id)->first();
            }

            $editable = !$isPending;

            return view(
                'attendance.show',
                compact(
                    'layout',
                    'attendance',
                    'pendingRequest',
                    'editable',
                )
            );
        }
    }
}
