<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Fortify のログイン成功後レスポンスを生成する。
     *
     * ログイン画面（一般 / 管理者）で送信された `login_context` を元に、
     * ログイン後のリダイレクト先を切り替える。
     *
     * - 管理者ログイン画面からのログインで、管理者権限を持たない場合は
     *   認証を解除し、管理者ログイン画面へ戻す。
     * - 管理者権限を持つユーザーは管理画面へ遷移させる。
     * - それ以外のユーザーは一般ユーザー向け勤怠画面へ遷移させる。
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $context = $request->input('login_context'); // user or admin

        // 管理画面からログインしたのに一般ユーザーなら弾く
        if ($context === 'admin' && !optional($request->user())->isAdmin()) {
            auth()->logout();

            return redirect('/admin/login')->withErrors(['email' => '管理者アカウントではありません']);
        }

        // 管理者なら管理画面へ、それ以外は一般画面へ
        if (optional($request->user())->isAdmin()) {
            return redirect('/admin/attendance/list');
        }

        return redirect('/attendance');
    }
}
