<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceIndexRequest;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    public function index(AdminAttendanceIndexRequest $request)
    {
        $layout = 'layouts.admin-menu';

        $day = $request->query('day'); //"2026-01-01"

        $current = $day ?
            Carbon::createFromFormat('Y-m-d', $day)
            : now();

        $yearMonthDay = $current->format('Y/m/d');
        $titleYearMonthDay = $current->format('Y年m月d日');

        $preDay = $current->copy()->subDay()->format('Y-m-d');
        $nextDay = $current->copy()->addDay()->format('Y-m-d');

        $attendances = Attendance::with('user', 'breaks')
            ->whereDate('work_date', $current)->get();

        return view('attendance.admin.index', compact(
            'layout',
            'attendances',
            'yearMonthDay',
            'titleYearMonthDay',
            'preDay',
            'nextDay',
        ));
    }
}
