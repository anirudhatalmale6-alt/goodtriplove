@extends('layouts.admin')
@section('title', 'Tableau de bord')
@section('content')
<h1>Tableau de bord</h1>

<div class="stat-grid">
    <div class="stat"><div class="stat__label">Vidéos publiées</div><div class="stat__value">{{ number_format($counts['videos_published'], 0, ',', ' ') }}</div></div>
    <div class="stat {{ $counts['videos_pending'] ? 'stat--warn' : '' }}"><div class="stat__label">Vidéos en attente</div><div class="stat__value">{{ $counts['videos_pending'] }}</div></div>
    <div class="stat"><div class="stat__label">Lieux publiés</div><div class="stat__value">{{ $counts['places_published'] }}</div></div>
    <div class="stat {{ $counts['places_pending'] ? 'stat--warn' : '' }}"><div class="stat__label">Lieux en attente</div><div class="stat__value">{{ $counts['places_pending'] }}</div></div>
    <div class="stat"><div class="stat__label">Pays / villes</div><div class="stat__value">{{ $counts['countries'] }} / {{ $counts['cities'] }}</div></div>
    <div class="stat"><div class="stat__label">Utilisateurs</div><div class="stat__value">{{ $counts['users'] }}</div></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h2>Quota YouTube du jour</h2>
            @if ($quota['configured'])
                <p class="muted small">{{ number_format($quota['used'], 0, ',', ' ') }} / {{ number_format($quota['limit'], 0, ',', ' ') }} unités utilisées — il reste de quoi lancer environ <strong>{{ intdiv($quota['remaining'], 100) }}</strong> recherches aujourd'hui.</p>
                <div class="quota-bar"><i style="width:{{ min(100, $quota['limit'] ? round($quota['used'] / $quota['limit'] * 100) : 0) }}%"></i></div>
            @else
                <p class="muted small">Aucune clé API YouTube configurée. Le collecteur reste en veille tant que <code>YOUTUBE_API_KEY</code> n'est pas renseignée.</p>
            @endif
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h2>Classification locale (Ollama)</h2>
            @if (! $ollama['enabled'])
                <p class="muted small">Désactivée.</p>
            @elseif ($ollama['up'])
                <p class="small"><span class="badge badge-success">En ligne</span> modèle <code>{{ $ollama['model'] }}</code></p>
            @else
                <p class="small"><span class="badge badge-warning">Injoignable</span> — le collecteur bascule sur la classification par règles, sans interruption.</p>
            @endif
        </div></div>
    </div>
</div>

<div class="card"><div class="card-body">
    <h2>Dernières collectes</h2>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Recherche</th><th>Statut</th><th>Trouvées</th><th>Nouvelles</th><th>Quota</th><th>Date</th></tr></thead>
        <tbody>
        @forelse ($recentRuns as $run)
            <tr>
                <td>{{ $run->collectorQuery?->label ?? '—' }}</td>
                <td><span class="badge {{ $run->status === 'success' ? 'badge-success' : ($run->status === 'failed' ? 'badge-danger' : 'badge-warning') }}">{{ $run->status }}</span></td>
                <td>{{ $run->items_returned }}</td>
                <td>{{ $run->items_created }}</td>
                <td>{{ $run->quota_units }}</td>
                <td class="muted small nowrap">{{ $run->created_at?->format('d/m H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">Aucune collecte pour l'instant.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div></div>

@if ($latestPending->isNotEmpty())
<div class="card"><div class="card-body">
    <h2>À valider</h2>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th></th><th>Titre</th><th>Lieu</th><th>Vues</th><th></th></tr></thead>
        <tbody>
        @foreach ($latestPending as $video)
            <tr>
                <td><img class="thumb-sm" src="{{ $video->thumbnail() }}" alt="" loading="lazy"></td>
                <td>{{ Str::limit($video->title, 70) }}</td>
                <td class="muted small">{{ collect([$video->city?->displayName(), $video->country?->displayName()])->filter()->implode(', ') ?: '—' }}</td>
                <td>{{ \App\Support\Format::compact($video->view_count) }}</td>
                <td class="nowrap">
                    <form class="inline" method="post" action="{{ route('admin.videos.approve', $video) }}">@csrf
                        <button class="btn btn-success btn-sm" type="submit">Publier</button>
                    </form>
                    <a class="btn btn-sm" href="{{ route('admin.videos.edit', $video) }}">Ouvrir</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div></div>
@endif
@endsection
