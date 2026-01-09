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

    <form action="/attendance/{{ $attendance->id }}" class="attendance-show-form" method="post">
        <div class="attendance-show-content">

            <table class="attendance-show-table">
                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">名前</th>
                    @if($editable)
                    <td class="attendance-show-table-col-second spacing">{{ optional($attendance->user)->name }}</td>
                    @else
                    <td class="attendance-show-table-col-second spacing">{{ optional($pendingOrApprovedRequest->user)->name }}</td>
                    @endif
                    <td class="attendance-show-table-col-third"></td>
                    <td class="attendance-show-table-col-fourth"></td>
                </tr>

                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">日付</th>
                    @if($editable)
                    <td class="attendance-show-table-col-second spacing">{{ $attendance->formated_year }}</td>
                    <input type="hidden" name="work_date" value="{{ $attendance->work_date }}" />
                    @else
                    <td class="attendance-show-table-col-second spacing">{{ $pendingOrApprovedRequest->formated_year }}</td>
                    @endif
                    <td class="attendance-show-table-col-third"></td>
                    @if($editable)
                    <td class="attendance-show-table-col-fourth spacing">{{ $attendance->formated_month_and_day }}</td>
                    @else
                    <td class="attendance-show-table-col-fourth spacing">{{ $pendingOrApprovedRequest->formated_month_and_day }}</td>
                    @endif
                </tr>

                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">
                        出勤・退勤
                    </th>
                    <td class="attendance-show-table-col-second">
                        @if($editable)
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="clock_in_at" class="time-input" value="{{ old('clock_in_at', $attendance->clock_in_time) }}" />
                        @else
                        <div class="time-input">{{ $pendingOrApprovedRequest->attendance->clock_in_time }}</div>
                        @endif
                    </td>
                    <td class="attendance-show-table-col-third">～</td>
                    <td class="attendance-show-table-col-fourth">
                        @if($editable)
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="clock_out_at" class="time-input" value="{{ old('clock_out_at', $attendance->clock_out_time) }}" />
                        @else
                        <div class="time-input">{{ $pendingOrApprovedRequest->attendance->clock_out_time }}</div>
                        @endif
                    </td>
                </tr>

                @if ($errors->has('clock_in_at'))
                <tr class="attendance-show-table-error">
                    <td colspan="4">
                        <div class="show-alert-danger">
                            <ul>
                                @foreach ($errors->get('clock_in_at') as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </td>
                </tr>
                @endif

                @if ($errors->has('clock_out_at'))
                <tr class="attendance-show-table-error">
                    <td colspan="4">
                        <div class="show-alert-danger">
                            <ul>
                                @foreach ($errors->get('clock_out_at') as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </td>
                </tr>
                @endif

                @if($editable)

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

                @if ($errors->has("breaks.$i.start"))
                <tr class="attendance-show-table-error">
                    <td colspan="4">
                        <div class="show-alert-danger">
                            <ul>
                                @foreach ($errors->get("breaks.$i.start") as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </td>
                </tr>
                @endif

                @if ($errors->has("breaks.$i.end"))
                <tr class="attendance-show-table-error">
                    <td colspan="4">
                        <div class="show-alert-danger">
                            <ul>
                                @foreach ($errors->get("breaks.$i.end") as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </td>
                </tr>
                @endif

                @endforeach

                @else

                @foreach($pendingOrApprovedRequest->breaks as $break)
                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">
                        @if($loop->iteration === 1)
                        休憩
                        @else
                        休憩{{ $loop->iteration}}
                        @endif
                    </th>
                    <td class="attendance-show-table-col-second">
                        <div class="time-input">{{ $break->break_start_time }}</div>
                    </td>
                    <td class="attendance-show-table-col-third">～</td>
                    <td class="attendance-show-table-col-fourth">
                        <div class="time-input">{{ $break->break_end_time }}</div>
                    </td>
                </tr>
                @endforeach

                @endif

                @if($editable)
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

                @if ($errors->has("breaks.new.start"))
                <tr class="attendance-show-table-error">
                    <td colspan="4">
                        <div class="show-alert-danger">
                            <ul>
                                @foreach ($errors->get("breaks.new.start") as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </td>
                </tr>
                @endif

                @if ($errors->has("breaks.new.end"))
                <tr class="attendance-show-table-error">
                    <td colspan="4">
                        <div class="show-alert-danger">
                            <ul>
                                @foreach ($errors->get("breaks.new.end") as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </td>
                </tr>
                @endif

                @endif

                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">備考</th>
                    <td colspan="4">
                        @if($editable)
                        <textarea name="reason" class="reason" id="">{{ old('reason') }}</textarea>
                        @else
                        <div class="reason">{{ $pendingOrApprovedRequest->reason }}</div>
                        @endif
                    </td>
                </tr>

                @if ($errors->has('reason'))
                <tr class="attendance-show-table-error">
                    <td colspan="4">
                        <div class="show-alert-danger">
                            <ul>
                                @foreach ($errors->get('reason') as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </td>
                </tr>
                @endif

            </table>
        </div>

        <div class="form-button">
            @if($editable)
            <button type="submit" name="submit" class="modify-button">修正</button>
            @else
            <span class="pending-message">*承認待ちのため修正できません</span>
            @endif
        </div>
        @csrf
    </form>
</div>
@endsection