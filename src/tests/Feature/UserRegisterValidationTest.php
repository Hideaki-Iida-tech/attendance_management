<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class UserRegisterValidationTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * 名前未入力で登録した場合にエラーになることをテスト
     */
    public function test_register_fails_when_name_is_missing()
    {
        // １．ユーザー情報登録画面を開く
        $response = $this->get('/register');
        $response->assertStatus(200);

        // 2. 名前を入力せずに、ほかの項目を入力
        $formData = [
            'name' => '',
            'email' => 'test11@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password1230,'
        ];

        // 3. 登録ボタンを押す （=POST /register）
        $response = $this->post('/register', $formData);

        // 4. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['name']);

        // 5. リダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/register');

        // 6. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('name'),
            'お名前を入力してください'
        );
        $response->assertSessionHas(
            '_old_input.email',
            'test11@example.com'
        );
    }

    /**
     *  メールアドレス未入力で登録した場合にエラーになることをテスト
     */
    public function test_register_fails_when_email_is_missing()
    {
        // 1. ユーザー情報登録画面を開く。
        $response = $this->get('/register');
        $response->assertStatus(200);

        // 2. メールアドレスを入力せずに、ほかの項目を入力
        $formData = [
            'name' => 'test',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // 3. 登録ボタンを押す （=POST /register）
        $response = $this->post('/register', $formData);

        // 4. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['email']);

        // 5 リダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/register');

        // 6. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('email'),
            'メールアドレスを入力してください'
        );
        $response->assertSessionHas('_old_input.name', 'test');
        $response->assertSessionHas('_old_input.email', '');
    }

    /**
     *  パスワードを7文字以下で登録した場合にエラーになることをテスト
     */
    public function test_register_fails_when_password_is_too_short()
    {
        // 1. ユーザー情報登録画面を開く
        $response = $this->get('/register');
        $response->assertStatus(200);

        // 2. パスワード以外の必要項目を入力し、7文字以下のパスワードを入力する
        $formData = [
            'name' => 'test',
            'email' => 'test11@example.com',
            'password' => 'passwor',
            'password_confirmation' => 'passwor',
        ];

        // 3. 登録ボタンを押す
        $response = $this->post('/register', $formData);

        // 4. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['password']);

        // 5. リダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/register');

        // 6. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('password'),
            'パスワードは8文字以上で入力してください'
        );
        $response->assertSessionHas('_old_input.name', 'test');
        $response->assertSessionHas(
            '_old_input.email',
            'test11@example.com'
        );
    }

    /**
     * パスワードと確認用パスワードが一致しない状態で登録した場合にエラーになることをテスト
     */
    public function test_register_fails_when_password_confirmation_does_not_match()
    {
        // 1. ユーザー情報登録画面を開く
        $response = $this->get('/register');
        $response->assertStatus(200);

        // 2. パスワードと確認用パスワードに異なった文字列を入力、その他の項目について入力
        $formData = [
            'name' => 'test',
            'email' => 'test11@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ];

        // 3. 登録ボタンを押す
        $response = $this->post('/register', $formData);

        // 4. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['password']);

        // 5. リダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/register');

        // 6. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('password'),
            'パスワードと一致しません'
        );
        $response->assertSessionHas('_old_input.name', 'test');
        $response->assertSessionHas(
            '_old_input.email',
            'test11@example.com'
        );
    }

    /**
     * パスワード未入力で登録した場合にエラーになることをテスト
     */
    public function test_register_fails_when_password_is_missing()
    {
        // 1. ユーザー情報登録画面を開く。
        $response = $this->get('/register');
        $response->assertStatus(200);

        // 2. パスワードを入力せずに、ほかの項目を入力
        $formData = [
            'name' => 'test',
            'email' => 'test11@example.com',
            'password' => '',
            'password_confirmation' => 'password123',
        ];

        // 3. 登録ボタンを押す
        $response = $this->post('/register', $formData);

        // 4. バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors(['password']);

        // 5. リダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/register');

        // 6. エラーメッセージがセッションに入っているか確認
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals(
            session('errors')->first('password'),
            'パスワードを入力してください'
        );
        $response->assertSessionHas('_old_input.name', 'test');
        $response->assertSessionHas('_old_input.email', 'test11@example.com');
    }

    /**
     * すべてをバリデーションルールの通りに登録した場合をテスト
     */
    public function test_register_succeeds_with_valid_data()
    {
        // 1. ユーザー情報登録画面を開く
        $response = $this->get('/register');
        $response->assertStatus(200);

        // 2. すべての項目を入力する
        $formData = [
            'name' => 'test',
            'email' => 'test11@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 3. 登録ボタンを押す
        $response = $this->post('/register', $formData);

        // 4. リダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        // 5. バリデーションエラーがないことを確認
        $response->assertSessionHasNoErrors();

        // 6. DBにユーザーが作成されていること
        $this->assertDatabaseHas(
            'users',
            [
                'name' => 'test',
                'email' => 'test11@example.com'
            ]
        );

        // 7. パスワードはハッシュ化されていること
        $user = User::where('email', 'test11@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check(
            'password123',
            $user->password
        ));
    }
}
