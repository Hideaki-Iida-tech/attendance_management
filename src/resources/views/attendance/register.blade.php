@extends($layout)
@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/register.css') }}">
@endsection

@section('content')
<div class="attendance-register">
    <form action="" class="attendance-register-form" method="">

        <div class="attendance-register-status">
            <span class="status-value">

                @if($status === 0)
                勤務外
                @elseif($status === 1)
                出勤中
                @elseif($status === 2)
                休憩中
                @elseif($status === 3)
                退勤済
                @endif

            </span>
        </div>

        <div class="attendance-register-date">
            <span class="date-value">

            </span>
        </div>

        <div class="attendance-register-time">
            <span class="time-value"></span>
        </div>

        <div class="attendance-register-button">

            @if($status === 0)
            <button class="attendance-button-clock-in" name="action" value={{$status}}>
                出勤
            </button>
            @elseif($status === 1)
            <button class="attendance-button-clock-out" name="action" value={{$status}}>
                退勤
            </button>
            <button class="attendance-button-break-start" name="action" value={{$status}}>
                休憩入
            </button>
            @elseif($status === 2)
            <button class="attendance-button-break-end" name="action" value={{$status}}>
                休憩戻
            </button>
            @elseif($status === 3)
            <p class="end-message">お疲れさまでした。</p>
            @endif
        </div>

    </form>
</div>
<script>
    function updateTime() {
        const now = new Date();

        // 日付（YYYY年MM月DD日）を整形
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');

        // 曜日を整形
        const weekDays = ['(日)', '(月)', '(火)', '(水)', '(木)', '(金)', '(土)'];
        const weekday = weekDays[now.getDay()];

        // 時：分を "HH:MM" 形式で整形する
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        // 表示更新
        document.querySelector('.date-value').textContent = `${year}年${month}月${day}日${weekday}`;
        document.querySelector('.time-value').textContent = `${hours}:${minutes}`;
    }

    // 最初の一回表示
    updateTime();

    // 1分（60,000ミリ秒）ごとに更新
    setInterval(updateTime, 1000 * 60);
</script>
@endsection