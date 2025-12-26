<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Http\Responses\LoginResponse;
use App\Http\Requests\LoginRequest as MyLoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Enums\UserRole;

/**
 * Fortify に関する設定・カスタマイズを行うサービスプロバイダ.
 *
 * - ログインリクエスト(FormRequest)の差し替え
 * - ログイン後レスポンス(LoginResponse)の差し替え
 * - 認証ビュー(view)の指定
 * - ログイン試行回数のレート制限
 * - 独自バリデーションを使ったログイン処理
 */
class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Fortify の認証関連クラスをアプリ独自の実装に差し替える。
     *
     * このメソッドでは、以下のカスタマイズを行う。
     *
     * 1. ログイン成功後のレスポンス処理（LoginResponse）を
     *    独自実装に差し替え、ログイン元（一般 / 管理者）や
     *    ユーザー権限に応じたリダイレクト制御を可能にする。
     *
     * 2. Fortify が内部で使用する LoginRequest を
     *    アプリ独自の FormRequest に置き換え、
     *    ログイン時のバリデーションルールを自由に定義できるようにする。
     *
     * @return void
     */
    public function register(): void
    {
        // ログイン成功後のレスポンスを独自実装に差し替える
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);

        // Fortify が使用する LoginRequest を自作の LoginRequest に差し替える
        $this->app->bind(
            \Laravel\Fortify\Http\Requests\LoginRequest::class,
            \App\Http\Requests\LoginRequest::class,
        );
    }

    /**
     * アプリケーション起動時に実行される処理.
     *
     * Fortify の動作（使用するアクション・ビュー・認証ロジック・レート制限など）をここで定義する。
     *
     *  @return void
     */
    public function boot(): void
    {
        // ユーザー作成時に使用するアクションを登録
        Fortify::createUsersUsing(CreateNewUser::class);

        // 会員登録画面として使用するビューを指定
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // ログイン画面として使用するビューを指定
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // 1分あたり最大10回まで
        // 「メールアドレス + IPアドレス」の組み合わせをキーとして制限
        RateLimiter::for('login', function (Request $request) {
            $email = (string)$request->email;
            return Limit::perMinute(10)->by($email . $request->ip());
        });

        // 自作フォームリクエストを用いたログイン認証処理の定義（一般ユーザー/管理者共通）
        Fortify::authenticateUsing(function (Request $request) {
            // 自作の LoginRequest(FormRequest) を解決
            $loginReq = app(MyLoginRequest::class);

            // 自作フォームリクエストのルール・メッセージに基づいてバリデーションを実行
            $request->validate($loginReq->rules(), $loginReq->messages());

            // ログインフォームが一般ユーザー用か管理者用か(user or admin)を取得
            $context = $request->input('login_context');

            // ログインフォームの種別からユーザーの権限を解決
            $role = $this->resolveLoginRole($context);

            // 入力された email に一致するユーザーを取得
            $user = User::where('email', $request->input('email'))->first();

            // ログイン可能な場合は User モデルを返す
            if ($this->canAuthenticate($user, $request, $role)) {
                return $user;
                // それ以外の場合はバリデーションエラーとして扱い、例外を投げる
                // （このメッセージは password フィールドに紐づく）
            } else {
                throw ValidationException::withMessages([
                    'password' => ['ログイン情報が登録されていません'],
                ]);
            }

            // ここに到達した場合は null を返す（認証失敗扱い）
            // ※ 実際には上の例外で処理が終了するため到達しないが、
            //   authenticateUsing のコールバック仕様として null を返す形も想定されている
            return null;
        });
    }

    /**
     * ログイン可能かどうかを判定する。
     *
     * 指定されたユーザーについて、
     * - ユーザーが存在すること
     * - 入力されたパスワードが一致すること
     * - ユーザーの権限（管理者 / 一般ユーザー）が一致すること
     *
     * 上記すべてを満たす場合に true を返す。
     *
     * ※ Fortify::authenticateUsing 内で使用される認証判定用のヘルパーメソッド。
     *
     * @param User     $user    認証対象のユーザー
     * @param Request  $request ログインリクエスト
     * @param UserRole $role    ログインフォームに対応するユーザー権限
     *
     * @return bool 認証可能な場合は true、それ以外は false
     */
    private function canAuthenticate(User $user, Request $request, UserRole $role): bool
    {
        return $user &&
            Hash::check(
                $request->input('password'),
                $user->password
            ) &&
            $user->role === $role;
    }

    /**
     * ログインフォームの文脈からユーザー権限を解決する。
     *
     * ログイン時に送信される login_context の値に応じて、
     * 管理者用ログインか一般ユーザー用ログインかを判定し、
     * 対応する UserRole を返す。
     *
     * 想定外の値が指定された場合は、
     * セキュリティ上の安全策として一般ユーザー（USER）を返す。
     *
     * ※ Fortify::authenticateUsing 内で使用される補助メソッド。
     *
     * @param string $context ログインフォームの種別（例: 'admin', 'user'）
     *
     * @return UserRole 解決されたユーザー権限
     */
    private function resolveLoginRole(string $context): UserRole
    {
        // 管理者用ログインフォームの場合
        if ($context === 'admin') {
            // 管理者フラグ値を設定
            $role = UserRole::ADMIN;
            // 一般ユーザー用ログインフォームの場合
        } elseif ($context === 'user') {
            // 一般ユーザーフラグ値を設定
            $role = UserRole::USER;
            // その他の場合
        } else {
            // 一般ユーザーフラグ値を設定
            $role = UserRole::USER;
        }
        return $role;
    }
}
