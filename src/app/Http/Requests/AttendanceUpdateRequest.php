<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\AttendanceChangeRequest;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     *  未ログインはリクエストを拒否
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the reques't.
     *
     * @return array
     */
    public function rules()
    {
        $userId = auth()->user()->id;
        $requestId = AttendanceChangeRequest::where('attendance_id', $this->id)->value('id');

        // 同じユーザー・同じ日付について、申請レコードは1件まで
        $uniqueRule = Rule::unique('attendance_change_requests', 'work_date')
            ->where(fn($query) => $query->where('user_id', $userId));

        // 更新の場合は自レコードのユニーク制約を解除
        if ($requestId !== null) {
            $uniqueRule->ignore($requestId);
        }
        //$workDate = $this->input('work_date');

        return [
            'work_date' => [
                'required',
                'date',
                // 同じユーザー・同じ日付について、申請レコードは1件まで
                $uniqueRule,
            ],

            // パスパラメータ id の存在・数値チェック
            'id' => ['required', 'integer', 'exists:attendances,id'],

            // 出勤・退勤（"HH:MM" のみ許可）
            'clock_in_at' => ['required', 'date_format:H:i'],
            'clock_out_at' => ['required', 'date_format:H:i'],

            // 備考
            'reason' => ['required', 'string', 'max:255'],

            // breaks 全体
            'breaks' => ['nullable', 'array'],

            // 既存休憩（0,1,2...の添え字分）
            'breaks.*.id' => ['nullable', 'integer'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],

            // 新規休暇（breaks[new][start/end]）
            'breaks.new' => ['nullable', 'array'],
            'breaks.new.start' => ['nullable', 'date_format:H:i'],
            'breaks.new.end' => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * バリデーションエラー時に表示するカスタムメッセージを定義する。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'work_date.unique' => 'すでに修正申請が出ています。同じユーザー・同じ日付について、未処理（pending）の申請は1件までです。',
            'reason.required' => '備考を入力してください',
            'reason.max' => '備考は255文字以内で入力してください。',
            'clock_in_at.required' => '出勤時間を入力してください。',
            'clock_in_at.date_format' => '出勤時刻の形式が不正です。HH:MMの形式で入力してください。',
            'clock_out_at.required' => '退勤時間を入力してください。',
            'clock_out_at.date_format' => '退勤時間の形式が不正です。HH:MMの形式で入力してください。',
            'breaks.*.start.date_format' => '休憩開始時刻の形式が不正です。HH:MMの形式で入力してください。',
            'breaks.*.end.date_format' => '休憩終了時刻の形式が不正です。HH:MMの形式で入力してください。',
            'breaks.new.start.date_format' => '休憩開始時刻の形式が不正です。HH:MMの形式で入力してください。',
            'breaks.new.end.date_format' => '休憩終了時刻の形式が不正です。HH:MMの形式で入力してください。',
        ];
    }

    /**
     * バリデーションルール適用後に、勤怠・休憩時間の整合性を検証する。
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 出勤時間が退勤時間より後ろになっている場合
            // 退勤時間が出勤時間より前になっている場合
            $in = $this->input('clock_in_at');
            $out = $this->input('clock_out_at');

            if ($in && $out && $in > $out) {
                $validator->errors()->add('clock_out_at', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // 休憩の整合性チェック
            $breaks = $this->input('breaks', []);

            // breaks は "new" も混ざるので、既存文分だけ取り出す
            $indexedBreaks = [];
            foreach ($breaks as $key => $value) {
                if ($key === 'new') continue;
                if (is_array($value)) $indexedBreaks[] = $value;
            }

            foreach ($indexedBreaks as $index => $break) {
                $start = $break['start'] ?? null;
                $end = $break['end'] ?? null;

                $startFilled = !is_null($start) && $start !== '';
                $endFilled = !is_null($end) && $end !== '';

                // 片方だけ入力はエラー
                if ($startFilled xor $endFilled) {
                    $validator->errors()->add(
                        "breaks.$index.start",
                        '休憩開始と終了は両方入力してください。'
                    );
                    continue;
                }

                // 両方入力されたら
                if ($startFilled && $endFilled) {
                    // 開始 <= 終了
                    if ($end < $start) {
                        $validator->errors()->add(
                            "breaks.$index.end",
                            '休憩終了は休憩開始より後にしてください。'
                        );
                    }
                    // 出勤/退勤が入力されている場合だけチェック
                    // 両方ともrequired指定しているのでフィルドチェックはなし
                    if ($in && $out) {
                        // 出勤時間 <= 休憩開始時間 <= 退勤時間
                        if ($start < $in || $out < $start) {
                            $validator->errors()->add(
                                "breaks.$index.start",
                                '休憩時間が不適切な値です'
                            );
                        }

                        // 休憩終了時間 =< 退勤時間
                        if ($out < $end) {
                            $validator->errors()->add(
                                "breaks.$index.end",
                                '休憩時間もしくは退勤時間が不適切な値です'
                            );
                            $validator->errors()->add(
                                'clock_out_at',
                                '休憩時間もしくは退勤時間が不適切な値です'
                            );
                        }
                    }
                }
            }

            // 新規休暇も同様
            $newStart = data_get($breaks, 'new.start');
            $newEnd = data_get($breaks, 'new.end');

            $startFilled = !is_null($newStart) && $newStart !== '';
            $endFilled = !is_null($newEnd) && $newEnd !== '';

            // 片方だけ入力はエラー
            if ($startFilled xor $endFilled) {
                $validator->errors()->add(
                    'breaks.new.start',
                    '休憩開始と終了は両方入力してください。'
                );
            }

            // 両方入力されたら
            if ($startFilled && $endFilled) {
                //開始 <= 終了
                if ($newEnd < $newStart) {
                    $validator->errors()->add(
                        'breaks.new.end',
                        '休憩終了は休憩開始より後にしてください。'
                    );
                }

                // 出勤/退勤が入力されている場合だけチェック
                // 両方ともrequired指定しているのでフィルドチェックはなし
                if ($in && $out) {
                    // 出勤時間 <= 休憩開始時間 <= 退勤時間
                    if ($newStart < $in || $out < $newStart) {
                        $validator->errors()->add(
                            "breaks.new.start",
                            '休憩時間が不適切な値です'
                        );
                    }

                    // 休憩終了時間 =< 退勤時間
                    if ($out < $newEnd) {
                        $validator->errors()->add(
                            "breaks.new.end",
                            '休憩時間もしくは退勤時間が不適切な値です'
                        );
                        $validator->errors()->add(
                            'clock_out_at',
                            '休憩時間もしくは退勤時間が不適切な値です'
                        );
                    }
                }
            }
        });
    }

    /**
     * バリデーションエラー時に使用する属性名（表示名）を定義する。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'clock_in_at' => '出勤',
            'clock_out_at' => '退勤',
            'reason' => '備考',
            'breaks.*.start' => '休憩開始',
            'breaks.*.end' => '休憩終了',
            'breaks.new.start' => '休憩開始',
            'breaks.new.end' => '休憩終了',
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
