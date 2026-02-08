@extends($layout)
@push('css')
<link rel="stylesheet" href="{{ asset('css/applications/admin/index.css') }}">
@endpush

@section('title')
申請一覧画面（管理者）
@endsection

@section('content')

<div class="applications-list">
    <div class="applications-list-title">
        <h1 class="applications-list-title-inner">申請一覧</h1>
    </div>

    <div class="applications-tab">
        <a href="/stamp_correction_request/list/?tab={{ App\Enums\ApplicationStatus::PENDING->name }}" class="applications-tab-pending {{ $status === App\Enums\ApplicationStatus::PENDING->value ? 'active' : '' }}">承認待ち</a>
        <a href="/stamp_correction_request/list/?tab={{ App\Enums\ApplicationStatus::APPROVED->name }}" class="applications-tab-approved {{ $status === App\Enums\ApplicationStatus::APPROVED->value ? 'active' : '' }}">承認済み</a>
    </div>

    <hr class="applications-separator">

    <div class="applications-list-content">
        <table class="applications-list-table">
            <tr class="applications-list-table-title">
                <th class="list-state">状態</th>
                <th class="list-name">名前</th>
                <th class="list-target-date">対象日時</th>
                <th class="list-reason">申請理由</th>
                <th class="list-request-date">申請日時</th>
                <th class="list-detail">詳細</th>
            </tr>
            @foreach($attendanceChangeRequests as $request)
            <tr class="applications-list-table-row">
                @if($status === App\Enums\ApplicationStatus::PENDING->value)
                <td class="list-state-content">承認待ち</td>
                @elseif($status === App\Enums\ApplicationStatus::APPROVED->value)
                <td class="list-state-content">承認済み</td>
                @endif
                <td>{{ $request->user?->name }}</td>
                <td>{{ $request->work_date?->format('Y/m/d') }}</td>
                <td>
                    <div class="reason-scroll">{{ $request->reason }}</div>
                </td>
                <td>{{ $request->created_at?->format('Y/m/d') }}</td>
                <td><a href="/stamp_correction_request/approve/{{ $request->id }}" class="detail-link">詳細</a></td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection