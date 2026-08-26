@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Data Quality</h1>

    <table class="table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Issue</th>
            <th>Entity</th>
            <th>Severity</th>
            <th>Message</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($issues as $issue)
            <tr>
                <td>{{ $issue->created_at }}</td>
                <td>{{ $issue->issue_type }}</td>
                <td>{{ $issue->entity_type }} #{{ $issue->entity_id }}</td>
                <td>{{ $issue->severity }}</td>
                <td>{{ $issue->message }}</td>
                <td>{{ $issue->status }}</td>
                <td>
                    @if($issue->status !== 'resolved')
                    <form method="POST" action="{{ route('admin.data-quality.resolve',$issue) }}">
                        @csrf
                        <button class="btn btn-sm btn-primary">Resolve</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $issues->links() }}
</div>
@endsection
