<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceBreakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }
    /**
     * 休憩ボタンが正しく機能することをテスト
     */
    public function test_user_can_start_break()
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

        // 6. 「出勤中」とステータスが表示されていることを確認
        $response->assertSee('出勤中');

        // 5. 「休憩入」ボタンが表示されていることを確認
        $response->assertSee('<button class="attendance-button-break-start" name="action" value="break_start">
                休憩入
            </button>', false);

        // 6. 休憩開始処理を行う
        $this->actingAs($user)->post('/attendance', ['action' => 'break_start']);

        // 7. DBに登録されていることを確認
        $this->assertDatabaseHas(
            'breaks',
            ['break_start_at' => $testTime]
        );
    }
    /**
     * 休憩は一日に何回もできることをテスト
     */
    public function test_user_can_take_multiple_breaks_in_a_day()
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

        // 6. 休憩開始処理を行う
        $this->actingAs($user)->post('/attendance', ['action' => 'break_start']);

        // 7. 休憩終了処理を行う
        $this->actingAs($user)->post('/attendance', ['action' => 'break_end']);

        // 8. 勤怠打刻画面を再表示
        $response = $this->actingAs($user)->get('/attendance');

        // 9. 「休憩入」ボタンが表示されていることを確認
        $response->assertSee('<button class="attendance-button-break-start" name="action" value="break_start">
                休憩入
            </button>', false);
    }
    /**
     * 休憩戻ボタンが正しく機能することをテスト
     */
    public function test_user_can_end_break()
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

        // 6. 休憩開始処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_start']);

        // 7. 勤怠打刻画面を再表示
        $response = $this->actingAs($user)->get('/attendance');

        // 8. 「休憩戻」ボタンが表示されていることを確認
        $response->assertSee('<button class="attendance-button-break-end" name="action" value="break_end">
                休憩戻
            </button>', false);

        // 9. 休憩終了処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_end']);

        // 10. DBに登録されていることを確認
        $this->assertDatabaseHas(
            'breaks',
            ['break_end_at' => $testTime]
        );
    }
    /**
     * 休憩戻は一日に何回もできることをテスト
     */
    public function test_user_can_end_break_multiple_times_per_day()
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

        // 6. 休憩開始処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_start']);

        // 7. 休憩終了処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_end']);

        // 8. 再度休憩開始処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_start']);

        // 9. 勤怠打刻画面を再表示
        $response = $this->actingAs($user)->get('/attendance');

        // 10. 「休憩戻」ボタンが表示されていることを確認
        $response->assertSee('<button class="attendance-button-break-end" name="action" value="break_end">
                休憩戻
            </button>', false);
    }
    /**
     * 休憩時刻が勤怠一覧画面で確認できるテスト
     */
    public function test_attendance_index_displays_break_time()
    {
        // 1. 出勤時刻設定
        $clockInTime = '2026-02-15 09:00:00';
        Carbon::setTestNow($clockInTime);

        // 2. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 3. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 4. 出勤処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        // 5. 休憩開始時刻設定
        $breakStartTime = '2026-02-15 10:00:00';
        Carbon::setTestNow($breakStartTime);

        // 6. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        // 7. 休憩開始処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_start']);

        // 8. 休憩終了時刻設定
        $breakEndTime = '2026-02-15 10:30:00';
        Carbon::setTestNow($breakEndTime);

        // 9. 休憩終了処理を行う
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_end']);

        // 10. 勤怠一覧画面を表示
        $response = $this->actingAs($user)->get('/attendance/list/?month=2026-02');

        // 11.勤怠一覧画面に休憩の日付及び時間があることを確認
        $response->assertSee(Carbon::parse($clockInTime)->translatedFormat('m/d(D)'));

        $start = Carbon::parse($breakStartTime);
        $end   = Carbon::parse($breakEndTime);

        $minutes = $start->diffInMinutes($end);

        $expected = sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
        $response->assertSee($expected);
    }
}
