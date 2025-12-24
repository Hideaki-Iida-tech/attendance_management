<?php

namespace App\Http\Requests;

use Laravel\Fortify\Http\Requests\LoginRequest as BaseLoginRequest;

/**
 * 一般ユーザーログイン画面/管理者ログイン画面共通
 * のバリデーションを設定するフォームリクエスト
 * Fortify既定のLoginRequestを継承して作成
 * FortifyServiceProviderのregisterメソッド内で
 * Fotify既定のLoginRequestを差し替えている
 */
class LoginRequest extends BaseLoginRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // emailがusersテーブルのemailに存在すること、login_contextにuserかadminが指定されていること等
            'email' => ['required', 'string', 'email', 'exists:users,email'],
            'password' => ['required', 'string'],
            'login_context' => [
                'required',
                'string',
                'in:user,admin',
            ],
        ];
    }

    /**
     * バリデーションエラー時のメッセージを設定
     * @return void
     */
    public function messages()
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
            'email.email' => 'メールアドレスは「ユーザー名@ドメイン」の形式で入力してください',
            'email.exists' => 'ログイン情報が登録されていません',
            'login_context.required' => 'ログイン種別が指定されていません。',
            'login_context.in' => 'ログイン種別が不正です。'
        ];
    }
}
