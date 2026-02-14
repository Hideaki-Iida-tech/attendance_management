<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStampDateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * 現在の日付情報が挿入されるべきタグが表示されることをテスト（現在の時刻はJSで更新・表示される仕様のため、PHPUnitではテストできない。JSでの更新対象のタグが表示されることをテストするものとする）
     */

    public function test_current_date_is_displayed_on_attendance_stamp_page()
    {
        // 1. ログインするユーザーのインスタンスを生成
        $user = User::where('name', 'テストユーザー1')
            ->first() ?? User::inRandomOrder()->first();

        // 2. 生成したuserについて、メール認証済みにする
        $user->markEmailAsVerified();

        // 3. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        // 4. JSで更新されるタグが表示されていること及び、更新用のJSのコードが出力されていること
        $response->assertSee('class="date-value"', false);

        $response->assertSee('function updateTime()', false); // scriptが含まれること。
    }
}
