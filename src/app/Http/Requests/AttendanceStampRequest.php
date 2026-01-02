<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceStampRequest extends FormRequest
{
    /**
     * リクエストの認可を行う。
     * ※ログイン必須にしたい場合は true のままでOK（ルート側で auth を付ける想定）
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 打刻アクションのバリデーションルールを定義する。
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'action' => [
                'required',
                'string',
                Rule::in(['clock_in', 'clock_out', 'break_start', 'break_end']),
            ],
        ];
    }


    /**
     * バリデーションエラーメッセージ。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'action.required' => '打刻種別が指定されていません。',
            'action.string' => '打刻種別の形式が不正です。',
            'action.in' => '打刻種別が不正です。',
        ];
    }
}
