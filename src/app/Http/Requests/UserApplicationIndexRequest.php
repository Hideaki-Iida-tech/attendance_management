<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\ApplicationStatus;

class UserApplicationIndexRequest extends FormRequest
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
     * page パラメータは、申請一覧画面のタブ切り替え用の値を表す。
     * 本リクエストでは、以下の条件を満たす場合のみ有効とする。
     *
     * - page が送信されていない場合は検証を行わない（sometimes）
     * - null または空値は許可する（初期表示用）
     * - 値が存在する場合は文字列であること
     * - ApplicationStatus Enum に定義された name のみを許可する
     *
     * これにより、Enum に存在しない不正なステータス値の流入を防ぐ。
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules()
    {
        return [
            'page' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(
                    array_map(
                        fn(ApplicationStatus $status) => $status->name,
                        ApplicationStatus::cases()
                    )
                ),
            ],
        ];
    }
}
