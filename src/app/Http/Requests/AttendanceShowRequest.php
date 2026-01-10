<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceShowRequest extends FormRequest
{
    /**
     * リクエストの認可を行う。
     * 未ログインの場合はリクエストを拒否
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
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
            // 申請一覧画面から遷移してきた場合に送られてくるクエリパラメータrequest_idの存在・数値チェック
            'request_id' => ['sometimes', 'nullable', 'integer', 'exists:attendance_change_requests,id'],
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
