<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\ApplicationStatus;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     *  ここはポリシー/権限設計に合わせて調整
     * 一般ユーザーが自分の勤怠だけ更新できる想定なら
     * Controller 側で
     * $attendance->user_id === auth()->id() を確認してもOK
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
        $workDate = $this->input('work_date');

        return [
            'work_date' => [
                'required',
                'date',
                // 同じユーザー・同じ日付について、未処理（pending）の申請は1件まで
                Rule::unique(
                    'attendance_change_requests'
                )->where(fn($query) => $query
                    ->where('user_id', $userId)
                    ->where('work_date', $workDate)
                    ->where('status', ApplicationStatus::PENDING))
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
     * 各入力項目・各バリデーションルールに対応する
     * エラーメッセージを日本語で明示的に指定することで、
     * デフォルトの機械的なメッセージをユーザー向けに分かりやすくする。
     *
     * - 必須入力エラー（required）
     * - 文字数制限エラー（max）
     * - 時刻形式エラー（date_format:H:i）
     * - 配列入力（breaks.* / breaks.new）に対するエラー
     *
     * 勤怠修正画面における入力ミスを、
     * どの項目で何が問題なのか直感的に伝えることを目的とする。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
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
     * rules() で行う単項目バリデーション（必須・形式チェック）では表現できない、
     * 以下のような「項目間の関係性」に基づく業務ルールを after バリデーションで検証する。
     *
     * 【検証内容】
     * - 出勤時間 <= 退勤時間 であること
     * - 休憩開始・終了は両方入力されていること（片方のみは不可）
     * - 休憩開始 <= 休憩終了 であること
     * - 休憩時間が 出勤時間〜退勤時間 の範囲内に収まっていること
     * - 既存休憩（breaks[index]）と新規休憩（breaks.new）の両方に同一ルールを適用
     *
     * 本メソッドでは、未入力時（null / 空文字）と入力済み時を明確に判別し、
     * 未入力データに対して不正な大小比較が行われないよう考慮している。
     *
     * また、エラーは対象となる入力項目（breaks.*.start / breaks.*.end 等）に
     * 紐づけて追加することで、画面上で適切な位置に表示されることを意図している。
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 出勤時間が退勤時間より後ろになっている場合
            // 退勤時間が手巾時間より前になっている場合
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
                    $validator->errors()->add(
                        "breaks.$index.end",
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
                $validator->errors()->add(
                    'breaks.new.end',
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
     * フォームの入力項目名（clock_in_at, breaks.*.start など）を、
     * ユーザーに分かりやすい日本語表記へ変換することで、
     * エラーメッセージの可読性とユーザー体験を向上させる。
     *
     * 配列形式で送信される休憩入力（既存休憩・新規休憩）については、
     * ワイルドカード指定を用いて共通の表示名を割り当てている。
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
