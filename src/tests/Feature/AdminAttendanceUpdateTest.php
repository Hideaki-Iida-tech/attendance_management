<?php

namespace Tests\Feature;

use App\Enums\ActionType;
use App\Enums\ApplicationStatus;
use App\Models\Attendance;
use App\Models\AttendanceChangeRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }
    /**
     * 承認待ちの修正申請が全て表示されていることをテスト
     */
    public function test_it_displays_all_pending_attendance_update_requests()
    {
        // 1-1. テストユーザー1のインスタンスを生成
        $users[] = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 1-2. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 1-3. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $users[0]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 1-4. 休憩開始・終了時刻をDBに保存
        $cases = [
            [
                'break_start_at' => '2026-02-15 10:00:00',
                'break_end_at' => '2026-02-15 10:15:00',
            ],
            [
                'break_start_at' => '2026-02-15 12:00:00',
                'break_end_at' => '2026-02-15 13:00:00',
            ],
            [
                'break_start_at' => '2026-02-15 15:00:00',
                'break_end_at' => '2026-02-15 15:15:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance->breaks()->create($case);
        }
        $breaks = $attendance->breaks()->orderBy('break_start_at', 'asc')->get();

        // 1-5. 修正ヘッダー情報を登録
        $changeData = [
            'user_id' => $users[0]->id,
            'attendance_id' => $attendance->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'status' => ApplicationStatus::PENDING, // 承認待ちに設定
            'reason' => 'test1',
        ];
        $changeRequests[] = AttendanceChangeRequest::create($changeData);

        // 1-6. 勤怠修正情報を登録
        $changedAttendanceData = [
            'new_clock_in_at' => Carbon::parse('2026-02-15 09:01:00'),
            'new_clock_out_at' => Carbon::parse('2026-02-15 17:01:00'),
            'old_clock_in_at' => $attendance->clock_in_at,
            'old_clock_out_at' => $attendance->clock_out_at,
        ];
        $changeRequests[0]->attendance()->create($changedAttendanceData);

        // 1-7. 休憩修正情報を登録
        $changedBreaksData = [
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[0]->id,
                'new_break_start_at' => Carbon::parse('2026-02-15 10:00:00'),
                'new_break_end_at' => Carbon::parse('2026-02-15 10:16:00'),
                'old_break_start_at' => $breaks[0]->break_start_at,
                'old_break_end_at' => $breaks[0]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[1]->id,
                'new_break_start_at' => Carbon::parse('2026-02-15 12:00:00'),
                'new_break_end_at' => Carbon::parse('2026-02-15 13:01:00'),
                'old_break_start_at' => $breaks[1]->break_start_at,
                'old_break_end_at' => $breaks[1]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[2]->id,
                'new_break_start_at' => Carbon::parse('2026-02-15 15:00:00'),
                'new_break_end_at' => Carbon::parse('2026-02-15 15:16:00'),
                'old_break_start_at' => $breaks[2]->break_start_at,
                'old_break_end_at' => $breaks[2]->break_end_at,
            ],
        ];
        foreach ($changedBreaksData as $changedBreak) {
            $changeRequests[0]->breaks()->create($changedBreak);
        }

        // 2-1. テストユーザー2のインスタンスを生成
        $users[] = User::where('name', 'テストユーザー2')
            ->first() ?? User::inRandomOrder()->first();

        // 2-2. テスト日時を固定
        $testTime = '2026-02-16 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 2-3. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-16 09:02:00';
        $clockOutTime = '2026-02-16 17:02:00';
        $attendanceData = [
            'user_id' => $users[1]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 2-4. 休憩開始・終了時刻をDBに保存
        $cases = [
            [
                'break_start_at' => '2026-02-16 10:01:00',
                'break_end_at' => '2026-02-16 10:16:00',
            ],
            [
                'break_start_at' => '2026-02-16 12:01:00',
                'break_end_at' => '2026-02-16 13:01:00',
            ],
            [
                'break_start_at' => '2026-02-16 15:01:00',
                'break_end_at' => '2026-02-16 15:16:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance->breaks()->create($case);
        }
        $breaks = $attendance->breaks()->orderBy('break_start_at', 'asc')->get();

        // 2-5. 修正ヘッダー情報を登録
        $changeData = [
            'user_id' => $users[1]->id,
            'attendance_id' => $attendance->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'status' => ApplicationStatus::PENDING, // 承認待ちに設定
            'reason' => 'test2',
        ];
        $changeRequests[] = AttendanceChangeRequest::create($changeData);

        // 2-6. 勤怠修正情報を登録
        $changedAttendanceData = [
            'new_clock_in_at' => Carbon::parse('2026-02-16 09:03:00'),
            'new_clock_out_at' => Carbon::parse('2026-02-16 17:03:00'),
            'old_clock_in_at' => $attendance->clock_in_at,
            'old_clock_out_at' => $attendance->clock_out_at,
        ];
        $changeRequests[1]->attendance()->create($changedAttendanceData);

        // 2-7. 休憩修正情報を登録
        $changedBreaksData = [
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[0]->id,
                'new_break_start_at' => Carbon::parse('2026-02-16 10:01:00'),
                'new_break_end_at' => Carbon::parse('2026-02-16 10:17:00'),
                'old_break_start_at' => $breaks[0]->break_start_at,
                'old_break_end_at' => $breaks[0]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[1]->id,
                'new_break_start_at' => Carbon::parse('2026-02-16 12:01:00'),
                'new_break_end_at' => Carbon::parse('2026-02-16 13:02:00'),
                'old_break_start_at' => $breaks[1]->break_start_at,
                'old_break_end_at' => $breaks[1]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[2]->id,
                'new_break_start_at' => Carbon::parse('2026-02-16 15:01:00'),
                'new_break_end_at' => Carbon::parse('2026-02-16 15:17:00'),
                'old_break_start_at' => $breaks[2]->break_start_at,
                'old_break_end_at' => $breaks[2]->break_end_at,
            ],
        ];
        foreach ($changedBreaksData as $changedBreak) {
            $changeRequests[1]->breaks()->create($changedBreak);
        }

        // 3-1. テストユーザー3のインスタンスを生成
        $users[] = User::where('name', 'テストユーザー3')
            ->first() ?? User::inRandomOrder()->first();

        // 3-2. テスト日時を固定
        $testTime = '2026-02-17 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 3-3. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-17 09:03:00';
        $clockOutTime = '2026-02-17 17:03:00';
        $attendanceData = [
            'user_id' => $users[2]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 3-4. 休憩開始・終了時刻をDBに保存
        $cases = [
            [
                'break_start_at' => '2026-02-17 10:02:00',
                'break_end_at' => '2026-02-17 10:17:00',
            ],
            [
                'break_start_at' => '2026-02-17 12:02:00',
                'break_end_at' => '2026-02-17 13:02:00',
            ],
            [
                'break_start_at' => '2026-02-17 15:02:00',
                'break_end_at' => '2026-02-17 15:17:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance->breaks()->create($case);
        }
        $breaks = $attendance->breaks()->orderBy('break_start_at', 'asc')->get();

        // 3-5. 修正ヘッダー情報を登録
        $changeData = [
            'user_id' => $users[2]->id,
            'attendance_id' => $attendance->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'status' => ApplicationStatus::PENDING, // 承認待ちに設定
            'reason' => 'test3',
        ];
        $changeRequests[] = AttendanceChangeRequest::create($changeData);

        // 3-6. 勤怠修正情報を登録
        $changedAttendanceData = [
            'new_clock_in_at' => Carbon::parse('2026-02-17 09:04:00'),
            'new_clock_out_at' => Carbon::parse('2026-02-17 17:04:00'),
            'old_clock_in_at' => $attendance->clock_in_at,
            'old_clock_out_at' => $attendance->clock_out_at,
        ];
        $changeRequests[2]->attendance()->create($changedAttendanceData);

        // 3-7. 休憩修正情報を登録
        $changedBreaksData = [
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[0]->id,
                'new_break_start_at' => Carbon::parse('2026-02-17 10:03:00'),
                'new_break_end_at' => Carbon::parse('2026-02-17 10:19:00'),
                'old_break_start_at' => $breaks[0]->break_start_at,
                'old_break_end_at' => $breaks[0]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[1]->id,
                'new_break_start_at' => Carbon::parse('2026-02-17 12:03:00'),
                'new_break_end_at' => Carbon::parse('2026-02-17 13:04:00'),
                'old_break_start_at' => $breaks[1]->break_start_at,
                'old_break_end_at' => $breaks[1]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[2]->id,
                'new_break_start_at' => Carbon::parse('2026-02-17 15:03:00'),
                'new_break_end_at' => Carbon::parse('2026-02-17 15:19:00'),
                'old_break_start_at' => $breaks[2]->break_start_at,
                'old_break_end_at' => $breaks[2]->break_end_at,
            ],
        ];
        foreach ($changedBreaksData as $changedBreak) {
            $changeRequests[2]->breaks()->create($changedBreak);
        }

        // 4-1. テスト日時を固定
        $testTime = '2026-02-18 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 4-2. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-18 09:04:00';
        $clockOutTime = '2026-02-18 17:04:00';
        $attendanceData = [
            'user_id' => $users[0]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 4-3. 休憩開始・終了時刻をDBに保存
        $cases = [
            [
                'break_start_at' => '2026-02-18 10:03:00',
                'break_end_at' => '2026-02-18 10:18:00',
            ],
            [
                'break_start_at' => '2026-02-18 12:03:00',
                'break_end_at' => '2026-02-18 13:03:00',
            ],
            [
                'break_start_at' => '2026-02-18 15:03:00',
                'break_end_at' => '2026-02-18 15:18:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance->breaks()->create($case);
        }
        $breaks = $attendance->breaks()->orderBy('break_start_at', 'asc')->get();

        // 4-4. 修正ヘッダー情報を登録
        $changeData = [
            'user_id' => $users[0]->id,
            'attendance_id' => $attendance->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'status' => ApplicationStatus::APPROVED, // 承認済みに設定
            'reason' => 'test4',
        ];
        $approvedChangeRequest = AttendanceChangeRequest::create($changeData);

        // 4-5. 勤怠修正情報を登録
        $changedAttendanceData = [
            'new_clock_in_at' => Carbon::parse('2026-02-18 09:05:00'),
            'new_clock_out_at' => Carbon::parse('2026-02-18 17:05:00'),
            'old_clock_in_at' => $attendance->clock_in_at,
            'old_clock_out_at' => $attendance->clock_out_at,
        ];
        $approvedChangeRequest->attendance()->create($changedAttendanceData);

        // 4-6. 休憩修正情報を登録
        $changedBreaksData = [
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[0]->id,
                'new_break_start_at' => Carbon::parse('2026-02-18 10:04:00'),
                'new_break_end_at' => Carbon::parse('2026-02-18 10:20:00'),
                'old_break_start_at' => $breaks[0]->break_start_at,
                'old_break_end_at' => $breaks[0]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[1]->id,
                'new_break_start_at' => Carbon::parse('2026-02-18 12:04:00'),
                'new_break_end_at' => Carbon::parse('2026-02-18 13:05:00'),
                'old_break_start_at' => $breaks[1]->break_start_at,
                'old_break_end_at' => $breaks[1]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[2]->id,
                'new_break_start_at' => Carbon::parse('2026-02-18 15:04:00'),
                'new_break_end_at' => Carbon::parse('2026-02-18 15:20:00'),
                'old_break_start_at' => $breaks[2]->break_start_at,
                'old_break_end_at' => $breaks[2]->break_end_at,
            ],
        ];
        foreach ($changedBreaksData as $changedBreak) {
            $approvedChangeRequest->breaks()->create($changedBreak);
        }
        // 5. 管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->firstOrFail();

        // 6. 申請一覧画面（管理者）を開く
        $response = $this->actingAs($adminUser)->get('/stamp_correction_request/list');

        // 7. 「承認待ち」の申請が全て表示されていることを確認
        $response->assertSee('承認待ち');
        foreach ($changeRequests as $changeRequest) {
            $response->assertSee($changeRequest->user->name);
            $response->assertSee($changeRequest->work_date->format('Y/m/d'));
            $response->assertSee($changeRequest->reason);
            $response->assertSee($changeRequest->created_at->format('Y/m/d'));
            $response->assertSee('<a href="/stamp_correction_request/approve/'
                . $changeRequest->id . '" class="detail-link">詳細</a>', false);
            $this->assertDatabaseHas(
                'attendance_change_requests',
                [
                    'id' => $changeRequest->id,
                    'status' => ApplicationStatus::PENDING->value,
                ]
            );
        }

        // 8. 「承認済み」の申請が表示されていないことを確認
        $response->assertDontSee($approvedChangeRequest->work_date->format('Y/m/d'));
        $response->assertDontSee($approvedChangeRequest->reason);
        $response->assertDontSee($approvedChangeRequest->created_at->format('Y/m/d'));
        $response->assertDontSee('<a href="/stamp_correction_request/approve/'
            . $approvedChangeRequest->id . '" class="detail-link">詳細</a>', false);
        $this->assertDatabaseHas(
            'attendance_change_requests',
            [
                'id' => $approvedChangeRequest->id,
                'status' => ApplicationStatus::APPROVED->value,
            ]
        );

        // 9. テスト時刻を現在に戻す
        Carbon::setTestNow();
    }

    /**
     * 承認済みの修正申請が全て表示されていることをテスト
     */
    public function test_it_displays_all_approved_attendance_update_requests()
    {
        // 1-1. テストユーザー1のインスタンスを生成
        $users[] = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 1-2. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 1-3. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $users[0]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 1-4. 休憩開始・終了時刻をDBに保存
        $cases = [
            [
                'break_start_at' => '2026-02-15 10:00:00',
                'break_end_at' => '2026-02-15 10:15:00',
            ],
            [
                'break_start_at' => '2026-02-15 12:00:00',
                'break_end_at' => '2026-02-15 13:00:00',
            ],
            [
                'break_start_at' => '2026-02-15 15:00:00',
                'break_end_at' => '2026-02-15 15:15:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance->breaks()->create($case);
        }
        $breaks = $attendance->breaks()->orderBy('break_start_at', 'asc')->get();

        // 1-5. 修正ヘッダー情報を登録
        $changeData = [
            'user_id' => $users[0]->id,
            'attendance_id' => $attendance->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'status' => ApplicationStatus::APPROVED, // 承認済みに設定
            'reason' => 'test1',
        ];
        $changeRequests[] = AttendanceChangeRequest::create($changeData);

        // 1-6. 勤怠修正情報を登録
        $changedAttendanceData = [
            'new_clock_in_at' => Carbon::parse('2026-02-15 09:01:00'),
            'new_clock_out_at' => Carbon::parse('2026-02-15 17:01:00'),
            'old_clock_in_at' => $attendance->clock_in_at,
            'old_clock_out_at' => $attendance->clock_out_at,
        ];
        $changeRequests[0]->attendance()->create($changedAttendanceData);

        // 1-7. 休憩修正情報を登録
        $changedBreaksData = [
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[0]->id,
                'new_break_start_at' => Carbon::parse('2026-02-15 10:00:00'),
                'new_break_end_at' => Carbon::parse('2026-02-15 10:16:00'),
                'old_break_start_at' => $breaks[0]->break_start_at,
                'old_break_end_at' => $breaks[0]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[1]->id,
                'new_break_start_at' => Carbon::parse('2026-02-15 12:00:00'),
                'new_break_end_at' => Carbon::parse('2026-02-15 13:01:00'),
                'old_break_start_at' => $breaks[1]->break_start_at,
                'old_break_end_at' => $breaks[1]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[2]->id,
                'new_break_start_at' => Carbon::parse('2026-02-15 15:00:00'),
                'new_break_end_at' => Carbon::parse('2026-02-15 15:16:00'),
                'old_break_start_at' => $breaks[2]->break_start_at,
                'old_break_end_at' => $breaks[2]->break_end_at,
            ],
        ];
        foreach ($changedBreaksData as $changedBreak) {
            $changeRequests[0]->breaks()->create($changedBreak);
        }

        // 2-1. テストユーザー2のインスタンスを生成
        $users[] = User::where('name', 'テストユーザー2')
            ->first() ?? User::inRandomOrder()->first();

        // 2-2. テスト日時を固定
        $testTime = '2026-02-16 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 2-3. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-16 09:02:00';
        $clockOutTime = '2026-02-16 17:02:00';
        $attendanceData = [
            'user_id' => $users[1]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 2-4. 休憩開始・終了時刻をDBに保存
        $cases = [
            [
                'break_start_at' => '2026-02-16 10:01:00',
                'break_end_at' => '2026-02-16 10:16:00',
            ],
            [
                'break_start_at' => '2026-02-16 12:01:00',
                'break_end_at' => '2026-02-16 13:01:00',
            ],
            [
                'break_start_at' => '2026-02-16 15:01:00',
                'break_end_at' => '2026-02-16 15:16:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance->breaks()->create($case);
        }
        $breaks = $attendance->breaks()->orderBy('break_start_at', 'asc')->get();

        // 2-5. 修正ヘッダー情報を登録
        $changeData = [
            'user_id' => $users[1]->id,
            'attendance_id' => $attendance->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'status' => ApplicationStatus::APPROVED, // 承認済みに設定
            'reason' => 'test2',
        ];
        $changeRequests[] = AttendanceChangeRequest::create($changeData);

        // 2-6. 勤怠修正情報を登録
        $changedAttendanceData = [
            'new_clock_in_at' => Carbon::parse('2026-02-16 09:03:00'),
            'new_clock_out_at' => Carbon::parse('2026-02-16 17:03:00'),
            'old_clock_in_at' => $attendance->clock_in_at,
            'old_clock_out_at' => $attendance->clock_out_at,
        ];
        $changeRequests[1]->attendance()->create($changedAttendanceData);

        // 2-7. 休憩修正情報を登録
        $changedBreaksData = [
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[0]->id,
                'new_break_start_at' => Carbon::parse('2026-02-16 10:01:00'),
                'new_break_end_at' => Carbon::parse('2026-02-16 10:17:00'),
                'old_break_start_at' => $breaks[0]->break_start_at,
                'old_break_end_at' => $breaks[0]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[1]->id,
                'new_break_start_at' => Carbon::parse('2026-02-16 12:01:00'),
                'new_break_end_at' => Carbon::parse('2026-02-16 13:02:00'),
                'old_break_start_at' => $breaks[1]->break_start_at,
                'old_break_end_at' => $breaks[1]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[2]->id,
                'new_break_start_at' => Carbon::parse('2026-02-16 15:01:00'),
                'new_break_end_at' => Carbon::parse('2026-02-16 15:17:00'),
                'old_break_start_at' => $breaks[2]->break_start_at,
                'old_break_end_at' => $breaks[2]->break_end_at,
            ],
        ];
        foreach ($changedBreaksData as $changedBreak) {
            $changeRequests[1]->breaks()->create($changedBreak);
        }

        // 3-1. テストユーザー3のインスタンスを生成
        $users[] = User::where('name', 'テストユーザー3')
            ->first() ?? User::inRandomOrder()->first();

        // 3-2. テスト日時を固定
        $testTime = '2026-02-17 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 3-3. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-17 09:03:00';
        $clockOutTime = '2026-02-17 17:03:00';
        $attendanceData = [
            'user_id' => $users[2]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 3-4. 休憩開始・終了時刻をDBに保存
        $cases = [
            [
                'break_start_at' => '2026-02-17 10:02:00',
                'break_end_at' => '2026-02-17 10:17:00',
            ],
            [
                'break_start_at' => '2026-02-17 12:02:00',
                'break_end_at' => '2026-02-17 13:02:00',
            ],
            [
                'break_start_at' => '2026-02-17 15:02:00',
                'break_end_at' => '2026-02-17 15:17:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance->breaks()->create($case);
        }
        $breaks = $attendance->breaks()->orderBy('break_start_at', 'asc')->get();

        // 3-5. 修正ヘッダー情報を登録
        $changeData = [
            'user_id' => $users[2]->id,
            'attendance_id' => $attendance->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'status' => ApplicationStatus::APPROVED, // 承認済みに設定
            'reason' => 'test3',
        ];
        $changeRequests[] = AttendanceChangeRequest::create($changeData);

        // 3-6. 勤怠修正情報を登録
        $changedAttendanceData = [
            'new_clock_in_at' => Carbon::parse('2026-02-17 09:04:00'),
            'new_clock_out_at' => Carbon::parse('2026-02-17 17:04:00'),
            'old_clock_in_at' => $attendance->clock_in_at,
            'old_clock_out_at' => $attendance->clock_out_at,
        ];
        $changeRequests[2]->attendance()->create($changedAttendanceData);

        // 3-7. 休憩修正情報を登録
        $changedBreaksData = [
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[0]->id,
                'new_break_start_at' => Carbon::parse('2026-02-17 10:03:00'),
                'new_break_end_at' => Carbon::parse('2026-02-17 10:19:00'),
                'old_break_start_at' => $breaks[0]->break_start_at,
                'old_break_end_at' => $breaks[0]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[1]->id,
                'new_break_start_at' => Carbon::parse('2026-02-17 12:03:00'),
                'new_break_end_at' => Carbon::parse('2026-02-17 13:04:00'),
                'old_break_start_at' => $breaks[1]->break_start_at,
                'old_break_end_at' => $breaks[1]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[2]->id,
                'new_break_start_at' => Carbon::parse('2026-02-17 15:03:00'),
                'new_break_end_at' => Carbon::parse('2026-02-17 15:19:00'),
                'old_break_start_at' => $breaks[2]->break_start_at,
                'old_break_end_at' => $breaks[2]->break_end_at,
            ],
        ];
        foreach ($changedBreaksData as $changedBreak) {
            $changeRequests[2]->breaks()->create($changedBreak);
        }

        // 4-1. テスト日時を固定
        $testTime = '2026-02-18 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 4-2. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-18 09:04:00';
        $clockOutTime = '2026-02-18 17:04:00';
        $attendanceData = [
            'user_id' => $users[0]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 4-3. 休憩開始・終了時刻をDBに保存
        $cases = [
            [
                'break_start_at' => '2026-02-18 10:03:00',
                'break_end_at' => '2026-02-18 10:18:00',
            ],
            [
                'break_start_at' => '2026-02-18 12:03:00',
                'break_end_at' => '2026-02-18 13:03:00',
            ],
            [
                'break_start_at' => '2026-02-18 15:03:00',
                'break_end_at' => '2026-02-18 15:18:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance->breaks()->create($case);
        }
        $breaks = $attendance->breaks()->orderBy('break_start_at', 'asc')->get();

        // 4-4. 修正ヘッダー情報を登録
        $changeData = [
            'user_id' => $users[0]->id,
            'attendance_id' => $attendance->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'status' => ApplicationStatus::PENDING, // 承認待ちに設定
            'reason' => 'test4',
        ];
        $pendingChangeRequest = AttendanceChangeRequest::create($changeData);

        // 4-5. 勤怠修正情報を登録
        $changedAttendanceData = [
            'new_clock_in_at' => Carbon::parse('2026-02-18 09:05:00'),
            'new_clock_out_at' => Carbon::parse('2026-02-18 17:05:00'),
            'old_clock_in_at' => $attendance->clock_in_at,
            'old_clock_out_at' => $attendance->clock_out_at,
        ];
        $pendingChangeRequest->attendance()->create($changedAttendanceData);

        // 4-6. 休憩修正情報を登録
        $changedBreaksData = [
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[0]->id,
                'new_break_start_at' => Carbon::parse('2026-02-18 10:04:00'),
                'new_break_end_at' => Carbon::parse('2026-02-18 10:20:00'),
                'old_break_start_at' => $breaks[0]->break_start_at,
                'old_break_end_at' => $breaks[0]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[1]->id,
                'new_break_start_at' => Carbon::parse('2026-02-18 12:04:00'),
                'new_break_end_at' => Carbon::parse('2026-02-18 13:05:00'),
                'old_break_start_at' => $breaks[1]->break_start_at,
                'old_break_end_at' => $breaks[1]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[2]->id,
                'new_break_start_at' => Carbon::parse('2026-02-18 15:04:00'),
                'new_break_end_at' => Carbon::parse('2026-02-18 15:20:00'),
                'old_break_start_at' => $breaks[2]->break_start_at,
                'old_break_end_at' => $breaks[2]->break_end_at,
            ],
        ];
        foreach ($changedBreaksData as $changedBreak) {
            $pendingChangeRequest->breaks()->create($changedBreak);
        }
        // 5. 管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->firstOrFail();

        // 6. 申請一覧画面（管理者）を開く
        $response = $this->actingAs($adminUser)
            ->get('/stamp_correction_request/list/?tab=' . ApplicationStatus::APPROVED->name);

        // 7. 「承認済み」の申請が全て表示されていることを確認
        $response->assertSee('承認済み');
        foreach ($changeRequests as $changeRequest) {
            $response->assertSee($changeRequest->user->name);
            $response->assertSee($changeRequest->work_date->format('Y/m/d'));
            $response->assertSee($changeRequest->reason);
            $response->assertSee($changeRequest->created_at->format('Y/m/d'));
            $response->assertSee('<a href="/stamp_correction_request/approve/'
                . $changeRequest->id . '" class="detail-link">詳細</a>', false);
            $this->assertDatabaseHas(
                'attendance_change_requests',
                [
                    'id' => $changeRequest->id,
                    'status' => ApplicationStatus::APPROVED->value,
                ]
            );
        }

        // 8. 「承認待ち」の申請が表示されていないことを確認
        $response->assertDontSee($pendingChangeRequest->work_date->format('Y/m/d'));
        $response->assertDontSee($pendingChangeRequest->reason);
        $response->assertDontSee($pendingChangeRequest->created_at->format('Y/m/d'));
        $response->assertDontSee('<a href="/stamp_correction_request/approve/'
            . $pendingChangeRequest->id . '" class="detail-link">詳細</a>', false);
        $this->assertDatabaseHas(
            'attendance_change_requests',
            [
                'id' => $pendingChangeRequest->id,
                'status' => ApplicationStatus::PENDING->value,
            ]
        );

        // 9. テスト時刻を現在に戻す
        Carbon::setTestNow();
    }
    /**
     * 修正申請の詳細内容が正しく表示されることをテスト
     */
    public function test_it_displays_attendance_correction_request_details_correctly()
    {
        // 1. テストユーザー1のインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 3. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 4. 休憩開始・終了時刻をDBに保存
        $cases = [
            [
                'break_start_at' => '2026-02-15 10:00:00',
                'break_end_at' => '2026-02-15 10:15:00',
            ],
            [
                'break_start_at' => '2026-02-15 12:00:00',
                'break_end_at' => '2026-02-15 13:00:00',
            ],
            [
                'break_start_at' => '2026-02-15 15:00:00',
                'break_end_at' => '2026-02-15 15:15:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance->breaks()->create($case);
        }
        $breaks = $attendance->breaks()->orderBy('break_start_at', 'asc')->get();

        // 5. 修正ヘッダー情報を登録
        $changeData = [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'status' => ApplicationStatus::PENDING, // 承認待ちに設定
            'reason' => 'test1',
        ];
        $changeRequest = AttendanceChangeRequest::create($changeData);

        // 6. 勤怠修正情報を登録
        $changedAttendanceData = [
            'new_clock_in_at' => Carbon::parse('2026-02-15 09:01:00'),
            'new_clock_out_at' => Carbon::parse('2026-02-15 17:01:00'),
            'old_clock_in_at' => $attendance->clock_in_at,
            'old_clock_out_at' => $attendance->clock_out_at,
        ];
        $changeRequest->attendance()->create($changedAttendanceData);

        // 7. 休憩修正情報を登録
        $changedBreaksData = [
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[0]->id,
                'new_break_start_at' => Carbon::parse('2026-02-15 10:00:00'),
                'new_break_end_at' => Carbon::parse('2026-02-15 10:16:00'),
                'old_break_start_at' => $breaks[0]->break_start_at,
                'old_break_end_at' => $breaks[0]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[1]->id,
                'new_break_start_at' => Carbon::parse('2026-02-15 12:00:00'),
                'new_break_end_at' => Carbon::parse('2026-02-15 13:01:00'),
                'old_break_start_at' => $breaks[1]->break_start_at,
                'old_break_end_at' => $breaks[1]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[2]->id,
                'new_break_start_at' => Carbon::parse('2026-02-15 15:00:00'),
                'new_break_end_at' => Carbon::parse('2026-02-15 15:16:00'),
                'old_break_start_at' => $breaks[2]->break_start_at,
                'old_break_end_at' => $breaks[2]->break_end_at,
            ],
        ];
        foreach ($changedBreaksData as $changedBreak) {
            $changeRequest->breaks()->create($changedBreak);
        }

        // 8. 管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->firstOrFail();

        // 9. 申請一覧画面（管理者）を開く
        $response = $this->actingAs($adminUser)->get('/stamp_correction_request/list');

        // 10. 「詳細」リンクが表示されていることを確認
        $response->assertSee('<a href="/stamp_correction_request/approve/'
            . $changeRequest->id . '" class="detail-link">詳細</a>', false);

        // 11. 修正申請承認画面（管理者）を開く
        $response = $this->actingAs($adminUser)
            ->get('/stamp_correction_request/approve/' . $changeRequest->id);

        // 12. 修正申請承認画面（管理者）に正しく表示されていることを確認
        $response->assertSee($changeRequest->user->name);
        $response->assertSee($changeRequest->work_date->format('Y年'));
        $response->assertSee($changeRequest->work_date->format('n月j日'));
        $response->assertSee($changeRequest->clockInTime);
        $response->assertSee($changeRequest->clockOutTime);
        foreach ($changeRequest->breaks as $break) {
            $response->assertSee($break->breakStartTime);
            $response->assertSee($break->breakEndTime);
        }
        $response->assertSee($changeRequest->reason);

        // 13. テスト時刻を現在に戻す
        Carbon::setTestNow();
    }
    /**
     * 修正申請の承認処理が正しく行われることをテスト
     */
    public function test_it_approves_attendance_correction_request_correctly()
    {
        // 1. テストユーザー1のインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 3. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 4. 休憩開始・終了時刻をDBに保存
        $cases = [
            [
                'break_start_at' => '2026-02-15 10:00:00',
                'break_end_at' => '2026-02-15 10:15:00',
            ],
            [
                'break_start_at' => '2026-02-15 12:00:00',
                'break_end_at' => '2026-02-15 13:00:00',
            ],
            [
                'break_start_at' => '2026-02-15 15:00:00',
                'break_end_at' => '2026-02-15 15:15:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance->breaks()->create($case);
        }
        $breaks = $attendance->breaks()->orderBy('break_start_at', 'asc')->get();

        // 5. 修正ヘッダー情報を登録
        $changeData = [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'status' => ApplicationStatus::PENDING, // 承認待ちに設定
            'reason' => 'test1',
        ];
        $changeRequest = AttendanceChangeRequest::create($changeData);

        // 6. 勤怠修正情報を登録
        $changedAttendanceData = [
            'new_clock_in_at' => Carbon::parse('2026-02-15 09:01:00'),
            'new_clock_out_at' => Carbon::parse('2026-02-15 17:01:00'),
            'old_clock_in_at' => $attendance->clock_in_at,
            'old_clock_out_at' => $attendance->clock_out_at,
        ];
        $changeRequest->attendance()->create($changedAttendanceData);

        // 7. 休憩修正情報を登録
        $changedBreaksData = [
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[0]->id,
                'new_break_start_at' => Carbon::parse('2026-02-15 10:00:00'),
                'new_break_end_at' => Carbon::parse('2026-02-15 10:16:00'),
                'old_break_start_at' => $breaks[0]->break_start_at,
                'old_break_end_at' => $breaks[0]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[1]->id,
                'new_break_start_at' => Carbon::parse('2026-02-15 12:00:00'),
                'new_break_end_at' => Carbon::parse('2026-02-15 13:01:00'),
                'old_break_start_at' => $breaks[1]->break_start_at,
                'old_break_end_at' => $breaks[1]->break_end_at,
            ],
            [
                'action' => ActionType::UPDATE,
                'target_break_id' => $breaks[2]->id,
                'new_break_start_at' => Carbon::parse('2026-02-15 15:00:00'),
                'new_break_end_at' => Carbon::parse('2026-02-15 15:16:00'),
                'old_break_start_at' => $breaks[2]->break_start_at,
                'old_break_end_at' => $breaks[2]->break_end_at,
            ],
        ];
        foreach ($changedBreaksData as $changedBreak) {
            $changeRequest->breaks()->create($changedBreak);
        }

        // 5. 管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->firstOrFail();

        // 6. 申請一覧画面（管理者）を開く
        $response = $this->actingAs($adminUser)->get('/stamp_correction_request/list');

        // 7. 「詳細」リンクが表示されていることを確認
        $response->assertSee('<a href="/stamp_correction_request/approve/'
            . $changeRequest->id . '" class="detail-link">詳細</a>', false);

        // 8. 修正申請承認画面（管理者）を開く
        $response = $this->actingAs($adminUser)
            ->get('/stamp_correction_request/approve/' . $changeRequest->id);

        // 9. 「承認」ボタンが表示されていることを確認
        $response->assertSee(
            '<button type="submit" name="submit" class="approve-button">承認</button>',
            false
        );

        // 10. 「承認」ボタンを押下
        $response = $this->actingAs($adminUser)
            ->post('/stamp_correction_request/approve/' . $changeRequest->id);
        $response->assertStatus(302);
        $response->assertRedirect('/stamp_correction_request/approve/' . $changeRequest->id);

        // 11. attendance_changer_requstsテーブルの該当レコードが承認済みになっていることを確認
        $this->assertDatabaseHas(
            'attendance_change_requests',
            [
                'attendance_id' => $attendance->id,
                'status' => ApplicationStatus::APPROVED,
            ]
        );

        // 12. attendancesテーブル、breaksテーブルの該当レコードが修正されていることを確認
        $this->assertDatabaseHas(
            'attendances',
            [
                'id' => $attendance->id,
                'clock_in_at' => $changeRequest->attendance->new_clock_in_at->format('Y-m-d H:i:s'),
                'clock_out_at' => $changeRequest->attendance->new_clock_out_at->format('Y-m-d H:i:s'),
            ]
        );
        $changeBreaksByTarget = $changeRequest->breaks()->get()->keyBy('target_break_id');
        foreach ($attendance->breaks as $break) {
            $change = $changeBreaksByTarget[$break->id];
            $this->assertDatabaseHas(
                'breaks',
                [
                    'id' => $break->id,
                    'break_start_at' => $change->new_break_start_at->format('Y-m-d H:i:s'),
                    'break_end_at' => $change->new_break_end_at->format('Y-m-d H:i:s'),
                ]
            );
        }

        // 13. テスト時刻を現在に戻す
        Carbon::setTestNow();
    }
}
