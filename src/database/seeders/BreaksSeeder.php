<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BreakTime;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class BreaksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userId = User::where('email', 'test1@example.com')->value('id');
        $workDate = '2026-01-05';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-05 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-05 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-05 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-05 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-05 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-05 15:15'),
            ]
        );

        $workDate = '2026-01-06';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-06 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-06 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-06 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-06 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-06 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-06 15:15'),
            ]
        );

        $workDate = '2026-01-07';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-07 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-07 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-07 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-07 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-07 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-07 15:15'),
            ]
        );

        $workDate = '2026-01-08';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-08 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-08 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-08 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-08 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-08 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-08 15:15'),
            ]
        );

        $workDate = '2026-01-09';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-09 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-09 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-09 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-09 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-09 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-09 15:15'),
            ]
        );

        $workDate = '2026-01-13';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-13 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-13 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-13 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-13 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-13 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-13 15:15'),
            ]
        );

        $workDate = '2026-01-14';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-14 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-14 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-14 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-14 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-14 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-14 15:15'),
            ]
        );

        $workDate = '2026-01-15';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-15 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-15 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-15 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-15 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-15 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-15 15:15'),
            ]
        );

        $workDate = '2026-01-16';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-16 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-16 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-16 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-16 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-16 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-16 15:15'),
            ]
        );

        $workDate = '2026-01-19';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-19 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-19 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-19 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-19 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-19 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-19 15:15'),
            ]
        );

        $workDate = '2026-01-20';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-20 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-20 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-20 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-20 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-20 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-20 15:15'),
            ]
        );

        $workDate = '2026-01-21';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-21 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-21 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-21 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-21 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-21 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-21 15:15'),
            ]
        );

        $workDate = '2026-01-22';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-22 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-22 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-22 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-22 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-22 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-22 15:15'),
            ]
        );

        $workDate = '2026-01-23';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-23 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-23 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-23 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-23 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-23 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-23 15:15'),
            ]
        );

        $workDate = '2026-01-26';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-26 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-26 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-26 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-26 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-26 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-26 15:15'),
            ]
        );

        $workDate = '2026-01-27';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-27 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-27 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-27 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-27 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-27 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-27 15:15'),
            ]
        );

        $workDate = '2026-01-28';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-28 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-28 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-28 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-28 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-28 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-28 15:15'),
            ]
        );

        $workDate = '2026-01-29';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-29 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-29 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-29 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-29 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-29 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-29 15:15'),
            ]
        );

        $workDate = '2026-01-30';
        $attendance = Attendance::where('user_id', $userId)->whereDate('work_date', $workDate)->firstOrFail();

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-30 10:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-30 10:15'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-30 12:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-30 13:00'),
            ]
        );

        BreakTime::create(
            [
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-30 15:00'),
                'break_end_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-30 15:15'),
            ]
        );
    }
}
