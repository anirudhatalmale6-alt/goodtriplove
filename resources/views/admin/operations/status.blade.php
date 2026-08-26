@extends('layouts.admin')
@section('title', 'État des services')
@section('content')
<h1>État des services</h1>

<div class="stat-grid">
    @foreach ($checks as $check)
        <div class="stat {{ $check['status'] === 'ok' ? 'stat--ok' : 'stat--warn' }}">
            <div class="stat__label">{{ $check['name'] }}</div>
            <div class="stat__value" style="font-size:16px">
                <span class="badge {{ $check['status'] === 'ok' ? 'badge-success' : ($check['status'] === 'down' ? 'badge-danger' : 'badge-warning') }}">{{ $check['status'] }}</span>
            </div>
            <div class="muted small" style="margin-top:6px;word-break:break-word">{{ $check['detail'] }}</div>
        </div>
    @endforeach
</div>

@if ($lastHealth->isNotEmpty())
<div class="card"><div class="card-body">
    <h3>Derniers relevés automatiques</h3>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Service</th><th>Statut</th><th>Message</th><th>Date</th></tr></thead>
        <tbody>
        @foreach ($lastHealth as $row)
            <tr>
                <td>{{ $row->service ?? '—' }}</td>
                <td><span class="badge {{ ($row->status ?? '') === 'ok' ? 'badge-success' : 'badge-warning' }}">{{ $row->status ?? '—' }}</span></td>
                <td class="muted small">{{ Str::limit($row->message ?? '', 80) }}</td>
                <td class="muted small nowrap">{{ $row->created_at ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div></div>
@endif
@endsection
