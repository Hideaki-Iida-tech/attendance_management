<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * 退勤ボタンが正しく機能することをテスト
     */
    public function test_user_can_clock_out()
    {
        // 1. 時刻固定
        $testTime = '2026-02-15 09:00:00';
        Carbon::setTestNow($testTime);

        // 2. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 3. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 4. 出勤処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        // 5. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        // 6. 「退勤」ボタンが表示されていることを確認
        $response->assertSee('<button class="attendance-button-clock-out" name="action" value="clock_out">
                退勤
            </button>', false);

        // 7. 退勤処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_out']);

        // 8. DBに登録されていることを確認
        $this->assertDatabaseHas(
            'attendances',
            [
                'user_id' => $user->id,
                'work_date' => Carbon::parse($testTime)->toDateString(),
                'clock_out_at' => $testTime,
            ]
        );
    }
    /**
     * 退勤時刻が勤怠一覧画面で確認できることをテスト
     */
    public function test_clock_out_time_is_displayed_on_attendance_index()
    {
        // 1. 出勤時刻を設定
        Carbon::setLocale('ja');
        $clockInTime = '2026-02-15 09:00:00';
        Carbon::setTestNow($clockInTime);

        // 2. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 3. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 4. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        // 5. 出勤処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        // 6. 退勤時刻を設定
        $clockOutTime = '2026-02-15 17:00:00';
        Carbon::setTestNow($clockOutTime);

        // 7. 退勤処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_out']);

        // 8. 勤怠一覧画面を表示
        $response = $this->actingAs($user)->get('/attendance/list');

        // 9. 勤怠一覧画面から退勤の日付、時刻を確認
        $response->assertSee(Carbon::parse($clockOutTime)->translatedFormat('m/d(D)'));
        $response->assertSee(Carbon::parse($clockOutTime)->format('H:i'));
    }
}
