@extends($layout)
@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endpush

@section('title')
勤怠一覧画面（一般ユーザー）
@endsection

@section('content')

@php
// 必ずコントローラーに移植
use Carbon\Carbon;

$dates = [];

$start = Carbon::now()->startOfMonth();
$end = Carbon::now()->endOfMonth();

$current = $start->copy();

$weekDays = ['(日)', '(月)', '(火)', '(水)', '(木)', '(金)', '(土)'];

while ($current->lte($end)) {
$dates[] = [
'date' => $current->format('m月d日'),
'weekday' => $weekDays[$current->dayOfWeek]
];
$current->addDay();
}
@endphp

<div class="attendance-list">
    <div class="attendance-list-title">
        <h1 class="attendance-list-title-inner">勤怠一覧</h1>
    </div>
    <div class="present-month-selector">
        <p><a href="" class="month-pre">←前月</a></p>
        <p class="present-month"><img src="{{asset('images/calendar.png')}}" alt="" class="calender-img">2025/12</p>
        <p><a href="" class="month-next">次月→</a></p>
    </div>

    <div class="attendance-list-content">
        <table class="attendance-list-table">
            <tr class="attendance-list-table-title">
                <th class="date-col">日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
            @foreach($dates as $date)
            <tr class="attendance-list-table-row">
                <td class="date-col">{{$date['date'] . $date['weekday']}}</td>
                <td>09:00</td>
                <td>18:00</td>
                <td>01:00</td>
                <td>08:00</td>
                <td><a href="" class="detail-link">詳細</a></td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection