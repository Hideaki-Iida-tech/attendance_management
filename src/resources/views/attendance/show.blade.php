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

    <form action="" class="attendance-show-form">
        <div class="attendance-show-content">

            <table class="attendance-show-table">
                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">名前</th>
                    <td class="attendance-show-table-col-second spacing">西伶奈</td>
                    <td class="attendance-show-table-col-third"></td>
                    <td class="attendance-show-table-col-fourth"></td>
                </tr>

                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">日付</th>
                    <td class="attendance-show-table-col-second spacing">2023年</td>
                    <td class="attendance-show-table-col-third"></td>
                    <td class="attendance-show-table-col-fourth spacing">6月1日</td>
                </tr>

                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">
                        出勤・退勤
                    </th>
                    <td class="attendance-show-table-col-second">
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="clock_in" class="time-input" />
                    </td>
                    <td class="attendance-show-table-col-third">～</td>
                    <td class="attendance-show-table-col-fourth">
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="clock_out" class="time-input" />
                    </td>
                </tr>

                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">
                        休憩
                    </th>
                    <td class="attendance-show-table-col-second">
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="break_start" class="time-input" />
                    </td>
                    <td class="attendance-show-table-col-third">～</td>
                    <td class="attendance-show-table-col-fourth">
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="break_end" class="time-input" />
                    </td>
                </tr>

                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">
                        休憩2
                    </th>
                    <td class="attendance-show-table-col-second">
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="break_start" class="time-input" />
                    </td>
                    <td class="attendance-show-table-col-third">～</td>
                    <td class="attendance-show-table-col-fourth">
                        <input type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" name="break_end" class="time-input" />
                    </td>
                </tr>

                <tr class="attendance-show-table-row">
                    <th class="attendance-show-table-col-title">備考</th>
                    <td colspan="3"><textarea name="remarks" class="remarks" id=""></textarea>
                    </td>
                </tr>
            </table>
        </div>
        <div class="form-button">
            <button type="submit" name="submit" class="modify-button">修正</button>
        </div>
    </form>
</div>
@endsection