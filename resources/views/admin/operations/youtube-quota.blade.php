@extends('layouts.admin')
@section('title', 'Quota YouTube')
@section('content')
<h1>Quota YouTube</h1>

@unless ($configured)
    <div class="alert alert--err">Aucune clé API YouTube configurée. Le collecteur reste en veille.</div>
@endunless

<div class="stat-grid">
    <div class="stat"><div class="stat__label">Limite quotidienne</div><div class="stat__value">{{ number_format($limit, 0, ',', ' ') }}</div></div>
    <div class="stat"><div class="stat__label">Utilisé aujourd'hui</div><div class="stat__value">{{ number_format($used, 0, ',', ' ') }}</div></div>
    <div class="stat"><div class="stat__label">Restant (avant arrêt)</div><div class="stat__value">{{ number_format($remaining, 0, ',', ' ') }}</div></div>
    <div class="stat {{ $percent >= $warningPercent ? 'stat--warn' : 'stat--ok' }}"><div class="stat__label">Pourcentage utilisé</div><div class="stat__value">{{ $percent }} %</div></div>
    <div class="stat"><div class="stat__label">Recherches encore possibles</div><div class="stat__value">{{ intdiv($remaining, max(1, $searchCost)) }}</div></div>
</div>

<div class="card"><div class="card-body">
    <div class="quota-bar"><i style="width:{{ min(100, $percent) }}%"></i></div>
    <p class="small muted mt">
        Seuil d'alerte : {{ $warningPercent }} % · arrêt du collecteur : {{ $hardStopPercent }} % ·
        coût d'une recherche : {{ $searchCost }} unités ·
        dernière requête : {{ $lastRequestAt ?? 'aucune aujourd’hui' }}
    </p>
    <p class="small muted">
        Le collecteur s'arrête au seuil d'arrêt, pas à la limite brute : cela laisse une marge pour
        les rafraîchissements de métriques et évite un refus de l'API pour le reste de la journée.
    </p>
</div></div>

@if ($history->isNotEmpty())
<div class="card"><div class="card-body">
    <h3>14 derniers jours</h3>
    <table class="table">
        <thead><tr><th>Date</th><th>Unités</th><th>%</th><th>Dernière requête</th></tr></thead>
        <tbody>
        @foreach ($history as $row)
            <tr>
                <td>{{ $row->usage_date }}</td>
                <td>{{ number_format($row->units_used, 0, ',', ' ') }}</td>
                <td>{{ $limit > 0 ? round($row->units_used / $limit * 100, 1) : 0 }} %</td>
                <td class="muted small">{{ $row->last_request_at ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endif
@endsection
