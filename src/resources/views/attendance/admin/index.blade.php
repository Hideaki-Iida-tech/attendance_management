@extends($layout)
@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance/admin/index.css') }}">
@endpush

@section('title')
勤怠一覧画面（管理者）
@endsection

@section('content')

<div class="attendance-list">
    <div class="attendance-list-title">
        <h1 class="attendance-list-title-inner">{{ $titleYearMonthDay }}の勤怠</h1>
    </div>
    <div class="present-month-selector">
        <p><a href="/admin/attendance/list/?day={{ $preDay }}" class="month-pre">←前日</a></p>
        <p class="present-month"><img src="{{asset('images/calendar.png')}}" alt="" class="calender-img">{{ $yearMonthDay }}</p>
        <p><a href="/admin/attendance/list/?day={{ $nextDay }}" class="month-next">翌日→</a></p>
    </div>

    <div class="attendance-list-content">
        <table class="attendance-list-table">
            <tr class="attendance-list-table-title">
                <th class="name-col">名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
            @foreach($attendances as $attendance)
            <tr class="attendance-list-table-row">
                <td class="name-col">{{ $attendance->user?->name }}</td>
                <td>{{ $attendance?->clock_in_time }}</td>
                <td>{{ $attendance?->clock_out_time }}</td>
                <td>{{ $attendance?->formated_break_time}}</td>
                <td>{{ $attendance?->formated_working_time }}</td>
                <td><a href="/attendance/{{ $attendance?->id }}" class="detail-link">詳細</a></td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection