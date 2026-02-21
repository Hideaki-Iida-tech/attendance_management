<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\GeneralUserSeeder::class,]);
    }
    /**
     * 自分が行った勤怠情報がすべて表示されていることをテスト
     */
    public function test_attendance_index_displays_all_user_attendances()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. 出勤時刻、退勤時刻、休憩開始時刻、休憩終了時刻その0を設定
        Carbon::setLocale('ja');
        $testClockInTime[] = '2026-02-15 09:00:00';
        $testClockOutTime[] = '2026-02-15 17:00:00';
        $testBreakStartTime[] = '2026-02-15 12:00:00';
        $testBreakEndTime[] = '2026-02-15 13:00:00';

        // 4. 出勤時刻、退勤時刻、休憩開始時刻、休憩終了時刻その1を設定
        $testClockInTime[] = '2026-02-16 09:01:00';
        $testClockOutTime[] = '2026-02-16 17:01:00';
        $testBreakStartTime[] = '2026-02-16 12:00:00';
        $testBreakEndTime[] = '2026-02-16 13:01:00';

        // 5. 出勤時刻、退勤時刻、休憩開始時刻、休憩終了時刻その2を設定
        $testClockInTime[] = '2026-02-17 09:02:00';
        $testClockOutTime[] = '2026-02-17 17:02:00';
        $testBreakStartTime[] = '2026-02-17 12:00:00';
        $testBreakEndTime[] = '2026-02-17 13:02:00';

        // 6. 3件のテストケースについて、出退勤、休憩開始終了処理を実行
        foreach ($testClockInTime as $idx => $clockInTime) {
            // 6-1. 出勤処理を行う
            Carbon::setTestNow($clockInTime);
            $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

            // 6-2. 休憩開始処理を行う
            Carbon::setTestNow($testBreakStartTime[$idx]);
            $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_start']);
            // 6-3 休憩終了処理を行う
            Carbon::setTestNow($testBreakEndTime[$idx]);
            $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_end']);
            // 6-4. 退勤処理を行う
            Carbon::setTestNow($testClockOutTime[$idx]);
            $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_out']);
        }

        // 7. 別のユーザーの出勤時刻、退勤時刻、休憩開始時刻、休憩終了時刻を設定
        $anotherClockInTime = '2026-02-18 09:03:00';
        $anotherClockOutTime = '2026-02-18 17:03:00';
        $anotherBreakStartTime = '2026-02-18 12:00:00';
        $anotherBreakEndTime = '2026-02-18 13:03:00';

        // 8. 他のユーザーのインスタンスを生成
        $anotherUser = User::where('name', 'テストユーザー2')
            ->first() ?? User::inRandomOrder()->first();

        // 9. 生成した他のuserについて、メール認証済みにする
        $anotherUser->markEmailAsVerified();


        // 10-1. 出勤処理を行う
        Carbon::setTestNow($anotherClockInTime);
        $response = $this->actingAs($anotherUser)->post('/attendance', ['action' => 'clock_in']);

        // 10-2. 休憩開始処理を行う
        Carbon::setTestNow($anotherBreakStartTime);
        $response = $this->actingAs($anotherUser)->post('/attendance', ['action' => 'break_start']);
        // 10-3 休憩終了処理を行う
        Carbon::setTestNow($anotherBreakEndTime);
        $response = $this->actingAs($anotherUser)->post('/attendance', ['action' => 'break_end']);
        // 10-4. 退勤処理を行う
        Carbon::setTestNow($anotherClockOutTime);
        $response = $this->actingAs($anotherUser)->post('/attendance', ['action' => 'clock_out']);

        // 11. 勤怠一覧画面を表示
        $response = $this->actingAs($user)->get('/attendance/list/?month=2026-02');

        // 12. 3件のテストケースすべてが一覧画面に表示されていることを確認
        foreach ($testClockInTime as $idx => $clockInTime) {
            $response->assertSee(Carbon::parse($clockInTime)->translatedFormat('m/d(D)'));
            $response->assertSee(Carbon::parse($clockInTime)->format('H:i'));
            $response->assertSee(Carbon::parse($testClockOutTime[$idx])->format('H:i'));

            $start = Carbon::parse($testBreakStartTime[$idx]);
            $end   = Carbon::parse($testBreakEndTime[$idx]);

            $minutes = $start->diffInMinutes($end);

            $expected = sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
            $response->assertSee($expected);
        }

        // 13. 他のユーザーのテストケースが一覧画面に表示されていないことを確認
        $response->assertSee(Carbon::parse($anotherClockInTime)->translatedFormat('m/d(D)'));
        $response->assertDontSee(Carbon::parse($anotherClockInTime)->format('H:i'));
        $response->assertDontSee(Carbon::parse($anotherClockOutTime)->format('H:i'));

        $start = Carbon::parse($anotherBreakStartTime);
        $end   = Carbon::parse($anotherBreakEndTime);

        $minutes = $start->diffInMinutes($end);

        $expected = sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
        $response->assertDontSee($expected);
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示されることをテスト
     */
    public function test_attendance_index_displays_current_month_by_default()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. 勤怠一覧画面を表示
        $response = $this->actingAs($user)->get('/attendance/list');

        // 4. 現在の月が表示されていることを確認
        $response->assertSee(now()->format('Y/m'));
    }
    /**
     * 「前月」を押下したときに表示月の前月の情報が表示されることをテスト
     */
    public function test_attendance_index_displays_previous_month_when_previous_button_clicked()
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

        // 4. テスト月の出退勤時刻をDBに保存
        $presentClockInTime = '2026-02-15 09:00:00';
        $presentClockOutTime = '2026-02-15 17:00:00';
        $presentData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($presentClockInTime),
            'clock_out_at' => Carbon::parse($presentClockOutTime),
        ];
        Attendance::create($presentData);

        // 5. 前月の日時を設定
        $previousTime = '2026-01-15 09:00:00';
        $previousClockInTime = '2026-01-15 09:01:00';
        $previousClockOutTime = '2026-01-15 17:01:00';

        // 6. 前月の出退勤時刻をDBに保存
        $previousData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($previousTime)->toDateString(),
            'clock_in_at' => Carbon::parse($previousClockInTime),
            'clock_out_at' => Carbon::parse($previousClockOutTime),
        ];
        Attendance::create($previousData);

        // 7. 勤怠一覧画面を表示
        $response = $this->actingAs($user)->get('/attendance/list/?month=2026-02');

        // 8. 勤怠一覧画面に前月のリンクが存在することを確認
        $response->assertSee('<a href="/attendance/list?month=2026-01" class="month-pre">←前月</a>', false);

        // 8. 前月の勤怠一覧を表示
        $response = $this->actingAs($user)->get('/attendance/list/?month=2026-01');

        // 9. 前月の勤怠一覧に今月の勤怠情報が表示されていないことを確認
        $response->assertDontSee(Carbon::parse($presentClockInTime)->format('Y/m'));
        $response->assertDontSee(Carbon::parse($presentClockInTime)->translatedFormat('m/d(D)'));
        $response->assertDontSee(Carbon::parse($presentClockInTime)->format('H:i'));
        $response->assertDontSee(Carbon::parse($presentClockOutTime)->format('H:i'));

        // 9. 前月の勤怠一覧に前月の勤怠情報が表示されていることを確認
        $response->assertSee(Carbon::parse($previousClockInTime)->format('Y/m'));
        $response->assertSee(Carbon::parse($previousClockInTime)->translatedFormat('m/d(D)'));
        $response->assertSee(Carbon::parse($previousClockInTime)->format('H:i'));
        $response->assertSee(Carbon::parse($previousClockOutTime)->format('H:i'));
    }

    /**
     * 「翌月」を押下したときに表示月の前月の情報が表示されることをテスト
     */
    public function test_attendance_index_displays_next_month_when_next_button_clicked()
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

        // 4. テスト月の出退勤時刻をDBに保存
        $presentClockInTime = '2026-02-15 09:00:00';
        $presentClockOutTime = '2026-02-15 17:00:00';
        $presentData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($presentClockInTime),
            'clock_out_at' => Carbon::parse($presentClockOutTime),
        ];
        Attendance::create($presentData);

        // 5. 翌月の日時を設定
        $nextTime = '2026-03-15 09:00:00';
        $nextClockInTime = '2026-03-15 09:01:00';
        $nextClockOutTime = '2026-03-15 17:01:00';

        // 6. 翌月の出退勤時刻をDBに保存
        $nextData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($nextTime)->toDateString(),
            'clock_in_at' => Carbon::parse($nextClockInTime),
            'clock_out_at' => Carbon::parse($nextClockOutTime),
        ];
        Attendance::create($nextData);

        // 7. 勤怠一覧画面を表示
        $response = $this->actingAs($user)->get('/attendance/list/?month=2026-02');

        // 8. 勤怠一覧画面に前月のリンクが存在することを確認
        $response->assertSee('<a href="/attendance/list?month=2026-03" class="month-next">次月→</a>', false);

        // 8. 翌月の勤怠一覧を表示
        $response = $this->actingAs($user)->get('/attendance/list/?month=2026-03');

        // 9. 翌月の勤怠一覧に今月の勤怠情報が表示されていないことを確認
        $response->assertDontSee(Carbon::parse($presentClockInTime)->format('Y/m'));
        $response->assertDontSee(Carbon::parse($presentClockInTime)->translatedFormat('m/d(D)'));
        $response->assertDontSee(Carbon::parse($presentClockInTime)->format('H:i'));
        $response->assertDontSee(Carbon::parse($presentClockOutTime)->format('H:i'));

        // 9. 翌月の勤怠一覧に翌月の勤怠情報が表示されていることを確認
        $response->assertSee(Carbon::parse($nextClockInTime)->format('Y/m'));
        $response->assertSee(Carbon::parse($nextClockInTime)->translatedFormat('m/d(D)'));
        $response->assertSee(Carbon::parse($nextClockInTime)->format('H:i'));
        $response->assertSee(Carbon::parse($nextClockOutTime)->format('H:i'));
    }
    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移することをテスト
     */
    public function test_it_redirects_to_attendance_show_page_when_detail_button_is_clicked()
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

        // 7. 勤怠一覧画面を表示
        $response = $this->actingAs($user)->get('/attendance/list/?month=2026-02');

        // 8. 該当の勤怠レコードの「詳細」リンクが表示されていることを確認
        $response->assertSee('<a href="/attendance/' . $attendance->id . '" class="detail-link">詳細</a>', false);

        // 9. 「詳細」リンクをクリック
        $response = $this->actingAs($user)->get('/attendance/' . $attendance->id);

        // 10. 勤怠詳細画面が表示されていることを確認
        $response->assertSee('勤怠詳細');
    }
}
