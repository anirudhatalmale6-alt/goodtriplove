@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Growth & Operations</h1>

    <div class="row">
        <div class="col-md-3"><div class="card"><div class="card-body">
            <strong>Data Quality</strong>
            <div>{{ $qualityOpen }} open issues</div>
        </div></div></div>

        <div class="col-md-3"><div class="card"><div class="card-body">
            <strong>Moderation</strong>
            <div>{{ $moderationPending }} pending</div>
        </div></div></div>
    </div>

    <hr>

    <h3>Service Health</h3>
    <div class="row">
        @foreach($health as $service => $rows)
            @php($last = $rows->first())
            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-body">
                        <strong>{{ strtoupper($service) }}</strong>
                        <div>{{ $last->status }}</div>
                        <small>{{ $last->message }}</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
