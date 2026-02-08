<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Attendance;
use Illuminate\Validation\Rule;

class AttendanceShowRequest extends FormRequest
{
    /**
     * リクエストの認可を行う。
     *
     * 管理者用／一般ユーザー用の文脈に応じて、勤怠詳細画面へのアクセス可否を判定する。
     *
     * @return bool 認可される場合は true、拒否される場合は false
     */
    public function authorize()
    {
        $user = $this->user();

        if (!$user) {
            return false; // 未ログインは拒否
        }

        // 文脈判定（ユーザー入力ではなく ミドルウェアで設定された attributes から）
        $isAdminContext = (bool) $this->attributes->get('is_admin_context', false);

        // 対象勤怠ID取得（route('id')を使っている前提）
        $attendanceId = (int) $this->route('id');

        // 対象勤怠の所有者(user_id)を最小コストで取得
        $ownerUserId = Attendance::query()
            ->whereKey($attendanceId)
            ->value('user_id');

        if (!$ownerUserId) {
            // 対象勤怠の所有者(user_id)が存在しない場合は拒否
            return false;
        }

        if ($isAdminContext) {
            // 管理者文脈：管理者だけ許可（対象者が誰の勤怠でも閲覧可）

            return $user->isAdmin();
        }

        // 一般文脈：本人の勤怠だけ許可
        return $user->id === (int) $ownerUserId;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // パスパラメータ id の存在・数値チェック
            'id' => ['required', 'integer', 'exists:attendances,id'],
        ];
    }

    /**
     * パスパラメータidをバリデーション対象に追加するメソッド
     * @return array
     */
    public function validationData()
    {
        return array_merge(
            $this->all(),
            ['id' => (int)$this->route('id')]
        );
    }
}
