<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示されることをテスト
     */
    public function test_it_displays_off_duty_status_when_user_is_off_duty()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        // 4. ステータスが「勤務外」であることを確認
        $response->assertSee('勤務外');
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示されることをテスト
     */
    public function test_it_displays_is_working_status_when_user_is_working()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. DBに出勤情報を記録
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => now(),
        ];

        Attendance::create($attendanceData);

        // 4. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        // 5. 「出勤中」と表示されていることを確認
        $response->assertSee('出勤中');
    }

    /**
     * 休憩中の場合、勤怠ステータスが正しく表示されることをテスト
     */
    public function test_it_displays_on_break_status_when_user_is_on_break()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. DBに出勤情報を記録
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => now(),
        ];

        $attendance = Attendance::create($attendanceData);

        // 4. 休憩開始時刻を記録
        $breakData = [
            'break_start_at' => now(),
        ];
        $attendance->breaks()->create($breakData);

        // 5. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        // 6. 「休憩中」と表示されていることを確認
        $response->assertSee('休憩中');
    }

    /**
     * 退勤済みの場合、勤怠ステータスが正しく表示されることをテスト
     */
    public function test_it_displays_finished_status_when_attendance_is_completed()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. DBに出勤情報を記録
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => now(),
            'clock_out_at' => now(),
        ];

        $attendance = Attendance::create($attendanceData);

        // 4. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        // 5. 「退勤済」と表示されていることを確認
        $response->assertSee('退勤済');
    }
}
