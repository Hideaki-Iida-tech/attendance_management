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
        <h1 class="attendance-list-title-inner">2025年12月11日の勤怠一覧</h1>
    </div>
    <div class="present-month-selector">
        <p><a href="" class="month-pre">←前月</a></p>
        <p class="present-month"><img src="{{asset('images/calendar.png')}}" alt="" class="calender-img">2025/12</p>
        <p><a href="" class="month-next">次月→</a></p>
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
            @for($i = 0; $i < 10; $i++)
                <tr class="attendance-list-table-row">
                <td class="name-col">西 伶奈</td>
                <td>09:00</td>
                <td>18:00</td>
                <td>01:00</td>
                <td>08:00</td>
                <td><a href="" class="detail-link">詳細</a></td>
                </tr>
                @endfor
        </table>
    </div>
</div>
@endsection