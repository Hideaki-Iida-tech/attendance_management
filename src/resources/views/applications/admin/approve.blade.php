@extends($layout)
@push('css')
<link rel="stylesheet" href="{{ asset('css/applications/admin/approve.css') }}">
@endpush

@section('title')
修正申請承認画面（管理者）
@endsection

@section('content')

<div class="approve">
    <div class="approve-title">
        <h1 class="approve-title-inner">勤怠詳細</h1>
    </div>

    <form action="/stamp_correction_request/approve/{{ $attendanceChangeRequest->id }}" class="approve-form" method="post">
        <div class="approve-content">

            <table class="approve-table">
                <tr class="approve-table-row">
                    <th class="approve-table-col-title">名前</th>
                    <td colspan="3" class="approve-table-col-second spacing">{{ $attendanceChangeRequest->user->name }}</td>
                </tr>

                <tr class="approve-table-row">
                    <th class="approve-table-col-title">日付</th>
                    <td class="approve-table-col-second spacing">{{ $attendanceChangeRequest->formated_year }}</td>
                    <td class="approve-table-col-third"></td>
                    <td class="approve-table-col-fourth spacing">{{ $attendanceChangeRequest->formated_month_and_day }}</td>
                </tr>

                <tr class="approve-table-row">
                    <th class="approve-table-col-title">
                        出勤・退勤
                    </th>
                    <td class="approve-table-col-second">
                        <div class="time-value">
                            {{ $attendanceChangeRequest->attendance?->clock_in_time }}
                        </div>
                    </td>
                    <td class="approve-table-col-third">～</td>
                    <td class="approve-table-col-fourth">
                        <div class="time-value">
                            {{ $attendanceChangeRequest->attendance?->clock_out_time }}
                        </div>
                    </td>
                </tr>

                @foreach($attendanceChangeRequest->breaks as $break)
                <tr class="approve-table-row">
                    <th class="approve-table-col-title">
                        @if($loop->iteration === 1)
                        休憩
                        @else
                        休憩{{ $loop->iteration }}
                        @endif
                    </th>
                    <td class="approve-table-col-second">
                        <div class="time-value">
                            {{ $break?->break_start_time }}
                        </div>
                    </td>
                    <td class="approve-table-col-third">～</td>
                    <td class="approve-table-col-fourth">
                        <div class="time-value">
                            {{ $break?->break_end_time }}
                        </div>
                    </td>
                </tr>
                @endforeach

                <tr class="approve-table-row">
                    <th class="approve-table-col-title">備考</th>
                    <td colspan="4">
                        <div class="remarks">{{ $attendanceChangeRequest->reason}}</div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="form-button">
            @if($isApproved)
            <button type="submit" name="submit" class="already-approved-button">承認済み</button>
            @else
            <button type="submit" name="submit" class="approve-button">承認</button>
            @endif
        </div>
        @csrf
    </form>
</div>
@endsection