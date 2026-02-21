<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceShowAndCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっていることをテスト
     */
    public function test_it_displays_selected_attendance_data_on_detail_page()
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
            'break_start_at' => Carbon::parse('2026-02-15 10:00:00'),
            'break_end_at' => Carbon::parse('2026-02-15 10:15:00'),
        ];
        $attendance->breaks()->create($breaksData);
        $attendance->load('breaks');

        // 2. ログインする管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 3. 管理者用勤怠一覧画面を開く
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list');

        // 4. 管理者用勤怠一覧画面に勤怠詳細画面へのリンクが表示されていることを確認
        $response->assertSee('<a href="/attendance/' . $attendance->id . '" class="detail-link">詳細</a>', false);

        // 5. 勤怠詳細画面へのリンクをクリック
        $response = $this->actingAs($adminUser)->get('/attendance/' . $attendance->id);

        // 6. 勤怠詳細画面の表示が適切かどうか確認
        $response->assertSee($user->name); // ユーザー名
        $response->assertSee($attendance->work_date->format('Y年')); // 出勤した年
        $response->assertSee($attendance->work_date->format('n月j日')); // 出勤した月日
        $response->assertSee($attendance->clockInTime); // 出勤開始時刻
        $response->assertSee($attendance->clockOutTime); // 出勤終了時刻
        foreach ($attendance->breaks as $break) {
            $response->assertSee($break->breakStartTime); // 休憩開始時刻
            $response->assertSee($break->breakEndTime); // 休憩終了時刻
        }
    }
    /**
     * 出勤時間が退勤時間より後ろになっている場合、エラーメッセージが表示されることをテスト
     */
    public function test_it_displays_validation_error_when_clock_in_is_after_clock_out()
    {
        // 1. ログインするユーザーのインスタンスを生成
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

        // 4. ログインする管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 5. 管理者用勤怠一覧画面を開く
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list');

        // 6. 管理者用勤怠一覧画面に勤怠詳細画面へのリンクが表示されていることを確認
        $response->assertSee('<a href="/attendance/' . $attendance->id . '" class="detail-link">詳細</a>', false);

        // 7. 勤怠詳細画面へのリンクをクリック
        $response = $this->actingAs($adminUser)->get('/attendance/' . $attendance->id);

        // 8. 修正情報を設定
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '17:00',
            'clock_out_at' => '08:00',
            'reason' => 'test',
        ];

        // 9. 「修正」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 10. 「修正」ボタンを押す
        $response = $this->actingAs($adminUser)->post('/attendance/' . $attendance->id, $formData);

        // 11. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['clock_out_at']);
        $response->assertSessionDoesntHaveErrors(['clock_in_at']);
        $response->assertSessionDoesntHaveErrors(['reason']);

        // 12. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance/' . $attendance->id);

        // 13. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('clock_out_at'),
            '出勤時間もしくは退勤時間が不適切な値です'
        );
    }
    /**
     * 出勤開始時間が退勤時間よりも後ろになっている場合、エラーメッセージが表示されることをテスト
     */
    public function test_it_displays_validation_error_when_break_start_is_after_clock_out()
    {
        // 1. ログインするユーザーのインスタンスを生成
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

        // 5. ログインする管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 6. 管理者用勤怠一覧画面を開く
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list');

        // 7. 管理者用勤怠一覧画面に勤怠詳細画面へのリンクが表示されていることを確認
        $response->assertSee('<a href="/attendance/' . $attendance->id . '" class="detail-link">詳細</a>', false);

        // 8. 勤怠詳細画面へのリンクをクリック
        $response = $this->actingAs($adminUser)->get('/attendance/' . $attendance->id);

        // 9. 修正情報を設定
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '09:00',
            'clock_out_at' => '17:00',
            'breaks' => [
                [
                    'id' => $breaks[0]->id,
                    'start' => '10:00',
                    'end' => '10:15',
                ],
                [
                    'id' => $breaks[1]->id,
                    'start' => '12:00',
                    'end' => '13:00',
                ],
                [
                    'id' => $breaks[2]->id,
                    'start' => '17:01',
                    'end' => '17:00',
                ],
            ],
            'reason' => 'test',
        ];

        // 10. 「修正」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 11. 「修正」ボタンを押す
        $response = $this->actingAs($adminUser)->post('/attendance/' . $attendance->id, $formData);

        // 12. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['breaks.2.start']);
        $response->assertSessionDoesntHaveErrors(['clock_out_at']);
        $response->assertSessionDoesntHaveErrors(['clock_in_at']);
        $response->assertSessionDoesntHaveErrors(['reason']);

        // 13. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance/' . $attendance->id);

        // 14. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('breaks.2.start'),
            '休憩時間が不適切な値です'
        );
    }

    /**
     * 休憩終了時間が退勤時間より後ろになっている場合、エラーメッセージが表示されることをテスト
     */
    public function test_it_displays_validation_error_when_break_end_is_after_clock_out()
    {
        // 1. ログインするユーザーのインスタンスを生成
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

        // 5. ログインする管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 6. 管理者用勤怠一覧画面を開く
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list');

        // 7. 管理者用勤怠一覧画面に勤怠詳細画面へのリンクが表示されていることを確認
        $response->assertSee('<a href="/attendance/' . $attendance->id . '" class="detail-link">詳細</a>', false);

        // 8. 勤怠詳細画面へのリンクをクリック
        $response = $this->actingAs($adminUser)->get('/attendance/' . $attendance->id);

        // 9. 修正情報を設定
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '09:00',
            'clock_out_at' => '17:00',
            'breaks' => [
                [
                    'id' => $breaks[0]->id,
                    'start' => '10:00',
                    'end' => '10:15',
                ],
                [
                    'id' => $breaks[1]->id,
                    'start' => '12:00',
                    'end' => '13:00',
                ],
                [
                    'id' => $breaks[2]->id,
                    'start' => '15:01',
                    'end' => '17:01',
                ],
            ],
            'reason' => 'test',
        ];
        // 10. 「修正」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 11. 「修正」ボタンを押す
        $response = $this->actingAs($adminUser)->post('/attendance/' . $attendance->id, $formData);

        // 12. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['clock_out_at']);
        $response->assertSessionHasErrors(['breaks.2.end']);
        $response->assertSessionDoesntHaveErrors(['clock_in_at']);
        $response->assertSessionDoesntHaveErrors(['reason']);

        // 13. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance/' . $attendance->id);

        // 14. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('clock_out_at'),
            '休憩時間もしくは退勤時間が不適切な値です'
        );
        $this->assertEquals(
            session('errors')->first('breaks.2.end'),
            '休憩時間もしくは退勤時間が不適切な値です'
        );
    }
    /**
     * 備考欄が未入力の場合のエラーメッセージが表示されることをテスト
     */
    public function test_it_displays_validation_error_when_reason_is_empty()
    {
        // 1. ログインするユーザーのインスタンスを生成
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

        // 4. ログインする管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 5. 管理者用勤怠一覧画面を開く
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list');

        // 6. 管理者用勤怠一覧画面に勤怠詳細画面へのリンクが表示されていることを確認
        $response->assertSee('<a href="/attendance/' . $attendance->id . '" class="detail-link">詳細</a>', false);

        // 7. 勤怠詳細画面へのリンクをクリック
        $response = $this->actingAs($adminUser)->get('/attendance/' . $attendance->id);

        // 8. 修正情報を設定
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '09:00',
            'clock_out_at' => '17:00',
            'reason' => '',
        ];

        // 9. 「修正」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 10. 「修正」ボタンを押す
        $response = $this->actingAs($adminUser)->post('/attendance/' . $attendance->id, $formData);

        // 11. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['reason']);
        $response->assertSessionDoesntHaveErrors(['clock_out_at']);
        $response->assertSessionDoesntHaveErrors(['clock_in_at']);

        // 12. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance/' . $attendance->id);

        // 13. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('reason'),
            '備考を入力してください'
        );
    }
}
