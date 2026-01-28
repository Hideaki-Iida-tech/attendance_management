<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\AttendanceChangeRequest;

class AdminApplicationUpdateRequest extends FormRequest
{
    /**
     * リクエストの認可を行う。
     * 未ログインの場合と管理者権限でログインしていない場合、
     * 該当する申請レコードが存在しない場合と、申請が承認済みの場合は
     * リクエストを拒否
     *
     * @return bool
     */
    public function authorize()
    {
        // 管理者でログインしていることだけをここで担保
        $user = $this->user();
        if (!$user || !$user->isAdmin()) return false;

        $id = (int) $this->route('attendance_correct_request');
        $req = AttendanceChangeRequest::find($id);
        return $req && !$req->isApproved();
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
