<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }
    /**
     * 出勤ボタンが正しく機能することをテスト
     */

    public function test_user_can_clock_in()
    {
        // 1. 時刻固定
        $testTime = '2026-02-15 09:00:00';
        Carbon::setTestNow($testTime);

        // 2. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 3. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 4. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        // 5. 「出勤」ボタンが表示されていることを確認
        $response->assertSee('<button class="attendance-button-clock-in" name="action" value="clock_in">
                出勤
            </button>', false);

        // 6. 出勤処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        // 7. DBに登録されていることを確認
        $this->assertDatabaseHas(
            'attendances',
            [
                'user_id' => $user->id,
                'work_date' => Carbon::parse($testTime)->toDateString(),
                'clock_in_at' => $testTime,
            ]
        );
    }

    /**
     * 出勤は一日一回のみできることをテスト
     */
    public function test_user_cannot_clock_in_more_than_once_per_day()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. 出勤処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        // 4. 退勤処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_out']);

        // 5. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        // 6. ステータスに「退勤済」が表示されていることを確認
        $response->assertSee('退勤済', false);

        // 7. 「出勤」ボタンが表示されていないことを確認
        $response->assertDontSee('<button class="attendance-button-clock-in" name="action" value="clock_in">
                出勤
            </button>', false);
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できることをテスト
     */
    public function test_clock_in_time_is_displayed_on_attendance_index()
    {
        // 1. 時刻固定
        Carbon::setLocale('ja');
        $testTime = '2026-02-15 09:00:00';
        Carbon::setTestNow($testTime);

        // 2. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 3. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 4. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        // 5. 「出勤」ボタンが表示されていることを確認
        $response->assertSee('<button class="attendance-button-clock-in" name="action" value="clock_in">
                出勤
            </button>', false);

        // 6. 出勤処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        // 7. 勤怠一覧画面を表示
        $response = $this->actingAs($user)->get('/attendance/list/?month=2026-02');

        // 8. 勤怠一覧画面から出勤の日付、時刻を確認
        $response->assertSee(Carbon::parse($testTime)->translatedFormat('m/d(D)'));
        $response->assertSee(Carbon::parse($testTime)->format('H:i'));
    }
}
