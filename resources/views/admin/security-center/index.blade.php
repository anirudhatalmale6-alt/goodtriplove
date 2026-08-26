@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Security Center</h1>

    <div class="row">
        @foreach($health as $service => $rows)
            @php($last = $rows->first())
            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-body">
                        <strong>{{ strtoupper($service) }}</strong>
                        <div>Status: {{ $last->status }}</div>
                        <small>{{ $last->message }}</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h3>Summary</h3>
            <p>Active devices: {{ $devices }}</p>
            <p>Pending reports: {{ $reportsPending }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3>Latest Audit Events</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>IP</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($audit as $entry)
                    <tr>
                        <td>{{ $entry->created_at }}</td>
                        <td>{{ $entry->actor_user_id ?? 'Guest' }}</td>
                        <td>{{ $entry->action }}</td>
                        <td>{{ $entry->auditable_type }} #{{ $entry->auditable_id }}</td>
                        <td>{{ $entry->ip_address }}</td>
                        <td>{{ $entry->success ? 'OK' : 'FAILED' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
