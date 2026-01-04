<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceIndexRequest extends FormRequest
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
