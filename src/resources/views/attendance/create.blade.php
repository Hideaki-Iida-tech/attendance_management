@extends($layout)
@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance/create.css') }}">
@endpush

@section('title')
勤怠登録画面（一般ユーザー）
@endsection

@section('content')
<div class="attendance-register">

    <form action="/attendance" class="attendance-register-form" method="post">

        @if(session('message'))
        <p class="attendance-success">
            {{ session('message') }}
        </p>
        @endif

        @if($errors->any())
        <div class="attendance-error-box">
            {{-- stamp() で入れているキー "error" があれば優先して表示 --}}
            @if($errors->has('error'))
            <p class="attendance-error">{{ $errors->first('error') }}</p>
            @else
            {{-- それ以外（バリデーションなど）は全部表示 --}}
            @foreach($errors->all() as $message)
            <p class="attendance-error">{{ $message }}</p>
            @endforeach
            @endif
        </div>
        @endif

        <div class="attendance-register-status">
            <span class="status-value">

                @if($state === \App\Enums\AttendanceState::OFF_DUTY)
                勤務外
                @elseif($state === \App\Enums\AttendanceState::WORKING)
                出勤中
                @elseif($state === \App\Enums\AttendanceState::ON_BREAK)
                休憩中
                @elseif($state === \App\Enums\AttendanceState::FINISHED)
                退勤済
                @endif

            </span>
        </div>

        <div class="attendance-register-date">
            <span class="date-value">

                @if($state === \App\Enums\AttendanceState::FINISHED)
                {{ $workDate }}
                @endif

            </span>
        </div>

        <div class="attendance-register-time">
            <span class="time-value">

                @if($state === \App\Enums\AttendanceState::FINISHED)
                {{ $clockOutAt }}
                @endif

            </span>
        </div>

        <div>
            @error('action')
            <p class="attendance-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="attendance-register-button">

            @if($state === \App\Enums\AttendanceState::OFF_DUTY)
            <button class="attendance-button-clock-in" name="action" value="clock_in">
                出勤
            </button>
            @elseif($state === \App\Enums\AttendanceState::WORKING)
            <button class="attendance-button-clock-out" name="action" value="clock_out">
                退勤
            </button>
            <button class="attendance-button-break-start" name="action" value="break_start">
                休憩入
            </button>
            @elseif($state === \App\Enums\AttendanceState::ON_BREAK)
            <button class="attendance-button-break-end" name="action" value="break_end">
                休憩戻
            </button>
            @elseif($state === \App\Enums\AttendanceState::FINISHED)
            <p class="end-message">お疲れさまでした。</p>
            @endif

        </div>
        @csrf
    </form>
</div>

@if($state !== \App\Enums\AttendanceState::FINISHED)
<script>
    function updateTime() {
        const now = new Date();

        // 日付（YYYY年MM月DD日）を整形
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1);
        const day = String(now.getDate());

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
@endif

@endsection