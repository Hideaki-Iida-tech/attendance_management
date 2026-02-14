<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginValidationTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * メールアドレスが未入力の場合にエラーになることをテスト（一般ユーザー）
     */
    public function test_login_fails_when_email_is_missing()
    {

        // 1. ユーザー情報の登録
        $response = $this->get('/register');
        $response->assertStatus(200);
        $formData = [
            'name' => 'test',
            'email' => 'test11@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->post('/register', $formData);

        $logoutResponse = $this->post('/logout');

        // 2. リダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        // 3. ログイン画面を表示
        $response = $this->get('/login');
        $response->assertStatus(200);

        // 4. メールアドレスを入力せずパスワードのみ入力
        $formData = [
            'login_context' => 'user',
            'email' => '',
            'password' => 'password123',
        ];

        // 5. ログインボタンを押す
        $response = $this->post('/login', $formData);

        // 6. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionDoesntHaveErrors(['password']);
        $response->assertSessionDoesntHaveErrors(['login_context']);

        // 7. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // 8. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('email'),
            'メールアドレスを入力してください'
        );
    }

    /**
     * パスワードが未入力の場合にエラーになることをテスト（一般ユーザー）
     */
    public function test_login_fails_when_password_is_missing()
    {
        // 1. ユーザー情報の登録
        $response = $this->get('/register');
        $response->assertStatus(200);
        $formData = [
            'name' => 'test',
            'email' => 'test11@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->post('/register', $formData);

        $logoutResponse = $this->post('/logout');

        // 2. リダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        // 3. ログイン画面を表示
        $response = $this->get('/login');
        $response->assertStatus(200);

        // 4. メールアドレスのみ入力し、パスワードは未入力
        $formData = [
            'login_context' => 'user',
            'email' => 'test11@example.com',
            'password' => '',
        ];

        // 5. ログインボタンを押す
        $response = $this->post('/login', $formData);

        // 6. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['password']);
        $response->assertSessionDoesntHaveErrors(['email']);
        $response->assertSessionDoesntHaveErrors(['login_context']);

        // 7. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // 8. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('password'),
            'パスワードを入力してください'
        );
        $response->assertSessionHas('_old_input.email', 'test11@example.com');
    }

    /**
     * 間違ったメールアドレス、パスワードを入力した場合にエラーになることをテスト（一般ユーザー）
     */
    public function test_login_fails_with_invalid_credentials()
    {
        // 1. ユーザー情報の登録
        $response = $this->get('/register');
        $response->assertStatus(200);
        $formData = [
            'name' => 'test',
            'email' => 'test11@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->post('/register', $formData);

        $logoutResponse = $this->post('/logout');

        // 2. リダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        // 3. ログイン画面を表示
        $response = $this->get('/login');
        $response->assertStatus(200);

        // 4. 間違った入力情報を入力
        $formData = [
            'login_context' => 'user',
            'email' => 'test111@example.com',
            'password' => 'password1234',
        ];

        // 5. ログインボタンを押す
        $response = $this->post('/login', $formData);

        // 6. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionDoesntHaveErrors(['password']);
        $response->assertSessionDoesntHaveErrors(['login_context']);

        // 7. リダイレクトすることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // 8. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('email'),
            'ログイン情報が登録されていません'
        );
        $response->assertSessionHas('_old_input.email', 'test111@example.com');
    }
}
