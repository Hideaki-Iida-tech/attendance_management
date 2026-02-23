<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserIndexAndShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setup();
        $this->seed(\Database\Seeders\AdminUserSeeder::class);
        $this->seed(\Database\Seeders\GeneralUserSeeder::class);
    }
    /**
     * 管理者が全一般ユーザーの「氏名」「メールアドレス」を確認できることをテスト
     */
    public function test_it_displays_name_and_email_for_all_users_to_admin()
    {
        // 1. すべての一般ユーザーのインスタンスを取得
        $users = User::where('role', UserRole::USER)->get();

        // 2. 管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 3. スタッフ一覧ページを開く
        $response = $this->actingAs($adminUser)->get('/admin/staff/list');

        // 4. 全一般ユーザーの「氏名」「メールアドレス」が表示されていることを確認
        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    /** 
     * ユーザーの勤怠情報が正しく表示されることをテスト
     */
    public function test_admin_can_view_correct_attendance_information_of_user()
    {
        // 1. 一般ユーザーのインスタンスを取得
        $user = User::where('name', 'テストユーザー1')->first()
            ?? User::inRandomOrder()->first();

        // 2-1. 出退勤時刻（1日目分）をDBに保存
        $clockInTime = '2026-02-14 09:00:00';
        $clockOutTime = '2026-02-14 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($clockInTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 2-2. 休憩時刻（1日目分）をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-14 10:00:00',
            'break_end_at' => '2026-02-14 10:15:00',
        ];
        $attendances[0]->breaks()->create($breaksData);

        // 3-1. 出退勤時刻（2日目分）をDBに保存
        $clockInTime = '2026-02-15 09:01:00';
        $clockOutTime = '2026-02-15 17:01:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($clockInTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 3-2. 休憩時刻（2日目）分をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:16:00',
        ];
        $attendances[1]->breaks()->create($breaksData);

        // 4-1. 出退勤時刻（3日目分）をDBに保存
        $clockInTime = '2026-02-16 09:02:00';
        $clockOutTime = '2026-02-16 17:02:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($clockInTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 4-2. 休憩時刻（3日目分）をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-16 10:00:00',
            'break_end_at' => '2026-02-16 10:17:00',
        ];
        $attendances[2]->breaks()->create($breaksData);

        // 5. 一般ユーザー（選択されていないユーザー）のインスタンスを取得
        $otherUser = User::where('name', 'テストユーザー2')->first()
            ?? User::inRandomOrder()->first();

        // 6-1. 出退勤時刻（選択されていないユーザー分）をDBに保存
        $clockInTime = '2026-02-17 09:03:00';
        $clockOutTime = '2026-02-17 17:03:00';
        $attendanceData = [
            'user_id' => $otherUser->id,
            'work_date' => Carbon::parse($clockInTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $otherUserAttendance = Attendance::create($attendanceData);

        // 6-2. 休憩時刻（選択されていないユーザー分）をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-17 10:00:00',
            'break_end_at' => '2026-02-17 10:18:00',
        ];
        $otherUserAttendance->breaks()->create($breaksData);

        // 7. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 8. 管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 9. スタッフ一覧ページを開く
        $response = $this->actingAs($adminUser)->get('/admin/staff/list');

        // 10. 選択すべき一般ユーザーの「氏名」「メールアドレス」「詳細」リンクが表示されていることを確認
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee('<a href="/admin/attendance/staff/'
            . $user->id . '" class="detail-link">詳細</a>', false);

        // 11. 「詳細」リンクをクリック
        $response = $this->actingAs($adminUser)->get('/admin/attendance/staff/' . $user->id);

        // 12. 選択したユーザー（テストユーザー1）の名前を含むタイトルが表示されていることを確認
        $response->assertSee('<h1 class="attendance-list-title-inner">' . $user->name . 'さんの勤怠</h1>', false);

        // 13. 選択したユーザー（テストユーザー1）の勤怠データが表示されていることを確認
        foreach ($attendances as $attendance) {
            $response->assertSee($attendance->work_date->translatedFormat('m/d(D)'));
            $response->assertSee($attendance->clockInTime);
            $response->assertSee($attendance->clockOutTime);
            $response->assertSee($attendance->formatedBreakTime);
            $response->assertSee($attendance->formatedWoringTime);
        }

        // 14. 選択していないユーザー（テストユーザー2）の勤怠データが表示されていないことを確認
        $response->assertDontSee($otherUserAttendance->clockInTime);
        $response->assertDontSee($otherUserAttendance->clockOutTime);
        $response->assertDontSee($otherUserAttendance->formatedBreakTime);
        $response->assertDontSee($otherUserAttendance->formatedWorkingTime);
    }
    /**
     * 「前月」を押下した時に表示月の前月の情報が表示されることをテスト
     */
    public function test_attendance_index_displays_previous_month_when_previous_button_clicked()
    {
        // 1. 一般ユーザーのインスタンスを取得
        $user = User::where('name', 'テストユーザー1')->first()
            ?? User::inRandomOrder()->first();

        // 2. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 3-1. 出退勤時刻（当月分）をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $currentMonthAttendance = Attendance::create($attendanceData);

        // 3-2. 休憩時刻（当月分）をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:15:00',
        ];
        $currentMonthAttendance->breaks()->create($breaksData);

        // 4-1. 出退勤時刻（前月分その1）をDBに保存
        $clockInTime = '2026-01-15 09:01:00';
        $clockOutTime = '2026-01-15 17:01:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($clockInTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 4-2. 休憩時刻（前月分その1）分をDBに保存
        $breaksData = [
            'break_start_at' => '2026-01-15 10:00:00',
            'break_end_at' => '2026-01-15 10:16:00',
        ];
        $attendances[0]->breaks()->create($breaksData);

        // 5-1. 出退勤時刻（前月分その2）をDBに保存
        $clockInTime = '2026-01-16 09:02:00';
        $clockOutTime = '2026-01-16 17:02:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($clockInTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 5-2. 休憩時刻（前月分その2）をDBに保存
        $breaksData = [
            'break_start_at' => '2026-01-16 10:00:00',
            'break_end_at' => '2026-01-16 10:17:00',
        ];
        $attendances[1]->breaks()->create($breaksData);

        // 6-1. 出退勤時刻（前月分その3）をDBに保存
        $clockInTime = '2026-01-17 09:03:00';
        $clockOutTime = '2026-01-17 17:03:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($clockInTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 6-2. 休憩時刻（前月分その3）をDBに保存
        $breaksData = [
            'break_start_at' => '2026-01-17 10:00:00',
            'break_end_at' => '2026-01-17 10:18:00',
        ];
        $attendances[2]->breaks()->create($breaksData);

        // 7. 管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 8. スタッフ一覧ページを開く
        $response = $this->actingAs($adminUser)->get('/admin/staff/list');

        // 9. 選択すべき一般ユーザーの「氏名」「メールアドレス」「詳細」リンクが表示されていることを確認
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee('<a href="/admin/attendance/staff/'
            . $user->id . '" class="detail-link">詳細</a>', false);

        // 10. 「詳細」リンクをクリック
        $response = $this->actingAs($adminUser)->get('/admin/attendance/staff/' . $user->id);

        // 11. 選択したユーザー（テストユーザー1）の名前を含むタイトルが表示されていることを確認
        $response->assertSee('<h1 class="attendance-list-title-inner">'
            . $user->name . 'さんの勤怠</h1>', false);

        // 12. 選択したユーザー（テストユーザー1）の当月の勤怠データが表示されていることを確認
        $response->assertSee($currentMonthAttendance->work_date->translatedFormat('m/d(D)'));
        $response->assertSee($currentMonthAttendance->clockInTime);
        $response->assertSee($currentMonthAttendance->clockOutTime);
        $response->assertSee($currentMonthAttendance->formatedBreakTime);
        $response->assertSee($currentMonthAttendance->formatedWoringTime);

        // 13. 「←前月」のリンクが表示されていることを確認
        $response->assertSee('<a href="/admin/attendance/staff/'
            . $user->id . '/?month=2026-01" class="month-pre">←前月</a>', false);

        // 14. 「←前月」のリンクをクリック
        $response = $this->actingAs($adminUser)->get('/admin/attendance/staff/'
            . $user->id . '/?month=2026-01');

        // 15. 選択したユーザー（テストユーザー1）の名前を含むタイトルが表示されていることを確認
        $response->assertSee('<h1 class="attendance-list-title-inner">'
            . $user->name . 'さんの勤怠</h1>', false);

        // 16. 選択したユーザー（テストユーザー1）の表示月の前月のデータが表示されていることを確認
        foreach ($attendances as $attendance) {
            $response->assertSee($attendance->work_date->translatedFormat('m/d(D)'));
            $response->assertSee($attendance->clockInTime);
            $response->assertSee($attendance->clockOutTime);
            $response->assertSee($attendance->formatedBreakTime);
            $response->assertSee($attendance->formatedWorkingTime);
        }

        // 17. 選択したユーザー（テストユーザー1）の当月の勤怠データが表示されていないことを確認
        $response->assertDontSee($currentMonthAttendance->work_date->translatedFormat('m/d(D)'));
        $response->assertDontSee($currentMonthAttendance->clockInTime);
        $response->assertDontSee($currentMonthAttendance->clockOutTime);
        $response->assertDontSee($currentMonthAttendance->formatedBreakTime);
        $response->assertDontSee($currentMonthAttendance->formatedWoringTime);
    }
    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示されることをテスト
     */
    public function test_attendance_index_displays_next_month_when_next_button_clicked()
    {
        // 1. 一般ユーザーのインスタンスを取得
        $user = User::where('name', 'テストユーザー1')->first()
            ?? User::inRandomOrder()->first();

        // 2. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 3-1. 出退勤時刻（当月分）をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $currentMonthAttendance = Attendance::create($attendanceData);

        // 3-2. 休憩時刻（当月分）をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:15:00',
        ];
        $currentMonthAttendance->breaks()->create($breaksData);

        // 4-1. 出退勤時刻（翌月分その1）をDBに保存
        $clockInTime = '2026-03-15 09:01:00';
        $clockOutTime = '2026-03-15 17:01:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($clockInTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 4-2. 休憩時刻（翌月分その1）分をDBに保存
        $breaksData = [
            'break_start_at' => '2026-03-15 10:00:00',
            'break_end_at' => '2026-03-15 10:16:00',
        ];
        $attendances[0]->breaks()->create($breaksData);

        // 5-1. 出退勤時刻（翌月分その2）をDBに保存
        $clockInTime = '2026-03-16 09:02:00';
        $clockOutTime = '2026-03-16 17:02:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($clockInTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 5-2. 休憩時刻（翌月分その2）をDBに保存
        $breaksData = [
            'break_start_at' => '2026-03-16 10:00:00',
            'break_end_at' => '2026-03-16 10:17:00',
        ];
        $attendances[1]->breaks()->create($breaksData);

        // 6-1. 出退勤時刻（翌月分その3）をDBに保存
        $clockInTime = '2026-03-17 09:03:00';
        $clockOutTime = '2026-03-17 17:03:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($clockInTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendances[] = Attendance::create($attendanceData);

        // 6-2. 休憩時刻（翌月分その3）をDBに保存
        $breaksData = [
            'break_start_at' => '2026-03-17 10:00:00',
            'break_end_at' => '2026-03-17 10:18:00',
        ];
        $attendances[2]->breaks()->create($breaksData);

        // 7. 管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 8. スタッフ一覧ページを開く
        $response = $this->actingAs($adminUser)->get('/admin/staff/list');

        // 9. 選択すべき一般ユーザーの「氏名」「メールアドレス」「詳細」リンクが表示されていることを確認
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee('<a href="/admin/attendance/staff/'
            . $user->id . '" class="detail-link">詳細</a>', false);

        // 10. 「詳細」リンクをクリック
        $response = $this->actingAs($adminUser)->get('/admin/attendance/staff/' . $user->id);

        // 11. 選択したユーザー（テストユーザー1）の名前を含むタイトルが表示されていることを確認
        $response->assertSee('<h1 class="attendance-list-title-inner">'
            . $user->name . 'さんの勤怠</h1>', false);

        // 12. 選択したユーザー（テストユーザー1）の当月の勤怠データが表示されていることを確認
        $response->assertSee($currentMonthAttendance->work_date->translatedFormat('m/d(D)'));
        $response->assertSee($currentMonthAttendance->clockInTime);
        $response->assertSee($currentMonthAttendance->clockOutTime);
        $response->assertSee($currentMonthAttendance->formatedBreakTime);
        $response->assertSee($currentMonthAttendance->formatedWoringTime);

        // 13. 「翌月→」のリンクが表示されていることを確認
        $response->assertSee('<a href="/admin/attendance/staff/'
            . $user->id . '/?month=2026-03" class="month-next">翌月→</a>', false);

        // 14. 「翌月→」のリンクをクリック
        $response = $this->actingAs($adminUser)->get('/admin/attendance/staff/'
            . $user->id . '/?month=2026-03');

        // 15. 選択したユーザー（テストユーザー1）の名前を含むタイトルが表示されていることを確認
        $response->assertSee('<h1 class="attendance-list-title-inner">'
            . $user->name . 'さんの勤怠</h1>', false);

        // 16. 選択したユーザー（テストユーザー1）の表示月の翌月のデータが表示されていることを確認
        foreach ($attendances as $attendance) {
            $response->assertSee($attendance->work_date->translatedFormat('m/d(D)'));
            $response->assertSee($attendance->clockInTime);
            $response->assertSee($attendance->clockOutTime);
            $response->assertSee($attendance->formatedBreakTime);
            $response->assertSee($attendance->formatedWorkingTime);
        }

        // 17. 選択したユーザー（テストユーザー1）の当月の勤怠データが表示されていないことを確認
        $response->assertDontSee($currentMonthAttendance->work_date->translatedFormat('m/d(D)'));
        $response->assertDontSee($currentMonthAttendance->clockInTime);
        $response->assertDontSee($currentMonthAttendance->clockOutTime);
        $response->assertDontSee($currentMonthAttendance->formatedBreakTime);
        $response->assertDontSee($currentMonthAttendance->formatedWoringTime);
    }
    /**
     * 「詳細」ボタンを押下すると、その日の勤怠詳細画面に遷移することをテスト
     */
    public function test_attendance_index_redirects_to_attendance_show_when_detail_button_clicked()
    {
        // 1. 一般ユーザーのインスタンスを取得
        $user = User::where('name', 'テストユーザー1')->first()
            ?? User::inRandomOrder()->first();

        // 2. テスト日時を固定
        $testTime = '2026-02-15 08:00:00';
        Carbon::setLocale('ja');
        Carbon::setTestNow($testTime);

        // 3-1. 出退勤時刻（当月分）をDBに保存
        $clockInTime = '2026-02-15 09:00:00';
        $clockOutTime = '2026-02-15 17:00:00';
        $attendanceData = [
            'user_id' => $user->id,
            'work_date' => Carbon::parse($testTime)->toDateString(),
            'clock_in_at' => Carbon::parse($clockInTime),
            'clock_out_at' => Carbon::parse($clockOutTime),
        ];
        $attendance = Attendance::create($attendanceData);

        // 3-2. 休憩時刻（当月分）をDBに保存
        $breaksData = [
            'break_start_at' => '2026-02-15 10:00:00',
            'break_end_at' => '2026-02-15 10:15:00',
        ];
        $attendance->breaks()->create($breaksData);
        $attendance->load('breaks');
        $breaks = $attendance->breaks()->get();

        // 3. 管理者ユーザーのインスタンスを取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 4. スタッフ一覧ページを開く
        $response = $this->actingAs($adminUser)->get('/admin/staff/list');

        // 5. 選択すべき一般ユーザーの「氏名」「メールアドレス」「詳細」リンクが表示されていることを確認
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee('<a href="/admin/attendance/staff/'
            . $user->id . '" class="detail-link">詳細</a>', false);

        // 6. 「詳細」リンクをクリック
        $response = $this->actingAs($adminUser)->get('/admin/attendance/staff/' . $user->id);

        // 7. 選択したユーザー（テストユーザー1）の名前を含むタイトルが表示されていることを確認
        $response->assertSee('<h1 class="attendance-list-title-inner">'
            . $user->name . 'さんの勤怠</h1>', false);

        // 8. 選択したユーザー（テストユーザー1）の当月の勤怠データが表示されていることを確認
        $response->assertSee($attendance->work_date->translatedFormat('m/d(D)'));
        $response->assertSee($attendance->clockInTime);
        $response->assertSee($attendance->clockOutTime);
        $response->assertSee($attendance->formatedBreakTime);
        $response->assertSee($attendance->formatedWoringTime);

        // 9. 選択したユーザー（テストユーザー1）について登録されている勤怠情報の「詳細」リンクが表示されていることを確認
        $response->assertSee('<a href="/attendance/' . $attendance->id
            . '" class="detail-link">詳細</a>', false);

        // 10. 「詳細」リンクをクリック
        $response = $this->actingAs($adminUser)->get('/attendance/' . $attendance->id);

        // 11. 登録した勤怠データ詳細が表示されていることを確認
        $response->assertSee('勤怠詳細');
        $response->assertSee($user->name); // 一般ユーザー名
        $response->assertSee($attendance->work_date->format('Y年')); // 年
        $response->assertSee($attendance->work_date->format('n月j日')); // 月日
        $response->assertSee($attendance->clockInTime); // 勤務開始時刻
        $response->assertSee($attendance->clockOutTime); // 勤務終了時刻
        foreach ($breaks as $break) {
            $response->assertSee($break->breakStartTime); // 休憩開始時刻
            $response->assertSee($break->breakEndTime); // 休憩終了時刻
        }
    }
}
