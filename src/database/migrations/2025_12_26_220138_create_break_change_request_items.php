<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakChangeRequestItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // break_change_request_itemsテーブル
        // 申請内容のうち、休憩開始時刻、休憩終了時刻を記録するテーブル
        Schema::create('break_change_request_items', function (Blueprint $table) {
            // 主キー
            $table->id();
            // attendance_change_requestsテーブルのidを外部キーとするrequest_id
            // 申請ヘッダーを特定
            $table->foreignId('request_id')->constrained('attendance_change_requests')->cascadeOnDelete();
            // 申請種別（0:add 1:update 2:delete enumで定義）
            $table->unsignedTinyInteger('action');
            // breaksテーブルのidを外部キーとするtarget_break_id
            // 申請対象の休憩レコードを特定（update/deleteの場合）
            // addの場合はnull
            $table->foreignId('target_break_id')->nullable()->constrained('breaks')->cascadeOnDelete();
            // 修正追加後の休憩開始時刻（null許容）
            $table->datetime('new_break_start_at')->nullable();
            // 修正追加後の休憩終了時刻（null許容）
            $table->datetime('new_break_end_at')->nullable();
            // 修正前の休憩開始時刻（null許容）
            $table->datetime('old_break_start_at')->nullable();
            // 修正前の休憩終了時刻（null許容）
            $table->datetime('old_break_end_at')->nullable();
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
        Schema::dropIfExists('break_change_request_items');
    }
}
