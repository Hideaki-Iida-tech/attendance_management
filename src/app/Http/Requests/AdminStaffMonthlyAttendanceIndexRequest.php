<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminStaffMonthlyAttendanceIndexRequest extends FormRequest
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
            // ?month=2026-01
            'month' => ['nullable', 'date_format:Y-m'],
        ];
    }

    public function messages(): array
    {
        return [
            'month.date_format' => '年月の形式が正しくありません。',
        ];
    }
}
