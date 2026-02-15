<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceStampRequest;
use App\Http\Requests\AttendanceIndexRequest;
use App\Http\Requests\AttendanceShowRequest;
use App\Enums\AttendanceState;
use App\Enums\ApplicationStatus;
use App\Models\Attendance;
use App\Models\AttendanceChangeRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use RuntimeException;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面を表示する。
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
            $layout = 'layouts.attendance-menu-after-work';
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
     * @param \App\Http\Requests\AttendanceStampRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function stamp(AttendanceStampRequest $request)
    {
        $validated = $request->validated();
        $action = $validated['action']; // 'clock_in'など
        $state = $this->attendanceStateResolver();
        try {
            switch ($action) {
                case 'clock_in':
                    if ($state === AttendanceState::OFF_DUTY) {
                        $this->clockIn();
                    }
                    break;
                case 'clock_out':
                    if ($state === AttendanceState::WORKING) {
                        $this->clockOut();
                    }
                    break;
                case 'break_start':
                    if ($state === AttendanceState::WORKING) {
                        $this->breakStart();
                    }
                    break;
                case 'break_end':
                    if ($state === AttendanceState::ON_BREAK) {
                        $this->breakEnd();
                    }
                    break;
                default:
                    abort(400, 'Invalid action');
            }
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
        return redirect()->back()->with('message', '打刻しました。');
    }

    /**
     * ログイン中ユーザーの当日の勤怠状態を判定する。
     * 
     * @return \App\Enums\AttendanceState
     */
    private function attendanceStateResolver(): AttendanceState
    {
        $user = auth()->user();
        $userId = $user->id;

        if (Attendance::isOffDuty($userId, today())) {
            return AttendanceState::OFF_DUTY;
        }

        if (Attendance::isFinished($userId, today())) {
            return AttendanceState::FINISHED;
        }

        if (Attendance::isOnBreak($userId, today())) {
            return AttendanceState::ON_BREAK;
        }

        if (Attendance::isWorking($userId, today())) {
            return AttendanceState::WORKING;
        }

        return AttendanceState::OFF_DUTY;
    }

    /**
     * 出勤打刻を行う。
     *
     * @return void
     * @throws \RuntimeException
     */
    private function clockIn()
    {
        try {
            $data = [
                'user_id' => auth()->user()->id,
                'work_date' => today(),
                'clock_in_at' => now(),
            ];
            Attendance::create($data);
        } catch (QueryException $e) {
            // 例：user_id + work_date の UNIQUE 制約違反など
            $errorCode = (int)($e->errorInfo[1] ?? 0);

            Log::warning('clockIn DB error', [
                'user_id' => auth()->id(),
                'date' => (string)today(),
                'sql_error_code' => $errorCode,
                'message' => $e->getMessage(),
            ]);

            if ($errorCode === 1062) {
                // すでに出勤レコードがあるケース
                throw new RuntimeException('本日はすでに出勤打刻済みです。画面を更新して状態をご確認ください。', 0, $e);
            }

            throw new RuntimeException('出勤打刻に失敗しました。時間をおいて再度お試しください。', 0, $e);
        } catch (\Throwable $e) {
            Log::error('clockIn unexpected error', [
                'user_id' => auth()->id(),
                'date' => (string)today(),
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('出勤打刻に失敗しました。管理者に連絡してください。', 0, $e);
        }
    }

    /**
     * 退勤打刻を行う。
     *
     * @return void
     * @throws \RuntimeException
     */
    private function clockOut()
    {
        try {
            $attendance = Attendance::where('user_id', auth()->user()->id)
                ->whereDate('work_date', today())->first();

            if (!$attendance) {
                // 出勤していないのに退勤しようとしたケース
                throw new RuntimeException('本日は出勤打刻が行われていません。');
            }

            if (!is_null($attendance->clock_out_at)) {
                // すでに退勤済み
                throw new RuntimeException('本日はすでに退勤打刻済みです。');
            }

            $data = [
                'clock_out_at' => now(),
            ];
            $attendance->update($data);
        } catch (QueryException $e) {
            Log::warning('clockOut DB error', [
                'user_id' => auth()->id(),
                'date' => (string)today(),
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                '退勤打刻に失敗しました。時間をおいて再度お試しください。',
                0,
                $e
            );
        } catch (\Throwable $e) {
            Log::error('clockOut unexpected error', [
                'user_id' => auth()->id(),
                'date' => (string)today(),
                'message' => $e->getMessage(),
            ]);

            if ($e instanceof RuntimeException) {
                throw $e;
            }

            throw new RuntimeException(
                '退勤打刻に失敗しました。管理者に連絡してください。',
                0,
                $e
            );
        }
    }

    /**
     * 休憩開始打刻を行う。
     *
     * @return void
     * @throws \RuntimeException
     */
    private function breakStart()
    {
        try {
            $attendance = Attendance::with('breaks')
                ->where('user_id', auth()->user()->id)
                ->whereDate('work_date', today())->first();

            if (!$attendance) {
                // 出勤していない
                throw new RuntimeException('本日は出勤打刻が行われていません。');
            }

            if (is_null($attendance->clock_in_at)) {
                // レコードはあるが出勤時刻がない（不整合/想定外）
                throw new RuntimeException('出勤打刻が確認できません。画面を更新して状態をご確認ください。');
            }
            if (!is_null($attendance->clock_out_at)) {
                // 退勤済み
                throw new RuntimeException('すでに退勤済みのため、休憩開始できません。');
            }

            // 未終了の休憩がある＝すでに休憩中
            $hasOngoingBreak = $attendance->breaks->contains(
                fn($break) => !is_null($break->break_start_at) && is_null($break->break_end_at)
            );

            if ($hasOngoingBreak) {
                throw new RuntimeException('すでに中継中のため、休憩開始できません。');
            }

            $data = ['break_start_at' => now()];
            $attendance->breaks()->create($data);
        } catch (QueryException $e) {
            Log::warning('breakStart DB error', [
                'user_id' => auth()->id(),
                'date' => (string)today(),
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                '休憩開始に失敗しました。時間をおいて再度お試しください。',
                0,
                $e
            );
        } catch (\Throwable $e) {
            Log::error('breakStart unexpected error', [
                'user_id' => auth()->id(),
                'date' => (string)today(),
                'message' => $e->getMessage(),
            ]);

            if ($e instanceof RuntimeException) {
                throw $e; // 想定内メッセージは潰さない
            }

            throw new RuntimeException(
                '休憩開始に失敗しました。管理者に連絡してください。',
                0,
                $e
            );
        }
    }

    /**
     * 休憩終了打刻を行う。
     *
     * @return void
     * @throws \RuntimeException
     */
    private function breakEnd()
    {
        try {
            $attendance = Attendance::with('breaks')
                ->where('user_id', auth()->user()->id)
                ->whereDate('work_date', today())->first();

            if (!$attendance) {
                // 出勤していない
                throw new RuntimeException('本日は出勤打刻が行われていません。');
            }

            if (is_null($attendance->clock_in_at)) {
                // レコードはあるが出勤時刻がない（不整合）
                throw new RuntimeException('出勤打刻が確認できません。画面を更新して状態をご確認ください。');
            }

            if (!is_null($attendance->clock_out_at)) {
                // 退勤済み
                throw new RuntimeException('すでに退勤済みのため、休憩終了ができません。');
            }

            // 進行中（開始済み・未終了）の休憩を取得
            $break = $attendance->breaks()
                ->whereNotNull('break_start_at')
                ->whereNull('break_end_at')
                ->orderByDesc('break_start_at')
                ->first();

            // 休憩中じゃない（押し間違い）
            if (!$break) {
                throw new RuntimeException('休憩中ではないため、休憩終了できません。');
            }

            $data = ['break_end_at' => now()];
            $break->update($data);
        } catch (QueryException $e) {
            Log::warning('breakEnd DB error', [
                'user_id' => auth()->id(),
                'date' => (string)today(),
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                '休憩終了に失敗しました。時間をおいて再度お試しください。',
                0,
                $e
            );
        } catch (\Throwable $e) {
            Log::error('breakEnd unexpected error', [
                'user_id' => auth()->id(),
                'date' => (string)today(),
                'message' => $e->getMessage(),
            ]);

            if ($e instanceof RuntimeException) {
                throw $e; // 想定内メッセージは潰さない
            }

            throw new RuntimeException(
                '休憩終了に失敗しました。管理者に連絡7してください。',
                0,
                $e
            );
        }
    }

    /**
     * 一般ユーザー向けの勤怠一覧画面を表示する。
     *
     * @param  \App\Http\Requests\AttendanceIndexRequest $request
     * @return \Illuminate\Contracts\View\View
     *
     */
    public function index(AttendanceIndexRequest $request)
    {
        $layout = 'layouts.user-menu';

        $user = auth()->user();
        $userId = $user->id;

        $target = $this->resolveTargetMonth($request);

        $current = $target['current'];
        $yearMonth = $current->format('Y/m');

        $prevMonth = $current->copy()->subMonth()->format('Y-m');
        $nextMonth = $current->copy()->addMonth()->format('Y-m');

        $startOfMonth = $target['start'];
        $endOfMonth = $target['end'];

        $dates = $this->buildMonthlyDates($startOfMonth, $endOfMonth);

        $attendanceMap = $this->getMonthlyAttendanceMap($userId, $startOfMonth, $endOfMonth);

        $dates = $this->attachAttendancesToDates($dates, $attendanceMap);

        return view('attendance.index', compact(
            'layout',
            'yearMonth',
            'prevMonth',
            'nextMonth',
            'dates'
        ));
    }

    /**
     * 勤怠詳細画面を表示する。
     *
     * @param  \App\Http\Requests\AttendanceShowRequest $request
     * @return \Illuminate\Contracts\View\View
     */
    public function show(AttendanceShowRequest $request)
    {
        $attendance = Attendance::with('breaks', 'user')
            ->findOrFail($request->route('id'));

        $isAdminContext = (bool)$request->attributes->get('is_admin_context', false);

        // 申請をまとめて取得
        $requests = AttendanceChangeRequest::where('attendance_id', $attendance->id)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn($row) => $row->status->value);

        $pendingRequest = $requests
            ->get(ApplicationStatus::PENDING->value)
            ?->first();

        $approvedRequest = $requests
            ->get(ApplicationStatus::APPROVED->value)
            ?->first();

        $isPending = (bool) $pendingRequest;
        $reason = (string) ($approvedRequest->reason ?? '');

        if ($isAdminContext) {
            $layout = 'layouts.admin-menu';

            return view(
                'attendance.admin.show',
                compact(
                    'layout',
                    'attendance',
                    'isPending',
                    'reason'
                )
            );
        } else {
            $layout = 'layouts.user-menu';

            $editable = !$isPending;

            return view(
                'attendance.show',
                compact(
                    'layout',
                    'attendance',
                    'pendingRequest',
                    'editable',
                    'reason',
                )
            );
        }
    }

    /**
     * 月指定クエリ（month=Y-m）から対象月情報を解決する。
     *
     * @param  \Illuminate\Http\Request $request
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
     * @param  \Carbon\Carbon $start  期間開始日（通常は月初）
     * @param  \Carbon\Carbon $end    期間終了日（通常は月末）
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
     * @param  int|string $userId 対象ユーザーID
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
