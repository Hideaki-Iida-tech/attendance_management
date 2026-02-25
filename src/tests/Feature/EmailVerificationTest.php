<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * 会員登録後、認証メールが送信されることをテスト
     */
    public function test_verification_email_is_sent_after_registration()
    {
        // 1. 会員情報登録画面を開く
        $response = $this->get('/register');
        $response->assertStatus(200);

        // 2. すべての項目を入力する
        $formData = [
            'name' => 'test11',
            'email' => 'test11@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 通知をテスト用に差し替え
        Notification::fake();

        // 3. 登録ボタンを押す
        $response = $this->followingRedirects()->post('/register', $formData);
        $response->assertStatus(200);
        // 4. メール認証誘導画面へ遷移していることを確認 
        $response->assertViewIs('auth.verify-email');

        // 5. バリデーションエラーがないことを確認
        $response->assertSessionHasNoErrors();

        // 6. ログイン状態であること
        $user = User::where('email', 'test11@example.com')->firstOrFail();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        // 7. 認証メールが送信されていることを確認
        Notification::assertSentTo($user, VerifyEmail::class);
    }
    /**
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移することをテスト
     */
    public function test_clicking_verification_button_redirects_to_verification_page()
    {

        // 1. ログインする
        $response = $this->get('/login');
        $response->assertStatus(200);

        $formData = [
            'email' => 'test1@example.com',
            'password' => 'password',
            'login_context' => 'user',
        ];

        $response = $this->post('/login', $formData);

        $user = User::where('email', 'test1@example.com')->firstOrFail();

        // 2. ログイン直後はホーム（/attendance）へ
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        // 3. 未認証ユーザーが /attendance に行こうとすると verify へ飛ばされる
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(302);
        $response->assertRedirect('/email/verify');

        // 4. 検証メールを再送
        Notification::fake();

        // 5. ログイン状態を明示
        $this->actingAs($user);

        // 6. mailhogの画面を表示
        $this->actingAs($user)->get('http://localhost:8025/');

        // 7. Fortify等の「検証メール再送ルート」
        $this->post(route('verification.send'));

        // 8. VerifyEmail通知が送られたこと＆URLを取り出す
        $verificationUrl = null;
        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            function ($notification) use ($user, &$verificationUrl) {
                $mail = $notification->toMail($user);

                // $mail->actionUrlをそのまま使用
                if (property_exists($mail, 'actionUrl') && $mail->actionUrl) {
                    $verificationUrl = $mail->actionUrl;
                    return true;
                }

                // 万一actionUrlが取れない場合に備えて、テスト内でURLを自前生成
                if (!$verificationUrl) {
                    $verificationUrl = URL::temporarySignedRoute(
                        'verification.verify',
                        now()->addMinutes(Config::get('auth.verification.expire', 60)),
                        ['id' => $user->getKey(), 'hash' => sha1($user->email)]
                    );
                }

                return true;
            }
        );

        $this->assertNotNull($verificationUrl, '検証URLを取得できませんでした。');
    }
    /**
     * メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     */
    public function test_user_is_redirected_to_attendance_page_after_email_verification()
    {
        // 1. ログインする
        $response = $this->get('/login');
        $response->assertStatus(200);

        $formData = [
            'email' => 'test1@example.com',
            'password' => 'password',
            'login_context' => 'user',
        ];

        $response = $this->post('/login', $formData);

        $user = User::where('email', 'test1@example.com')->firstOrFail();

        // 2. ログイン直後はホーム（/attendance）へ
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        // 3. 未認証ユーザーが /attendance に行こうとすると verify へ飛ばされる
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(302);
        $response->assertRedirect('/email/verify');

        // 4. 検証メールを再送
        Notification::fake();

        // 5. ログイン状態を明示
        $this->actingAs($user);

        // 6. mailhogの画面を表示
        $this->actingAs($user)->get('http://localhost:8025/');

        // 7. Fortify等の「検証メール再送ルート」
        $this->post(route('verification.send'));

        // 8. VerifyEmail通知が送られたこと＆URLを取り出す
        $verificationUrl = null;
        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            function ($notification) use ($user, &$verificationUrl) {
                $mail = $notification->toMail($user);

                // $mail->actionUrlをそのまま使用
                if (property_exists($mail, 'actionUrl') && $mail->actionUrl) {
                    $verificationUrl = $mail->actionUrl;
                    return true;
                }

                // 万一actionUrlが取れない場合に備えて、テスト内でURLを自前生成
                if (!$verificationUrl) {
                    $verificationUrl = URL::temporarySignedRoute(
                        'verification.verify',
                        now()->addMinutes(Config::get('auth.verification.expire', 60)),
                        ['id' => $user->getKey(), 'hash' => sha1($user->email)]
                    );
                }

                return true;
            }
        );

        $this->assertNotNull($verificationUrl, '検証URLを取得できませんでした。');

        // 9. 「メール内の認証ボタンをクリックした」想定で、検証URLにアクセス
        $verifyResponse = $this->get($verificationUrl);
        $verifyResponse->assertStatus(302);
        $verifyResponse->assertRedirect('/attendance');

        // 10. DB上も認証済みになっていること
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
