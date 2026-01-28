@extends($layout)
@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance/admin/staff.css') }}">
@endpush

@section('title')
スタッフ別勤怠一覧画面（管理者）
@endsection

@section('content')

<div class="attendance-list">
    <div class="attendance-list-title">
        <h1 class="attendance-list-title-inner">{{$user->name}}さんの勤怠</h1>
    </div>
    <div class="present-month-selector">
        <p><a href="/admin/attendance/staff/{{ $user->id }}/?month={{ $prevMonth }}" class="month-pre">←前月</a></p>
        <p class="present-month"><img src="{{asset('images/calendar.png')}}" alt="" class="calender-img">{{ $yearMonth }}</p>
        <p><a href="/admin/attendance/staff/{{ $user->id }}/?month={{ $nextMonth }}" class="month-next">次月→</a></p>
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
    <div class="attendance-list-output">
        <button id="csv-export" class="output-button">ＣＳＶ出力</button>
        <iframe id="csv-frame" style="display:none;"></iframe>
    </div>

</div>

<script>
    document.getElementById('csv-export').addEventListener('click', () => {
        // ページ遷移させずにダウンロード開始
        document.getElementById('csv-frame').src = "/admin/attendance/staff/{{ $user->id }}/export?month={{ $currentMonth }}";

        // cookie を監視
        const timer = setInterval(() => {
            if (document.cookie.includes('csv_downloaded=')) {
                clearInterval(timer);

                alert('CSVの作成が完了しました');

                // cookie を削除
                document.cookie = 'csv_downloaded=; Max-Age=0; path=/';
            }
        }, 500);
    });
</script>
@endsection