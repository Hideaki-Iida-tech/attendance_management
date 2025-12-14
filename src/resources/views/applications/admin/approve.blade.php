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

    <form action="" class="approve-form">
        <div class="approve-content">

            <table class="approve-table">
                <tr class="approve-table-row">
                    <th class="approve-table-col-title">名前</th>
                    <td class="approve-table-col-second spacing">西 伶奈</td>
                    <td class="approve-table-col-third"></td>
                    <td class="approve-table-col-fourth"></td>
                </tr>

                <tr class="approve-table-row">
                    <th class="approve-table-col-title">日付</th>
                    <td class="approve-table-col-second spacing">2023年</td>
                    <td class="approve-table-col-third"></td>
                    <td class="approve-table-col-fourth spacing">6月1日</td>
                </tr>

                <tr class="approve-table-row">
                    <th class="approve-table-col-title">
                        出勤・退勤
                    </th>
                    <td class="approve-table-col-second">
                        <div class="time-value">clock_in</div>
                    </td>
                    <td class="approve-table-col-third">～</td>
                    <td class="approve-table-col-fourth">
                        <div class="time-value">
                            clock_out
                        </div>
                    </td>
                </tr>

                <tr class="approve-table-row">
                    <th class="approve-table-col-title">
                        休憩
                    </th>
                    <td class="approve-table-col-second">
                        <div class="time-value">
                            break_start
                        </div>
                    </td>
                    <td class="approve-table-col-third">～</td>
                    <td class="approve-table-col-fourth">
                        <div class="time-value">
                            break_end
                        </div>
                    </td>
                </tr>

                <tr class="approve-table-row">
                    <th class="approve-table-col-title">
                        休憩2
                    </th>
                    <td class="approve-table-col-second">
                        <div class="time-value">
                            break_start
                        </div>
                    </td>
                    <td class="approve-table-col-third">～</td>
                    <td class="approve-table-col-fourth">
                        <div class="time-value">
                            break_end
                        </div>
                    </td>
                </tr>

                <tr class="approve-table-row">
                    <th class="approve-table-col-title">備考</th>
                    <td colspan="3">
                        <div class="remarks">remarks</div>
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
    </form>
</div>
@endsection