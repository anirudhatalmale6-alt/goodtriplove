@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Moderation Queue</h1>

    <table class="table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Entity</th>
            <th>Reason</th>
            <th>Priority</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($items as $item)
            <tr>
                <td>{{ $item->created_at }}</td>
                <td>{{ $item->entity_type }} #{{ $item->entity_id }}</td>
                <td>{{ $item->reason }}</td>
                <td>{{ $item->priority }}</td>
                <td>{{ $item->status }}</td>
                <td>
                    @if($item->status !== 'resolved')
                    <form method="POST" action="{{ route('admin.moderation.resolve',$item) }}">
                        @csrf
                        <input name="notes" placeholder="Notes">
                        <button class="btn btn-sm btn-primary">Resolve</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $items->links() }}
</div>
@endsection
