<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use App\Http\Requests\RegisterRequest;
use App\Enums\UserRole;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    // registerページのバリデーションを自作のフォームリクエストに差し替えるために、メソッドを書き換え
    public function create(array $input): User
    {
        // 自作のフォームリクエストのインスタンスを取得
        $req = app(RegisterRequest::class);
        // 入力されたメールアドレスを小文字に変換
        $input['email'] = strtolower(trim((string)($input['email'] ?? '')));
        // 自作のフォームリクエストのルールとメッセージを適用
        Validator::make(
            $input,
            $req->rules(),
            $req->messages(),
        )->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            // 一般ユーザーのフラグ値を設定
            'role' => UserRole::USER,
        ]);
    }
}
