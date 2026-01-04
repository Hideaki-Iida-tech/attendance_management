<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    /**
     * マスアサインメントを許可する属性。
     *
     * フォーム入力やリクエストデータから
     * 一括代入（create / update）してよいカラムを定義する。
     */
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
    ];

    /**
     * Cast attributes to native types.
     *
     * clock_in_at / clock_out_at を datetime（Carbon）として扱うことで、
     * 時刻の比較・フォーマット（format / diff / translatedFormat 等）を
     * 安全かつ直感的に行えるようにする。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'clock_in_at'  => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    /**
     * 勤怠に紐づく休憩（BreakTime）を取得するリレーション。
     *
     * 1つの勤怠（Attendance）は、0件以上の休憩レコードを持つ。
     * 勤務中・休憩中・休憩終了の判定に利用される。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function breaks()
    {
        return $this->hasMany(BreakTime::class);
    }

    /**
     * 指定したユーザー・日付が「勤務外（Off Duty）」であるかを判定する。
     *
     * 次のいずれかを満たす場合に true を返す。
     * - 指定した日付の勤怠レコードが存在しない場合
     * - 勤怠レコードは存在するが、出勤・退勤ともに未記録で、
     *   かつ休憩レコードも存在しない場合
     *
     * 出勤済み、休憩中、または退勤済みの場合は false を返す。
     *
     * @param int    $userId 判定対象のユーザーID
     * @param string $date   勤務日（YYYY-MM-DD 形式）
     * @return bool          勤務外であれば true、そうでなければ false
     */
    public static function isOffDuty(int $userId, string $date): bool
    {
        // 指定したユーザーIDと勤務日に合致するレコードの一件目を取得
        $attendance = static::query()
            ->withExists('breaks')
            ->where('user_id', $userId)
            ->whereDate('work_date', $date)
            ->first();
        // レコードが存在しない場合
        if (!$attendance) {
            return true; // trueを返す
        }

        // 指定したユーザーIDと勤務日に合致するレコードが存在するが、clock_in_atとclock_out_atがnullの場合、trueを返す
        return is_null($attendance->clock_in_at) &&
            is_null($attendance->clock_out_at) &&
            !$attendance->breaks_exists();
    }

    /**
     * 指定したユーザー・日付が「勤務中（Working）」であるかを判定する。
     *
     * 次のすべてを満たす場合に true を返す。
     * - 指定した日付の勤怠レコードが存在する
     * - 出勤時刻（clock_in_at）が記録されている
     * - 退勤時刻（clock_out_at）が未記録である
     * - 休憩レコードが存在しない、または
     *   すべての休憩レコードについて開始・終了時刻が記録されている
     *
     * いずれかを満たさない場合は false を返す。
     *
     * @param int    $userId 判定対象のユーザーID
     * @param string $date   勤務日（YYYY-MM-DD 形式）
     * @return bool          勤務中であれば true、そうでなければ false
     */
    public static function isWorking(int $userId, string $date): bool
    {
        // 指定したユーザーIDと勤務日に合致するレコードの一件目を取得
        $attendance = static::with('breaks')
            ->where('user_id', $userId)
            ->whereDate('work_date', $date)
            ->first();

        // 合致するレコードがない場合
        if (!$attendance) {
            return false;
        }

        // 勤務開始していて、まだ退勤していないことが「勤務中」の前提
        if (
            is_null($attendance->clock_in_at) ||
            !is_null($attendance->clock_out_at)
        ) {
            return false;
        }

        // 休憩レコードが無ければ、そのまま勤務中
        if ($attendance->breaks->isEmpty()) {
            return true;
        }

        // 休憩がある場合は「すべての休憩が開始・終了ともに埋まっている」なら勤務中
        $allBreaksCompleted = $attendance->breaks->every(
            fn($break) =>
            !is_null($break->break_start_at) && !is_null($break->break_end_at)
        );

        // すべての休憩開始・終了ともに埋まっているなら、true（勤務中）一つでも埋まっていない箇所があるならfalse（休憩中）を返す
        return $allBreaksCompleted;
    }

    /**
     * 指定したユーザー・日付の勤務が「完了（退勤済み）」しているかを判定する。
     *
     * 出勤時刻（clock_in_at）および退勤時刻（clock_out_at）の
     * 両方が記録されている勤怠レコードが存在する場合に true を返す。
     *
     * 勤怠レコードが存在しない場合や、出勤または退勤が未記録の場合は false を返す。
     *
     * @param int    $userId 判定対象のユーザーID
     * @param string $date   勤務日（YYYY-MM-DD 形式）
     * @return bool          勤務が完了していれば true、そうでなければ false
     */
    public static function isFinished(int $userId, string $date): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', $date)
            ->whereNotNull('clock_in_at')
            ->whereNotNull('clock_out_at')
            ->exists();
    }

    /**
     * 指定したユーザー・日付が「休憩中（On Break）」であるかを判定する。
     *
     * 次のすべてを満たす場合に true を返す。
     * - 指定した日付の勤怠レコードが存在する
     * - 出勤時刻（clock_in_at）が記録されている
     * - 退勤時刻（clock_out_at）が未記録である
     * - 休憩レコードが存在し、
     *   そのうち少なくとも1件の休憩について
     *   開始時刻は記録されているが、終了時刻が未記録である
     *
     * 上記条件を満たさない場合は false を返す。
     *
     * @param int    $userId 判定対象のユーザーID
     * @param string $date   勤務日（YYYY-MM-DD 形式）
     * @return bool          休憩中であれば true、そうでなければ false
     */
    public static function isOnBreak(int $userId, string $date): bool
    {
        // 指定したユーザーIDと勤務日に合致するレコードの一件目を取得
        $attendance = static::with('breaks')
            ->where('user_id', $userId)
            ->whereDate('work_date', $date)
            ->first();

        // 合致するレコードがない場合
        if (!$attendance) {
            return false;
        }

        // 勤務開始していて、まだ退勤していないことが「勤務中」の前提
        if (
            is_null($attendance->clock_in_at) ||
            !is_null($attendance->clock_out_at)
        ) {
            return false;
        }

        // 休憩レコードが無ければ、休憩中ではない
        if ($attendance->breaks->isEmpty()) {
            return false;
        }

        // 休憩レコードがある場合は「休憩が開始が埋まっており・終了が埋まっていない場合」が一レコードでもあるなら休憩中

        // 未終了の休憩（開始あり・終了なし）が1件でもあれば休憩中
        return $attendance->breaks->contains(fn($break) =>
        !is_null($break->break_start_at) &&
            is_null($break->break_end_at));
    }

    public function getClockInTimeAttribute(): ?string
    {
        return $this->clock_in_at
            ? $this->clock_in_at->format('H:i')
            : null;
    }

    public function getClockOutTimeAttribute(): ?string
    {
        return $this->clock_out_at
            ? $this->clock_out_at->format('H:i')
            : null;
    }

    public function getFormatedBreakTimeAttribute(): ?string
    {

        // 休憩が存在しない場合
        if (!$this->relationLoaded('breaks') && !$this->breaks()->exists()) {
            return '0:00';
        }

        $totalMinutes = $this->breaks
            ->filter(fn($break) => $break->break_start_at &&
                $break->break_end_at)
            ->sum(function ($break) {
                return $break->break_end_at->diffInMinutes($break->break_start_at);
            });

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    public function getFormatedWorkingTimeAttribute(): ?string
    {
        // 出勤・退勤がそろっていない日は勤務時間を出せいない
        if (!$this->clock_in_at || !$this->clock_out_at) {
            return null;
        }

        // 総勤務分（出勤～退勤）
        $workMinutes = $this->clock_out_at->diffInMinutes($this->clock_in_at);

        // 休憩分（開始・終了がそろっている休憩だけ合算）
        $breaks = $this->relationLoaded('breaks') ?
            $this->breaks : $this->breaks()->get();

        $breakMinutes = $breaks->filter(fn($break) => $break->break_start_at && $break->break_end_at)
            ->sum(fn($break) => $break->break_end_at->diffInMinutes($break->break_start_at));

        // 実勤務分（マイナスにならないようガード）
        $totalMinutes = max(0, $workMinutes - $breakMinutes);

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }
}
