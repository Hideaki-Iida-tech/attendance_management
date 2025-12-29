<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreaksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // breaksテーブル
        // 休憩開始時刻、休憩終了時刻を記録するテーブル
        // 一日に何回でも休憩開始時刻、休憩終了時刻を記録可能
        Schema::create('breaks', function (Blueprint $table) {
            // 主キー
            $table->id();
            // attendancesテーブルのidを外部キーとするattendance_id
            // 休憩情報を紐づける出退勤レコードを特定
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            // 休憩開始時刻
            $table->datetime('break_start_at');
            // 休憩終了時刻（null許容）
            $table->datetime('break_end_at')->nullable();
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
        Schema::dropIfExists('breaks');
    }
}
