<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // attendancesテーブル
        // 出退勤時刻を記録するテーブル
        Schema::create('attendances', function (Blueprint $table) {
            // 主キー
            $table->id();
            // usersテーブルのidを外部キーとするuser_id（出退勤の記録者）
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // 勤務日
            $table->date('work_date');
            // 出勤日時
            $table->datetime('clock_in_at');
            // 退勤日時（null許容）
            $table->datetime('clock_out_at')->nullable();
            // user_id + work_date の複合ユニーク制約
            // 1人のユーザーにつき、1日1レコードのみ
            $table->unique(['user_id', 'work_date']);
            // タイムスタンプ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
