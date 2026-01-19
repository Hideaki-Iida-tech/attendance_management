<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminApplicationShowRequest extends FormRequest
{
    /**
     * リクエストの認可を行う。
     * 未ログインの場合はリクエストを拒否
     *
     * @return bool
     */
    public function authorize()
    {
        // 管理者でログインしていることだけをここで担保
        $user = $this->user();
        return $user && $user->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // パスパラメータ attendance_correct_request の存在・数値チェック
            'attendance_correct_request' => ['required', 'integer', 'exists:attendance_change_requests,id'],
        ];
    }

    /**
     * パスパラメータattendance_correct_requestをバリデーション対象に追加するメソッド
     * @return array
     */
    public function validationData()
    {
        return array_merge(
            $this->all(),
            ['attendance_correct_request' => (int)$this->route('attendance_correct_request')]
        );
    }
}
