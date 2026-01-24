<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendancesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userId = User::where('name', 'テストユーザー1')->value('id');

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-05',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-05 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-05 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-06',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-06 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-06 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-07',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-07 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-07 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-08',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-08 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-08 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-09',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-09 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-09 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-13',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-13 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-13 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-14',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-14 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-14 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-15',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-15 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-15 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-16',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-16 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-16 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-19',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-19 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-19 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-20',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-20 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-20 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-21',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-21 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-21 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-22',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-22 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-22 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-23',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-23 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-23 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-26',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-26 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-26 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-27',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-27 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-27 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-28',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-28 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-28 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-29',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-29 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-29 17:00'),
            ]
        );

        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'work_date' => '2026-01-30',
            ],
            [
                'clock_in_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-30 08:30'),
                'clock_out_at' => Carbon::createFromFormat('Y-m-d H:i', '2026-01-30 17:00'),
            ]
        );
    }
}
