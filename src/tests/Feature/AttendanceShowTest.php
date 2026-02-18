<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }
    /**
     * 勤怠詳細画面の名前がログインユーザーの氏名になっていることをテスト
     */
    public function test_attendance_show_displays_logged_in_user_name()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. ユーザー名を取得
        $name = $user->name;

        // 4. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 5. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 6. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/' . $attendance->id);

        // 7. 「名前」がログインユーザーの名前になっていることを確認
        $response->assertSee($name);
    }
    /**
     * 勤怠詳細画面の「日付」が選択した日付になっていることをテスト
     */
    public function test_attendance_show_displays_date_of_selected_attendance()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 4. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 5. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/' . $attendance->id);

        // 6. 「日付」が選択した日付になっていることを確認
        $response->assertSee($attendance->work_date->format('Y年'));
        $response->assertSee($attendance->work_date->format('n月j日'));
    }
    /**
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致していることをテスト
     */
    public function test_attendance_show_displays_clock_in_and_clock_out_time_of_logged_in_user()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 4. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 5. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/' . $attendance->id);

        // 6. 「出勤」「退勤」が一致していることを確認
        $response->assertSee($attendance->clock_in_at->format('H:i'));
        $response->assertSee($attendance->clock_out_at->format('H:i'));
    }
    /**
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致していることを確認
     */
    public function test_attendance_show_displays_break_times_of_logged_in_user()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 4. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);



        // 5. 休憩開始・終了時刻をDBに保存
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

        // 6. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/' . $attendance->id);

        // 7. 「休憩」が一致していることを確認
        foreach ($cases as $case) {
            $response->assertSee(Carbon::parse($case['break_start_at'])->format('H:i'));
            $response->assertSee(Carbon::parse($case['break_end_at'])->format('H:i'));
        }
    }
}
