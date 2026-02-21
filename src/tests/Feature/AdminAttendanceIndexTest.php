<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できることをテスト
     */
    public function test_it_displays_all_users_attendances_for_the_specified_date()
    {
        // 1-1. テストユーザー1のインスタンスを作成
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
        $attendances[] = Attendance::create($attendanceData);

        // 1-4. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:15:00',
        ];
        $attendances[0]->breaks()->create($breaksData);

        // 2-1. テストユーザー2のインスタンスを作成
        $users[] = User::where('name', 'テストユーザー2')
            ->first() ?? User::inRandomOrder()->first();

        // 2-2. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:01:00';
        $clockOutTime = '2026-02-15 17:01:00';
        $attendanceData = [
            'user_id' => $users[1]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 2-3. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:16:00',
        ];
        $attendances[1]->breaks()->create($breaksData);

        // 3-1. テストユーザー3のインスタンスを作成
        $users[] = User::where('name', 'テストユーザー3')
            ->first() ?? User::inRandomOrder()->first();

        // 3-2. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:02:00';
        $clockOutTime = '2026-02-15 17:02:00';
        $attendanceData = [
            'user_id' => $users[2]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 3-3. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:17:00',
        ];
        $attendances[2]->breaks()->create($breaksData);

        // 4-1. テストユーザー4のインスタンスを作成
        $users[] = User::where('name', 'テストユーザー4')
            ->first() ?? User::inRandomOrder()->first();

        // 4-2. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:03:00';
        $clockOutTime = '2026-02-15 17:03:00';
        $attendanceData = [
            'user_id' => $users[3]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 4-3. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:18:00',
        ];
        $attendances[3]->breaks()->create($breaksData);


        // 5-1. テストユーザー5のインスタンスを作成
        $users[] = User::where('name', 'テストユーザー5')
            ->first() ?? User::inRandomOrder()->first();

        // 5-2. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:04:00';
        $clockOutTime = '2026-02-15 17:04:00';
        $attendanceData = [
            'user_id' => $users[4]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 5-3. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:19:00',
        ];
        $attendances[4]->breaks()->create($breaksData);


        // 6-1. テストユーザー6のインスタンスを作成
        $users[] = User::where('name', 'テストユーザー6')
            ->first() ?? User::inRandomOrder()->first();

        // 6-2. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:05:00';
        $clockOutTime = '2026-02-15 17:05:00';
        $attendanceData = [
            'user_id' => $users[5]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 6-3. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:20:00',
        ];
        $attendances[5]->breaks()->create($breaksData);

        // 7-1. テストユーザー7のインスタンスを作成
        $users[] = User::where('name', 'テストユーザー7')
            ->first() ?? User::inRandomOrder()->first();

        // 7-2. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:06:00';
        $clockOutTime = '2026-02-15 17:06:00';
        $attendanceData = [
            'user_id' => $users[6]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 7-3. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:21:00',
        ];
        $attendances[6]->breaks()->create($breaksData);

        // 8-1. テストユーザー8のインスタンスを作成
        $users[] = User::where('name', 'テストユーザー8')
            ->first() ?? User::inRandomOrder()->first();

        // 8-2. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:07:00';
        $clockOutTime = '2026-02-15 17:07:00';
        $attendanceData = [
            'user_id' => $users[7]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 8-3. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:22:00',
        ];
        $attendances[7]->breaks()->create($breaksData);

        // 9-1. テストユーザー9のインスタンスを作成
        $users[] = User::where('name', 'テストユーザー9')
            ->first() ?? User::inRandomOrder()->first();

        // 9-2. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:08:00';
        $clockOutTime = '2026-02-15 17:08:00';
        $attendanceData = [
            'user_id' => $users[8]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 9-3. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:23:00',
        ];
        $attendances[8]->breaks()->create($breaksData);

        // 10-1. テストユーザー10のインスタンスを作成
        $users[] = User::where('name', 'テストユーザー10')
            ->first() ?? User::inRandomOrder()->first();

        // 10-2. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:09:00';
        $clockOutTime = '2026-02-15 17:09:00';
        $attendanceData = [
            'user_id' => $users[9]->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 10-3. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:24:00',
        ];
        $attendances[9]->breaks()->create($breaksData);

        // 11-1. テストユーザー1で前日の出退勤情報を作成
        $clockInTime = '2026-02-14 09:10:00';
        $clockOutTime = '2026-02-14 17:10:00';
        $attendanceData = [
            'user_id' => $users[0]->id,
            'work_date' => Carbon::parse('2026-02-14 08:00:00')->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $prevAttendance = Attendance::create($attendanceData);

        // 11-2. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-14 10:00:00',
            'break_end_at' => '2026-02-14 10:25:00',
        ];
        $prevAttendance->breaks()->create($breaksData);

        // 12. ログインする管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 13. 管理者用勤怠一覧画面を開く
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list');

        // 14. その日になされた全ユーザーの勤怠情報が表示されていることを確認
        $response->assertSee($attendances[0]->work_date->format('Y年n月j日') . 'の勤怠');
        $response->assertSee($attendances[0]->work_date->format('Y/m/d'));
        foreach ($users as $user) {
            $response->assertSee($user->name);
        }
        foreach ($attendances as $attendance) {
            $response->assertSee($attendance->clockInTime);
            $response->assertSee($attendance->clockOutTime);
            $response->assertSee($attendance->formatedBreakTime);
            $response->assertSee($attendance->formatedWorkingTime);
        }
        // 15. 前日になされた出退勤情報が表示されていないことを確認
        $response->assertDontSee($prevAttendance->clockInTime);
        $response->assertDontSee($prevAttendance->clockOutTime);
        $response->assertDontSee($prevAttendance->formatedWorkingTime);
    }

    /**
     * 遷移した際に現在の日付が表示されることを確認するテスト
     */
    public function test_it_displays_current_date_when_page_is_loaded()
    {
        // 1. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 2. ログインする管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 3. 管理者用勤怠一覧画面を開く
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list');

        // 4. 現在の日付が表示されていることを確認
        $response->assertSee(Carbon::parse($testTime)->format('Y年n月j日') . 'の勤怠');
        $response->assertSee(Carbon::parse($testTime)->format('Y/m/d'));
    }
    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示されることをテスト
     */
    public function test_it_displays_previous_day_attendances_when_previous_day_button_is_clicked()
    {
        // 1-1. テストユーザー1のインスタンスを作成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 1-2. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 1-3. 当日の出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 1-4. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:15:00',
        ];
        $attendance->breaks()->create($breaksData);

        // 2-1. 前日の出退勤時刻をDBに保存
        $prevTime = '2026-02-14 08:00:00';

        $clockInTime = '2026-02-14 09:01:00';
        $clockOutTime = '2026-02-14 17:01:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($prevTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $prevAttendance = Attendance::create($attendanceData);

        // 2-2. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-14 10:00:00',
            'break_end_at' => '2026-02-14 10:16:00',
        ];
        $prevAttendance->breaks()->create($breaksData);

        // 3. ログインする管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 4. 管理者用勤怠一覧画面を開く
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list');

        // 5. 「←前日」リンクが表示されていることを確認
        $response->assertSee('<a href="/admin/attendance/list/?day=2026-02-14" class="month-pre">←前日</a>', false);

        // 6. 管理者用勤怠一覧画面の「←前日」リンクをクリック
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list/?day=2026-02-14');

        // 7. 前日の勤怠情報が表示されていることを確認
        $response->assertSee($prevAttendance->work_date->format('Y年n月j日') . 'の勤怠');
        $response->assertSee($prevAttendance->work_date->format('Y/m/d'));
        $response->assertSee($prevAttendance->clockInTime);
        $response->assertSee($prevAttendance->clockOutTime);
        $response->assertSee($prevAttendance->formatedWorkingTime);

        // 8. 当日の勤怠情報が表示されていないことを確認
        $response->assertDontSee($attendance->clockInTime);
        $response->assertDontSee($attendance->clockOutTime);
        $response->assertDontSee($attendance->formatedWorkingTime);
    }
    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示されることをテスト
     */
    public function test_it_displays_next_day_attendances_when_next_day_button_is_clicked()
    {
        // 1-1. テストユーザー1のインスタンスを作成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 1-2. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 1-3. 当日の出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 1-4. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:15:00',
        ];
        $attendance->breaks()->create($breaksData);

        // 2-1. 翌日の出退勤時刻をDBに保存
        $prevTime = '2026-02-16 08:00:00';

        $clockInTime = '2026-02-16 09:01:00';
        $clockOutTime = '2026-02-16 17:01:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($prevTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $nextAttendance = Attendance::create($attendanceData);

        // 2-2. 休憩時刻をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-16 10:00:00',
            'break_end_at' => '2026-02-16 10:16:00',
        ];
        $nextAttendance->breaks()->create($breaksData);

        // 3. ログインする管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 4. 管理者用勤怠一覧画面を開く
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list');

        // 5. 「翌日→」リンクが表示されていることを確認
        $response->assertSee('<a href="/admin/attendance/list/?day=2026-02-16" class="month-next">翌日→</a>', false);

        // 6. 管理者用勤怠一覧画面の「翌日→」リンクをクリック
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list/?day=2026-02-16');

        // 7. 翌日の勤怠情報が表示されていることを確認
        $response->assertSee($nextAttendance->work_date->format('Y年n月j日') . 'の勤怠');
        $response->assertSee($nextAttendance->work_date->format('Y/m/d'));
        $response->assertSee($nextAttendance->clockInTime);
        $response->assertSee($nextAttendance->clockOutTime);
        $response->assertSee($nextAttendance->formatedWorkingTime);

        // 8. 当日の勤怠情報が表示されていないことを確認
        $response->assertDontSee($attendance->clockInTime);
        $response->assertDontSee($attendance->clockOutTime);
        $response->assertDontSee($attendance->formatedWorkingTime);
    }
}
