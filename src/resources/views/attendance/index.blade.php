@extends($layout)
@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endpush

@section('title')
勤怠一覧画面（一般ユーザー）
@endsection

@section('content')

<div class="attendance-list">
    <div class="attendance-list-title">
        <h1 class="attendance-list-title-inner">勤怠一覧</h1>
    </div>
    <div class="present-month-selector">
        <p><a href="/attendance/list?month={{ $prevMonth }}" class="month-pre">←前月</a></p>
        <p class="present-month"><img src="{{asset('images/calendar.png')}}" alt="" class="calender-img">{{ $yearMonth }}</p>
        <p><a href="/attendance/list?month={{ $nextMonth }}" class="month-next">翌月→</a></p>
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
                <td class="date-col">{{ $date['label'] }}</td>
                <td>{{ $date['attendance']?->clock_in_time }}</td>
                <td>{{ $date['attendance']?->clock_out_time }}</td>
                <td>{{ $date['attendance']?->formated_break_time }}</td>
                <td>{{ $date['attendance']?->formated_working_time }}</td>
                @if(empty($date['attendance']))
                <td><span class="detail-link">詳細</span></td>
                @else
                <td><a href="/attendance/{{ $date['attendance']?->id }}" class="detail-link">詳細</a></td>
                @endif
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection