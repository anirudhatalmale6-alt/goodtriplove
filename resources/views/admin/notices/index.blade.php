@extends('layouts.admin')
@section('title', 'Signalements')
@section('content')
<h1>Signalements</h1>
<p class="muted small">Cycle : reçu → tri → examen → décision → notification. Chaque étape est journalisée.</p>

<form class="filters" method="get">
    <div class="field"><label>Statut</label>
        <select name="status" onchange="this.form.submit()">
            <option value="">Tous ({{ $statusCounts->sum() }})</option>
            @foreach (['received' => 'Reçus', 'triage' => 'En tri', 'under_review' => 'En examen', 'closed' => 'Clos'] as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }} ({{ $statusCounts[$key] ?? 0 }})</option>
            @endforeach
        </select>
    </div>
</form>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>#</th><th>Cible</th><th>Motif</th><th>Statut</th><th>Décision</th><th>Notifié</th><th>Reçu</th><th></th></tr></thead>
    <tbody>
    @forelse ($notices as $notice)
        <tr>
            <td>{{ $notice->id }}</td>
            <td class="small">{{ $notice->target_type }} #{{ $notice->target_id ?? '—' }}
                @if ($notice->target_url)<div class="muted small" style="max-width:280px;word-break:break-all">{{ $notice->target_url }}</div>@endif
            </td>
            <td class="muted small">{{ $notice->reason }}</td>
            <td><span class="badge {{ $notice->status === 'closed' ? 'badge-success' : 'badge-warning' }}">{{ $notice->status }}</span></td>
            <td class="muted small">{{ $notice->decision ?? '—' }}</td>
            <td>{!! $notice->notified_at ? '<span class="badge badge-success">oui</span>' : '<span class="badge">non</span>' !!}</td>
            <td class="muted small nowrap">{{ $notice->created_at?->format('d/m H:i') }}</td>
            <td><a class="btn btn-sm" href="{{ route('admin.notices.show', ['notice' => $notice->id]) }}">Ouvrir</a></td>
        </tr>
    @empty
        <tr><td colspan="8" class="muted">Aucun signalement.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="pagination">{{ $notices->onEachSide(1)->links('pagination') }}</div>
@endsection
