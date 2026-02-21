<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceChangeRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * 出勤時間が退勤時間より後ろになっている場合、エラーメッセージが表示されることをテスト
     */
    public function test_it_displays_validation_error_when_clock_in_is_after_clock_out()
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
        $response->assertStatus(200);

        // 6. 修正情報を設定
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '17:00',
            'clock_out_at' => '08:00',
            'reason' => 'test',
        ];

        // 7. 「修正」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 8. 「修正」ボタンを押す
        $response = $this->actingAs($user)->post('/attendance/' . $attendance->id, $formData);

        // 9. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['clock_out_at']);
        $response->assertSessionDoesntHaveErrors(['clock_in_at']);
        $response->assertSessionDoesntHaveErrors(['work_date']);
        $response->assertSessionDoesntHaveErrors(['reason']);

        // 10. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance/' . $attendance->id);

        // 11. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('clock_out_at'),
            '出勤時間もしくは退勤時間が不適切な値です'
        );
    }

    /**
     * 休憩開始時間が退勤時間より後ろになっている場合、エラーメッセージが表示されることをテスト
     */
    public function test_it_displays_validation_error_when_break_start_is_after_clock_out()
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
        $breaks = $attendance->breaks()->orderBy('break_start_at', 'asc')->get();

        // 6. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/' . $attendance->id);
        $response->assertStatus(200);

        // 7. 修正情報を設定
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

        // 8. 「修正」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 9. 「修正」ボタンを押す
        $response = $this->actingAs($user)->post('/attendance/' . $attendance->id, $formData);

        // 10. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['breaks.2.start']);
        $response->assertSessionDoesntHaveErrors(['work_date']);
        $response->assertSessionDoesntHaveErrors(['clock_out_at']);
        $response->assertSessionDoesntHaveErrors(['clock_in_at']);
        $response->assertSessionDoesntHaveErrors(['reason']);

        // 11. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance/' . $attendance->id);

        // 12. エラーメッセージがセッションに入っているか確認
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
        $breaks = $attendance->breaks()->orderBy('break_start_at', 'asc')->get();

        // 6. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/' . $attendance->id);
        $response->assertStatus(200);

        // 7. 修正情報を設定
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
        // 8. 「修正」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 9. 「修正」ボタンを押す
        $response = $this->actingAs($user)->post('/attendance/' . $attendance->id, $formData);

        // 10. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['clock_out_at']);
        $response->assertSessionHasErrors(['breaks.2.end']);
        $response->assertSessionDoesntHaveErrors(['work_date']);
        $response->assertSessionDoesntHaveErrors(['clock_in_at']);
        $response->assertSessionDoesntHaveErrors(['reason']);

        // 11. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance/' . $attendance->id);

        // 12. エラーメッセージがセッションに入っているか確認
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
        $response->assertStatus(200);

        // 6. 修正情報を設定
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '09:00',
            'clock_out_at' => '17:00',
            'reason' => '',
        ];

        // 7. 「修正」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 8. 「修正」ボタンを押す
        $response = $this->actingAs($user)->post('/attendance/' . $attendance->id, $formData);

        // 9. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['reason']);
        $response->assertSessionDoesntHaveErrors(['clock_out_at']);
        $response->assertSessionDoesntHaveErrors(['clock_in_at']);
        $response->assertSessionDoesntHaveErrors(['work_date']);

        // 10. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance/' . $attendance->id);

        // 11. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('reason'),
            '備考を入力してください'
        );
    }
    /**
     * 修正申請処理が実行されることをテスト
     */
    public function test_it_processes_attendance_correction_request()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. ログインユーザー名を取得
        $guestUserName = $user->name;

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

        // 6. 休憩開始・終了時刻をDBに保存
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

        // 7. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/' . $attendance->id);
        $response->assertStatus(200);

        // 8. 修正情報を設定
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '09:01',
            'clock_out_at' => '17:01',
            'breaks' => [
                [
                    'id' => $breaks[0]->id,
                    'start' => '10:00',
                    'end' => '10:16',
                ],
                [
                    'id' => $breaks[1]->id,
                    'start' => '12:00',
                    'end' => '13:01',
                ],
                [
                    'id' => $breaks[2]->id,
                    'start' => '15:01',
                    'end' => '15:15',
                ],
            ],
            'reason' => 'test',
        ];

        // 9. 「修正」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 10. 「修正」ボタンを押す
        $response = $this->actingAs($user)
            ->post('/attendance/' . $attendance->id, $formData);

        // 11. 作成した申請レコードを取得
        $requestRecord = AttendanceChangeRequest::with('attendance', 'breaks')
            ->where('attendance_id', $attendance->id)
            ->latest('updated_at')
            ->first();

        // 12. 一般ユーザーからログアウト
        $logoutResponse = $this->post('/logout');

        // 13. ログインする管理者ユーザーのインスタンスを生成
        $adminUser = User::where('email', 'admin@example.com')
            ->first();

        // 14. 修正申請承認画面（管理者）を開く
        $response = $this->actingAs($adminUser)
            ->get('/stamp_correction_request/approve/' . $requestRecord->id);

        // 15. 修正申請承認画面の表示を確認
        $response->assertSee($guestUserName);
        $response->assertSee($requestRecord->work_date->format('Y年'));
        $response->assertSee($requestRecord->work_date->format('n月j日'));
        $response->assertSee($requestRecord->attendance->new_clock_in_at->format('H:i'));
        $response->assertSee($requestRecord->attendance->new_clock_out_at->format('H:i'));
        foreach ($requestRecord->breaks as $break) {
            $response->assertSee($break->new_break_start_at->format('H:i'));
            $response->assertSee($break->new_break_end_at->format('H:i'));
        }

        // 16. 申請一覧画面（管理者）を開く
        $response = $this->actingAs($adminUser)->get('/stamp_correction_request/list');

        // 17. 申請一覧画面の表示を確認
        $response->assertSee('承認待ち');
        $response->assertSee($guestUserName);
        $response->assertSee($requestRecord->work_date->format('Y/m/d'));
        $response->assertSee($requestRecord->reason);
        $response->assertSee($requestRecord->created_at->format('Y/m/d'));
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていることをテスト
     */
    public function test_it_displays_all_pending_requests_of_logged_in_user()
    {
        // 1-1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 1-2 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 1-3. ログインユーザー名を取得
        $userName = $user->name;

        // 1-4. テスト日時を固定（ログインユーザーの1件目）
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 1-5. 出退勤時刻をDBに保存（ログインユーザーの1件目）
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance[] = Attendance::create($attendanceData);

        // 1-6. 休憩開始・終了時刻をDBに保存（ログインユーザーの1件目）
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
            $attendance[0]->breaks()->create($case);
        }
        $breaks = $attendance[0]->breaks()
            ->orderBy('break_start_at', 'asc')->get();

        // 1-7. 勤怠詳細ページを開く（ログインユーザーの1件目）
        $response = $this->actingAs($user)
            ->get('/attendance/' . $attendance[0]->id);
        $response->assertStatus(200);

        // 1-8. 修正情報を設定（ログインユーザーの1件目）
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '09:01',
            'clock_out_at' => '17:01',
            'breaks' => [
                [
                    'id' => $breaks[0]->id,
                    'start' => '10:00',
                    'end' => '10:16',
                ],
                [
                    'id' => $breaks[1]->id,
                    'start' => '12:00',
                    'end' => '13:01',
                ],
                [
                    'id' => $breaks[2]->id,
                    'start' => '15:01',
                    'end' => '15:15',
                ],
            ],
            'reason' => 'test1',
        ];

        // 1-9. 「修正」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 1-10. 「修正」ボタンを押す（ログインユーザーの1件目）
        $response = $this->actingAs($user)
            ->post('/attendance/' . $attendance[0]->id, $formData);

        // 1-11. 作成した申請レコードを取得（ログインユーザーの1件目）
        $requestRecord[] = AttendanceChangeRequest::with('attendance', 'breaks')
            ->where('attendance_id', $attendance[0]->id)
            ->latest('updated_at')
            ->first();

        // 2-1. テスト日時を固定（ログインユーザーの2件目）
        $testTime = '2026-02-16 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 2-2. 出退勤時刻をDBに保存（ログインユーザーの2件目）
        $clockInTime = '2026-02-16 09:30:00';
        $clockOutTime = '2026-02-16 17:30:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance[] = Attendance::create($attendanceData);

        // 2-3. 休憩開始・終了時刻をDBに保存（ログインユーザーの2件目）
        $cases = [
            [
                'break_start_at' => '2026-02-16 10:30:00',
                'break_end_at' => '2026-02-16 10:45:00',
            ],
            [
                'break_start_at' => '2026-02-16 12:30:00',
                'break_end_at' => '2026-02-16 13:30:00',
            ],
            [
                'break_start_at' => '2026-02-16 15:30:00',
                'break_end_at' => '2026-02-16 15:45:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance[1]->breaks()->create($case);
        }
        $breaks = $attendance[1]->breaks()
            ->orderBy('break_start_at', 'asc')->get();

        // 2-4. 勤怠詳細ページを開く（ログインユーザーの2件目）
        $response = $this->actingAs($user)
            ->get('/attendance/' . $attendance[1]->id);
        $response->assertStatus(200);

        // 2-5. 修正情報を設定（ログインユーザーの2件目）
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '09:31',
            'clock_out_at' => '17:31',
            'breaks' => [
                [
                    'id' => $breaks[0]->id,
                    'start' => '10:30',
                    'end' => '10:47',
                ],
                [
                    'id' => $breaks[1]->id,
                    'start' => '12:30',
                    'end' => '13:47',
                ],
                [
                    'id' => $breaks[2]->id,
                    'start' => '15:30',
                    'end' => '15:47',
                ],
            ],
            'reason' => 'test2',
        ];

        // 2-6. 「修正」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 2-7. 「修正」ボタンを押す（ログインユーザーの2件目）
        $response = $this->actingAs($user)
            ->post('/attendance/' . $attendance[1]->id, $formData);

        // 2-8. 作成した申請レコードを取得（ログインユーザーの2件目）
        $requestRecord[] = AttendanceChangeRequest::with('attendance', 'breaks')
            ->where('attendance_id', $attendance[1]->id)
            ->latest('updated_at')
            ->first();

        // 3-1. 非ログインユーザーのインスタンスを生成（非ログインユーザー用）
        $otherUser = User::where('name', 'テストユーザー2')
            ->first() ?? User::inRandomOrder()->first();

        // 3-2. 生成したotherUserについて、メール認証済みにする（非ログインユーザー用）
        $otherUser->markEmailAsVerified();

        // 3-3. 非ログインユーザー名を取得（非ログインユーザー用）
        $otherUserName = $otherUser->name;

        // 3-4. テスト日時を固定（非ログインユーザー用）
        $testTime = '2026-02-17 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 3-5. 出退勤時刻をDBに保存（非ログインユーザー用）
        $clockInTime = '2026-02-17 10:00:00';
        $clockOutTime = '2026-02-17 18:00:00';
        $attendanceData = [
            'user_id' => $otherUser->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance[] = Attendance::create($attendanceData);

        // 3-6. 休憩開始・終了時刻をDBに保存（非ログインユーザー用）
        $cases = [
            [
                'break_start_at' => '2026-02-17 11:00:00',
                'break_end_at' => '2026-02-17 11:18:00',
            ],
            [
                'break_start_at' => '2026-02-17 13:00:00',
                'break_end_at' => '2026-02-17 14:03:00',
            ],
            [
                'break_start_at' => '2026-02-17 16:00:00',
                'break_end_at' => '2026-02-17 16:18:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance[2]->breaks()->create($case);
        }
        $breaks = $attendance[2]->breaks()
            ->orderBy('break_start_at', 'asc')->get();

        // 3-7. 勤怠詳細ページを開く（非ログインユーザー用）
        $response = $this->actingAs($otherUser)
            ->get('/attendance/' . $attendance[2]->id);
        $response->assertStatus(200);

        // 3-8. 修正情報を設定（非ログインユーザー用）
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '10:01',
            'clock_out_at' => '18:01',
            'breaks' => [
                [
                    'id' => $breaks[0]->id,
                    'start' => '11:00',
                    'end' => '11:19',
                ],
                [
                    'id' => $breaks[1]->id,
                    'start' => '13:00',
                    'end' => '14:04',
                ],
                [
                    'id' => $breaks[2]->id,
                    'start' => '16:00',
                    'end' => '16:19',
                ],
            ],
            'reason' => 'test3',
        ];

        // 3-9. 「修正」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 3-10. 「修正」ボタンを押す（非ログインユーザー用）
        $response = $this->actingAs($otherUser)
            ->post('/attendance/' . $attendance[2]->id, $formData);

        // 3-11. 作成した申請レコードを取得（非ログインユーザー用）
        $requestRecord[] = AttendanceChangeRequest::with('attendance', 'breaks')
            ->where('attendance_id', $attendance[2]->id)
            ->latest('updated_at')
            ->first();

        // 4-1. テスト日時を固定（ログインユーザーの3件目/承認済み用）
        $testTime = '2026-02-18 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 4-2. 出退勤時刻をDBに保存（ログインユーザーの3件目/承認済み用）
        $clockInTime = '2026-02-18 10:30:00';
        $clockOutTime = '2026-02-18 18:30:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance[] = Attendance::create($attendanceData);

        // 4-3. 休憩開始・終了時刻をDBに保存（ログインユーザーの3件目/承認済み用）
        $cases = [
            [
                'break_start_at' => '2026-02-18 11:30:00',
                'break_end_at' => '2026-02-18 11:49:00',
            ],
            [
                'break_start_at' => '2026-02-18 13:30:00',
                'break_end_at' => '2026-02-18 14:34:00',
            ],
            [
                'break_start_at' => '2026-02-18 16:30:00',
                'break_end_at' => '2026-02-18 16:49:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance[3]->breaks()->create($case);
        }
        $breaks = $attendance[3]->breaks()
            ->orderBy('break_start_at', 'asc')->get();

        // 4-4. 勤怠詳細ページを開く（ログインユーザーの3件目/承認済み用）
        $response = $this->actingAs($user)
            ->get('/attendance/' . $attendance[3]->id);
        $response->assertStatus(200);

        // 4-5. 修正情報を設定（ログインユーザーの3件目/承認済み用）
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '10:31',
            'clock_out_at' => '18:31',
            'breaks' => [
                [
                    'id' => $breaks[0]->id,
                    'start' => '11:30',
                    'end' => '11:50',
                ],
                [
                    'id' => $breaks[1]->id,
                    'start' => '13:30',
                    'end' => '14:35',
                ],
                [
                    'id' => $breaks[2]->id,
                    'start' => '16:30',
                    'end' => '16:50',
                ],
            ],
            'reason' => 'test4',
        ];

        // 4-6. 「修正」ボタンが表示されていることを確認（ログインユーザーの3件目/承認済み用）
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 4-7. 「修正」ボタンを押す（ログインユーザーの3件目/承認済み用）
        $response = $this->actingAs($user)
            ->post('/attendance/' . $attendance[3]->id, $formData);

        // 4-8. 作成した申請レコードを取得（ログインユーザーの3件目/承認済み用）
        $requestRecord[] = AttendanceChangeRequest::with('attendance', 'breaks')
            ->where('attendance_id', $attendance[3]->id)
            ->latest('updated_at')->first();

        // 4-9. 承認処理
        $adminUser = User::where('email', 'admin@example.com')
            ->first();
        $response = $this->actingAs($adminUser)
            ->post('/stamp_correction_request/approve/' . $requestRecord[3]->id);

        // 5. 申請一覧画面（一般ログインユーザー）を開く
        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        // 6. ログインユーザーが行った申請が全て表示されていることを確認
        $response->assertSee('承認待ち');
        $response->assertSee($userName);

        // 6-1. 1件目
        $this->assertDatabaseHas(
            'attendance_change_requests',
            [
                'id' => $requestRecord[0]->id,
                'status' => ApplicationStatus::PENDING->value,
            ]
        );
        $response->assertSee($requestRecord[0]->work_date->format('Y/m/d'));
        $response->assertSee($requestRecord[0]->reason);
        $response->assertSee($requestRecord[0]->created_at->format('Y/m/d'));

        // 6-2. 2件目
        $this->assertDatabaseHas(
            'attendance_change_requests',
            [
                'id' => $requestRecord[1]->id,
                'status' => ApplicationStatus::PENDING->value,
            ]
        );
        $response->assertSee($requestRecord[1]->work_date->format('Y/m/d'));
        $response->assertSee($requestRecord[1]->reason);
        $response->assertSee($requestRecord[1]->created_at->format('Y/m/d'));

        // 6-3. ログインしていないユーザーの申請が表示されていないことを確認
        $this->assertDatabaseHas(
            'attendance_change_requests',
            [
                'id' => $requestRecord[2]->id,
                'status' => ApplicationStatus::PENDING->value,
            ]
        );
        $response->assertDontSee($otherUserName);
        $response->assertDontSee($requestRecord[2]->work_date->format('Y/m/d'));
        $response->assertDontSee($requestRecord[2]->reason);
        $response->assertDontSee($requestRecord[2]->created_at->format('Y/m/d'));

        // 6-4. ログインユーザーの承認済申請が表示されていないことを確認
        $this->assertDatabaseHas(
            'attendance_change_requests',
            [
                'id' => $requestRecord[3]->id,
                'status' => ApplicationStatus::APPROVED->value,
            ]
        );
        $response->assertDontSee($requestRecord[3]->work_date->format('Y/m/d'));
        $response->assertDontSee($requestRecord[3]->reason);
        $response->assertDontSee($requestRecord[3]->created_at->format('Y/m/d'));
    }
    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されていることをテスト
     */
    public function test_it_displays_all_approved_requests_of_logged_in_user()
    {
        // 1-1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 1-2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 1-3. ログインユーザー名を取得
        $userName = $user->name;

        // 1-4. テスト日時を固定（ログインユーザーの1件目）
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 1-5. 出退勤時刻をDBに保存（ログインユーザーの1件目）
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance[] = Attendance::create($attendanceData);

        // 1-6. 休憩開始・終了時刻をDBに保存（ログインユーザーの1件目）
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
            $attendance[0]->breaks()->create($case);
        }
        $breaks = $attendance[0]->breaks()
            ->orderBy('break_start_at', 'asc')->get();

        // 1-7. 勤怠詳細ページを開く（ログインユーザーの1件目）
        $response = $this->actingAs($user)
            ->get('/attendance/' . $attendance[0]->id);
        $response->assertStatus(200);

        // 1-8. 修正情報を設定（ログインユーザーの1件目）
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '09:01',
            'clock_out_at' => '17:01',
            'breaks' => [
                [
                    'id' => $breaks[0]->id,
                    'start' => '10:00',
                    'end' => '10:16',
                ],
                [
                    'id' => $breaks[1]->id,
                    'start' => '12:00',
                    'end' => '13:01',
                ],
                [
                    'id' => $breaks[2]->id,
                    'start' => '15:01',
                    'end' => '15:15',
                ],
            ],
            'reason' => 'test1',
        ];

        // 1-9. 「修正」ボタンが表示されていることを確認（ログインユーザーの1件目）
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 1-10. 「修正」ボタンを押す（ログインユーザーの1件目）
        $response = $this->actingAs($user)
            ->post('/attendance/' . $attendance[0]->id, $formData);

        // 1-11. 作成した申請レコードを取得（ログインユーザーの1件目）
        $requestRecord[] = AttendanceChangeRequest::with('attendance', 'breaks')
            ->where('attendance_id', $attendance[0]->id)
            ->latest('updated_at')
            ->first();

        // 1-12. 承認処理（ログインユーザーの1件目）
        $adminUser = User::where('email', 'admin@example.com')
            ->first();
        $response = $this->actingAs($adminUser)
            ->post('/stamp_correction_request/approve/' . $requestRecord[0]->id);

        // 2-1. テスト日時を固定（ログインユーザーの2件目）
        $testTime = '2026-02-16 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 2-2. 出退勤時刻をDBに保存（ログインユーザーの2件目）
        $clockInTime = '2026-02-16 09:30:00';
        $clockOutTime = '2026-02-16 17:30:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance[] = Attendance::create($attendanceData);

        // 2-3. 休憩開始・終了時刻をDBに保存（ログインユーザーの2件目）
        $cases = [
            [
                'break_start_at' => '2026-02-16 10:30:00',
                'break_end_at' => '2026-02-16 10:45:00',
            ],
            [
                'break_start_at' => '2026-02-16 12:30:00',
                'break_end_at' => '2026-02-16 13:30:00',
            ],
            [
                'break_start_at' => '2026-02-16 15:30:00',
                'break_end_at' => '2026-02-16 15:45:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance[1]->breaks()->create($case);
        }
        $breaks = $attendance[1]->breaks()
            ->orderBy('break_start_at', 'asc')->get();

        // 2-4. 勤怠詳細ページを開く（ログインユーザーの2件目）
        $response = $this->actingAs($user)
            ->get('/attendance/' . $attendance[1]->id);
        $response->assertStatus(200);

        // 2-5. 修正情報を設定（ログインユーザーの2件目）
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '09:31',
            'clock_out_at' => '17:31',
            'breaks' => [
                [
                    'id' => $breaks[0]->id,
                    'start' => '10:30',
                    'end' => '10:47',
                ],
                [
                    'id' => $breaks[1]->id,
                    'start' => '12:30',
                    'end' => '13:47',
                ],
                [
                    'id' => $breaks[2]->id,
                    'start' => '15:30',
                    'end' => '15:47',
                ],
            ],
            'reason' => 'test2',
        ];

        // 2-6. 「修正」ボタンが表示されていることを確認（ログインユーザーの2件目）
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 2-7. 「修正」ボタンを押す（ログインユーザーの2件目）
        $response = $this->actingAs($user)
            ->post('/attendance/' . $attendance[1]->id, $formData);

        // 2-8. 作成した申請レコードを取得（ログインユーザーの2件目）
        $requestRecord[] = AttendanceChangeRequest::with('attendance', 'breaks')
            ->where('attendance_id', $attendance[1]->id)
            ->latest('updated_at')
            ->first();

        // 2-9. 承認処理（ログインユーザーの2件目）
        $response = $this->actingAs($adminUser)
            ->post('/stamp_correction_request/approve/' . $requestRecord[1]->id);

        // 3-1. 非ログインユーザーのインスタンスを生成（非ログインユーザー用）
        $otherUser = User::where('name', 'テストユーザー2')
            ->first() ?? User::inRandomOrder()->first();

        // 3-2. 生成したotherUserについて、メール認証済みにする（非ログインユーザー用）
        $otherUser->markEmailAsVerified();

        // 3-3. 非ログインユーザー名を取得（非ログインユーザー用）
        $otherUserName = $otherUser->name;

        // 3-4. テスト日時を固定（非ログインユーザー用）
        $testTime = '2026-02-17 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 3-5. 出退勤時刻をDBに保存（非ログインユーザー用）
        $clockInTime = '2026-02-17 10:00:00';
        $clockOutTime = '2026-02-17 18:00:00';
        $attendanceData = [
            'user_id' => $otherUser->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance[] = Attendance::create($attendanceData);

        // 3-6. 休憩開始・終了時刻をDBに保存（非ログインユーザー用）
        $cases = [
            [
                'break_start_at' => '2026-02-17 11:00:00',
                'break_end_at' => '2026-02-17 11:18:00',
            ],
            [
                'break_start_at' => '2026-02-17 13:00:00',
                'break_end_at' => '2026-02-17 14:03:00',
            ],
            [
                'break_start_at' => '2026-02-17 16:00:00',
                'break_end_at' => '2026-02-17 16:18:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance[2]->breaks()->create($case);
        }
        $breaks = $attendance[2]->breaks()
            ->orderBy('break_start_at', 'asc')->get();

        // 3-7. 勤怠詳細ページを開く（非ログインユーザー用）
        $response = $this->actingAs($otherUser)
            ->get('/attendance/' . $attendance[2]->id);
        $response->assertStatus(200);

        // 3-8. 修正情報を設定（非ログインユーザー用）
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '10:01',
            'clock_out_at' => '18:01',
            'breaks' => [
                [
                    'id' => $breaks[0]->id,
                    'start' => '11:00',
                    'end' => '11:19',
                ],
                [
                    'id' => $breaks[1]->id,
                    'start' => '13:00',
                    'end' => '14:04',
                ],
                [
                    'id' => $breaks[2]->id,
                    'start' => '16:00',
                    'end' => '16:19',
                ],
            ],
            'reason' => 'test3',
        ];

        // 3-9. 「修正」ボタンが表示されていることを確認（非ログインユーザー用）
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 3-10. 「修正」ボタンを押す（非ログインユーザー用）
        $response = $this->actingAs($otherUser)
            ->post('/attendance/' . $attendance[2]->id, $formData);

        // 3-11. 作成した申請レコードを取得（非ログインユーザー用）
        $requestRecord[] = AttendanceChangeRequest::with('attendance', 'breaks')
            ->where('attendance_id', $attendance[2]->id)
            ->latest('updated_at')
            ->first();

        // 3-12. 承認処理（非ログインユーザーの申請について）
        $response = $this->actingAs($adminUser)
            ->post('/stamp_correction_request/approve/' . $requestRecord[2]->id);

        // 4-1. テスト日時を固定（ログインユーザーの3件目/承認待ち）
        $testTime = '2026-02-18 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 4-2. 出退勤時刻をDBに保存（ログインユーザーの3件目/承認待ち）
        $clockInTime = '2026-02-18 10:30:00';
        $clockOutTime = '2026-02-18 18:30:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance[] = Attendance::create($attendanceData);

        // 4-3. 休憩開始・終了時刻をDBに保存（ログインユーザーの3件目/承認待ち）
        $cases = [
            [
                'break_start_at' => '2026-02-18 11:30:00',
                'break_end_at' => '2026-02-18 11:49:00',
            ],
            [
                'break_start_at' => '2026-02-18 13:30:00',
                'break_end_at' => '2026-02-18 14:34:00',
            ],
            [
                'break_start_at' => '2026-02-18 16:30:00',
                'break_end_at' => '2026-02-18 16:49:00',
            ],
        ];
        foreach ($cases as $case) {
            $attendance[3]->breaks()->create($case);
        }
        $breaks = $attendance[3]->breaks()
            ->orderBy('break_start_at', 'asc')->get();

        // 4-4. 勤怠詳細ページを開く（ログインユーザーの3件目/承認待ち）
        $response = $this->actingAs($user)
            ->get('/attendance/' . $attendance[3]->id);
        $response->assertStatus(200);

        // 4-5. 修正情報を設定（ログインユーザーの3件目/承認待ち）
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '10:31',
            'clock_out_at' => '18:31',
            'breaks' => [
                [
                    'id' => $breaks[0]->id,
                    'start' => '11:30',
                    'end' => '11:50',
                ],
                [
                    'id' => $breaks[1]->id,
                    'start' => '13:30',
                    'end' => '14:35',
                ],
                [
                    'id' => $breaks[2]->id,
                    'start' => '16:30',
                    'end' => '16:50',
                ],
            ],
            'reason' => 'test4',
        ];

        // 4-6. 「修正」ボタンが表示されていることを確認（ログインユーザーの3件目/承認待ち）
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 4-7. 「修正」ボタンを押す（ログインユーザーの3件目/承認待ち）
        $response = $this->actingAs($user)
            ->post('/attendance/' . $attendance[3]->id, $formData);

        // 4-8. 作成した申請レコードを取得（ログインユーザーの3件目/承認待ち）
        $requestRecord[] = AttendanceChangeRequest::with('attendance', 'breaks')
            ->where('attendance_id', $attendance[3]->id)
            ->latest('updated_at')->first();

        // 5. 申請一覧画面（一般ログインユーザー）の「承認済み」タブを開く
        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list/?tab=' . ApplicationStatus::APPROVED->name);

        // 6. ログインユーザーが行った申請が全て表示されていることを確認
        $response->assertSee('承認済み');
        $response->assertSee($userName);

        // 6-1. 1件目
        $this->assertDatabaseHas(
            'attendance_change_requests',
            [
                'id' => $requestRecord[0]->id,
                'status' => ApplicationStatus::APPROVED->value,
            ]
        );
        $response->assertSee($requestRecord[0]->work_date->format('Y/m/d'));
        $response->assertSee($requestRecord[0]->reason);
        $response->assertSee($requestRecord[0]->created_at->format('Y/m/d'));

        // 6-2. 2件目
        $this->assertDatabaseHas(
            'attendance_change_requests',
            [
                'id' => $requestRecord[1]->id,
                'status' => ApplicationStatus::APPROVED->value,
            ]
        );
        $response->assertSee($requestRecord[1]->work_date->format('Y/m/d'));
        $response->assertSee($requestRecord[1]->reason);
        $response->assertSee($requestRecord[1]->created_at->format('Y/m/d'));

        // 6-3. 非ログインユーザーの承認済み申請が表示されていないことを確認
        $this->assertDatabaseHas(
            'attendance_change_requests',
            [
                'id' => $requestRecord[2]->id,
                'status' => ApplicationStatus::APPROVED->value,
            ]
        );
        $response->assertDontSee($requestRecord[2]->work_date->format('Y/m/d'));
        $response->assertDontSee($requestRecord[2]->reason);
        $response->assertDontSee($requestRecord[2]->created_at->format('Y/m/d'));

        // 6-4. ログインユーザーの承認待ち申請が表示されていないことを確認
        $this->assertDatabaseHas(
            'attendance_change_requests',
            [
                'id' => $requestRecord[3]->id,
                'status' => ApplicationStatus::PENDING->value,
            ]
        );
        $response->assertDontSee($requestRecord[3]->work_date->format('Y/m/d'));
        $response->assertDontSee($requestRecord[3]->reason);
        $response->assertDontSee($requestRecord[3]->created_at->format('Y/m/d'));
    }
    /**
     * 各申請の「詳細」画面を押下すると勤怠詳細画面に遷移することをテスト
     */
    public function test_it_redirects_to_attendance_show_page_when_detail_button_is_clicked()
    {
        // 1-1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 1-2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 1-3. ログインユーザー名を取得
        $userName = $user->name;

        // 1-4. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 1-5. 出退勤時刻をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 1-6. 休憩開始・終了時刻をDBに保存
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
        $breaks = $attendance->breaks()
            ->orderBy('break_start_at', 'asc')->get();

        // 1-7. 勤怠詳細ページを開く
        $response = $this->actingAs($user)
            ->get('/attendance/' . $attendance->id);
        $response->assertStatus(200);

        // 1-8. 修正情報を設定
        $formData = [
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => '09:01',
            'clock_out_at' => '17:01',
            'breaks' => [
                [
                    'id' => $breaks[0]->id,
                    'start' => '10:00',
                    'end' => '10:16',
                ],
                [
                    'id' => $breaks[1]->id,
                    'start' => '12:00',
                    'end' => '13:01',
                ],
                [
                    'id' => $breaks[2]->id,
                    'start' => '15:01',
                    'end' => '15:15',
                ],
            ],
            'reason' => 'test1',
        ];

        // 1-9. 「修正」ボタンが表示されていることを確認（ログインユーザーの1件目）
        $response->assertSee('<button type="submit" name="submit" class="modify-button">修正</button>', false);

        // 1-10. 「修正」ボタンを押す（ログインユーザーの1件目）
        $response = $this->actingAs($user)
            ->post('/attendance/' . $attendance->id, $formData);

        // 1-11. 作成した申請レコードを取得（ログインユーザーの1件目）
        $requestRecord = AttendanceChangeRequest::with('attendance', 'breaks')
            ->where('attendance_id', $attendance->id)
            ->latest('updated_at')
            ->first();

        // 2. 申請一覧画面（一般ログインユーザー）を開く
        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        // 3. 「詳細」リンクが表示されていることを確認
        $response->assertSee(
            '<td><a href="/attendance/' . $requestRecord->attendance_id . '" class="detail-link">詳細</a></td>',
            false
        );

        // 4. 「詳細」リンクをクリック
        $response = $this->actingAs($user)->get('/attendance/' . $requestRecord->attendance_id);
        // 5. 勤怠詳細画面の表示内容を確認
        $response->assertSee($userName);
        $response->assertSee($requestRecord->work_date->format('Y年'));
        $response->assertSee($requestRecord->work_date->format('n月j日'));
        $response->assertSee($requestRecord->attendance->new_clock_in_at->format('H:i'));
        $response->assertSee($requestRecord->attendance->new_clock_out_at->format('H:i'));
        $breaks = $requestRecord->breaks()->get();
        foreach ($breaks as $break) {
            $response->assertSee($break->new_break_start_at->format('H:i'));
            $response->assertSee($break->new_break_end_at->format('H:i'));
        }
        $response->assertSee($requestRecord->reason);
        $response->assertSee('*承認待ちのため修正できません');
    }
}
