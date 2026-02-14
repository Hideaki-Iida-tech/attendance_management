<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 1. シーダーで管理者を登録
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * メールアドレスが未入力の場合にエラーになることをテスト（管理者）
     */
    public function test_login_fails_when_email_is_missing()
    {
        // 2. ログイン画面を表示
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        // 3. メールアドレスを入力せずパスワードのみ入力
        $formData = [
            'login_context' => 'admin',
            'email' => '',
            'password' => 'password',
        ];

        // 4. ログインボタンを押す
        $response = $this->post('/login', $formData);

        // 5. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionDoesntHaveErrors(['password']);
        $response->assertSessionDoesntHaveErrors(['login_context']);

        // 6. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');

        // 7. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('email'),
            'メールアドレスを入力してください'
        );
    }

    /**
     * パスワードが未入力の場合にエラーになることをテスト（管理者）
     */
    public function test_login_fails_when_password_is_missing()
    {
        // 2. ログイン画面を表示
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        // 3. メールアドレスのみ入力し、パスワードは未入力
        $formData = [
            'login_context' => 'admin',
            'email' => 'admin@example.com',
            'password' => '',
        ];

        // 4. 登録ボタンを押す
        $response = $this->post('/login', $formData);

        // 5. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['password']);
        $response->assertSessionDoesntHaveErrors(['email']);
        $response->assertSessionDoesntHaveErrors(['login_context']);

        // 6. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');

        // 7. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('password'),
            'パスワードを入力してください'
        );
        $response->assertSessionHas('_old_input.email', 'admin@example.com');
    }

    /**
     * 間違ったメールアドレス、パスワードを入力した場合にエラーになることをテスト（管理者）
     */
    public function test_login_fails_with_invalid_credentials()
    {
        // 2. ログイン画面を表示
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        // 3.間違った入力情報を入力
        $formData = [
            'login_context' => 'admin',
            'email' => 'admin111@example.com',
            'password' => 'password1234',
        ];

        // 4. ログインボタンを押す
        $response = $this->post('/login', $formData);

        // 5. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionDoesntHaveErrors(['password']);
        $response->assertSessionDoesntHaveErrors(['login_context']);

        // 6. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');

        // 7. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('email'),
            'ログイン情報が登録されていません'
        );
        $response->assertSessionHas('_old_input.email', 'admin111@example.com');
    }
}
