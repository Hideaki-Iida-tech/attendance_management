@extends($layout)
@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance/show.css') }}">
@endpush

@section('title')
勤怠詳細画面（一般ユーザー）
@endsection

@section('content')

<div class="attendance-show">
    <div class="attendance-show-title">
        <h1 class="attendance-show-title-inner">勤怠詳細</h1>
    </div>

    <form action="" class="attendance-show-form" method="post">
        <div class="attendance-show-content">

            <table class="attendance-show-table">
                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">名前</th>
                    <td class="attendance-show-table-col-second spacing">{{ optional($attendance->user)->name }}</td>
                    <td class="attendance-show-table-col-third"></td>
                    <td class="attendance-show-table-col-fourth"></td>
                </tr>

                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">日付</th>
                    <td class="attendance-show-table-col-second spacing">{{ $attendance->formated_year }}</td>
                    <td class="attendance-show-table-col-third"></td>
                    <td class="attendance-show-table-col-fourth spacing">{{ $attendance->formated_month_and_day }}</td>
                </tr>

                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">
                        出勤・退勤
                        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}" />
                    </th>
                    <td class="attendance-show-table-col-second">
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="clock_in_at" class="time-input" value="{{ $attendance->clock_in_time }}" />
                    </td>
                    <td class="attendance-show-table-col-third">～</td>
                    <td class="attendance-show-table-col-fourth">
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="clock_out_at" class="time-input" value="{{ $attendance->clock_out_time }}" />
                    </td>
                </tr>

                @foreach($attendance->breaks as $i => $break)
                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">
                        <input type="hidden" name="breaks[{{ $i }}][id]" value="{{ $break->id }}" />
                        @if($loop->iteration === 1)
                        休憩
                        @else
                        休憩{{ $loop->iteration}}
                        @endif
                    </th>
                    <td class="attendance-show-table-col-second">
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="breaks[{{ $i }}][start]" class="time-input" value="{{ old("breaks.$i.start", $break->break_start_time ?? '') }}" />
                    </td>
                    <td class="attendance-show-table-col-third">～</td>
                    <td class="attendance-show-table-col-fourth">
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="breaks[{{ $i }}][end]" class="time-input" value="{{ old("breaks.$i.end", $break->break_end_time ?? '') }}" />
                    </td>
                </tr>
                @endforeach
                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">
                        休憩{{ $attendance->breaks->count() +  1}}
                    </th>
                    <td class="attendance-show-table-col-second">
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="breaks[new][start]" class="time-input" value="{{ old('breaks.new.start') }}" />
                    </td>
                    <td class="attendance-show-table-col-third">～</td>
                    <td class="attendance-show-table-col-fourth">
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="breaks[new][end]" class="time-input" value="{{ old('breaks.new.end') }}" />
                    </td>
                </tr>

                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">備考</th>
                    <td colspan="3"><textarea name="reason" class="reason" id="">{{ old('reason') }}</textarea>
                    </td>
                </tr>
            </table>
        </div>
        <div class="form-button">
            <button type="submit" name="submit" class="modify-button">修正</button>
        </div>
        @csrf
    </form>
</div>
@endsection