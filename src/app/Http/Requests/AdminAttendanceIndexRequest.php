<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceIndexRequest extends FormRequest
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
            // ?day=2026-01-01
            'day' => [
                'nullable',
                'date_format:Y-m-d'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'day.date_format' => '年月日の形式が正しくありません。',
        ];
    }
}
