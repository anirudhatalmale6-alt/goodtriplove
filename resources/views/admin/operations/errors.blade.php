@extends('layouts.admin')
@section('title', "Centre d'erreurs")
@section('content')
<h1>Centre d'erreurs technique</h1>
<p class="muted small">Les valeurs sensibles (mots de passe, jetons, clés API) sont retirées du contexte avant enregistrement.</p>

<div class="card"><div class="card-body">
    <h3>Erreurs applicatives</h3>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Source</th><th>Gravité</th><th>Message</th><th>Occurrences</th><th>Dernière</th><th>Statut</th></tr></thead>
        <tbody>
        @forelse ($events as $event)
            <tr>
                <td class="small">{{ $event->source }}</td>
                <td><span class="badge {{ $event->severity === 'critical' ? 'badge-danger' : 'badge-warning' }}">{{ $event->severity }}</span></td>
                <td class="small" style="max-width:420px;word-break:break-word">{{ Str::limit($event->message, 160) }}</td>
                <td>{{ $event->occurrences }}</td>
                <td class="muted small nowrap">{{ $event->last_seen_at }}</td>
                <td><span class="badge">{{ $event->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">Aucune erreur enregistrée.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div></div>

<div class="card"><div class="card-body">
    <h3>Jobs en échec</h3>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>File</th><th>Échec le</th><th>Exception</th></tr></thead>
        <tbody>
        @forelse ($failedJobs as $job)
            <tr>
                <td class="small">{{ $job->queue }}</td>
                <td class="muted small nowrap">{{ $job->failed_at }}</td>
                <td class="small" style="max-width:520px;word-break:break-word">{{ Str::limit($job->exception, 200) }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">Aucun job en échec.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div></div>
@endsection
