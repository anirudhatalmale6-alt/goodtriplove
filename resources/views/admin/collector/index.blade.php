@extends('layouts.admin')
@section('title', 'Collecteur vidéo')
@section('content')
<h1>Collecteur vidéo</h1>

<div class="card"><div class="card-body">
    <h3>Quota YouTube aujourd'hui</h3>
    @if ($quota['configured'])
        <p class="small">{{ number_format($quota['used'], 0, ',', ' ') }} / {{ number_format($quota['limit'], 0, ',', ' ') }} unités.
        Une recherche coûte {{ $quota['search_cost'] }} unités : il reste environ <strong>{{ intdiv($quota['remaining'], max(1, $quota['search_cost'])) }}</strong> recherches possibles aujourd'hui.</p>
        <div class="quota-bar"><i style="width:{{ min(100, $quota['limit'] ? round($quota['used'] / $quota['limit'] * 100) : 0) }}%"></i></div>
    @else
        <p class="muted small">Clé API YouTube absente : le collecteur ne lance aucune recherche.</p>
    @endif
</div></div>

<div class="row">
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h3>Nouvelle recherche</h3>
            <form method="post" action="{{ route('admin.collector.store') }}">
                @csrf
                <div class="field"><label>Libellé</label><input name="label" required></div>
                <div class="field"><label>Requête YouTube</label><input name="query" required placeholder="meilleurs restaurants Porto"></div>
                <div class="grid-3">
                    <div class="field"><label>Pays</label>
                        <select name="country_id"><option value="">—</option>
                            @foreach ($countries as $country)<option value="{{ $country->id }}">{{ $country->displayName() }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Catégorie</label>
                        <select name="category_id"><option value="">—</option>
                            @foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->displayName() }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Langue</label>
                        <select name="locale"><option value="">—</option>
                            @foreach (['fr','pt','es','it','de','en'] as $locale)<option value="{{ $locale }}">{{ strtoupper($locale) }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="grid-3">
                    <div class="field"><label>Résultats max</label><input name="max_results" type="number" value="25" min="5" max="50"></div>
                    <div class="field"><label>Priorité</label><input name="priority" type="number" value="100"></div>
                    <div class="field"><label>Intervalle (h)</label><input name="interval_hours" type="number" value="168"></div>
                </div>
                <button class="btn btn-primary" type="submit">Créer</button>
            </form>
        </div></div>
    </div>

    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h3>Générer un jeu de recherches</h3>
            <p class="muted small">Crée automatiquement une recherche par ville populaire et par catégorie pour un pays.</p>
            <form method="post" action="{{ route('admin.collector.generate') }}">
                @csrf
                <div class="grid-3">
                    <div class="field"><label>Pays</label>
                        <select name="country_id" required>
                            @foreach ($countries as $country)<option value="{{ $country->id }}">{{ $country->displayName() }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Langue</label>
                        <select name="locale" required>
                            @foreach (['fr','pt','es','it','de','en'] as $locale)<option value="{{ $locale }}">{{ strtoupper($locale) }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Villes max</label><input name="limit_cities" type="number" value="5" min="1" max="50"></div>
                </div>
                <button class="btn btn-primary" type="submit">Générer</button>
            </form>
        </div></div>
    </div>
</div>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Libellé</th><th>Requête</th><th>Contexte</th><th>Prio</th><th>Intervalle</th><th>Dernier run</th><th>Importées</th><th>Active</th><th></th></tr></thead>
    <tbody>
    @forelse ($queries as $query)
        <tr>
            <form method="post" action="{{ route('admin.collector.update', $query) }}">
                @csrf @method('PUT')
                <td><input name="label" value="{{ $query->label }}" style="width:160px"></td>
                <td><input name="query" value="{{ $query->query }}" style="width:200px"></td>
                <td class="muted small">{{ collect([$query->city?->displayName(), $query->country?->displayName(), $query->category?->displayName()])->filter()->implode(' · ') ?: '—' }}</td>
                <td><input name="priority" type="number" value="{{ $query->priority }}" style="width:64px"></td>
                <td><input name="interval_hours" type="number" value="{{ $query->interval_hours }}" style="width:70px"></td>
                <td class="muted small nowrap">{{ $query->last_run_at?->format('d/m H:i') ?? 'jamais' }}</td>
                <td>{{ $query->videos_imported }}</td>
                <td><input type="checkbox" name="is_active" value="1" @checked($query->is_active) style="width:auto"></td>
                <td class="nowrap"><button class="btn btn-sm btn-primary" type="submit">OK</button></td>
            </form>
            <td class="nowrap">
                <form class="inline" method="post" action="{{ route('admin.collector.run', $query) }}">@csrf
                    <button class="btn btn-sm" type="submit" @disabled(! $quota['configured'])>Lancer</button>
                </form>
                <form class="inline" method="post" action="{{ route('admin.collector.destroy', $query) }}">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger" type="submit">✕</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="10" class="muted">Aucune recherche enregistrée.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="pagination">{{ $queries->onEachSide(1)->links('pagination') }}</div>

<div class="card mt"><div class="card-body">
    <h3>Derniers passages</h3>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Recherche</th><th>Statut</th><th>Trouvées</th><th>Nouvelles</th><th>MAJ</th><th>Quota</th><th>Message</th><th>Date</th></tr></thead>
        <tbody>
        @foreach ($runs as $run)
            <tr>
                <td>{{ $run->collectorQuery?->label ?? '—' }}</td>
                <td><span class="badge {{ $run->status === 'success' ? 'badge-success' : ($run->status === 'failed' ? 'badge-danger' : 'badge-warning') }}">{{ $run->status }}</span></td>
                <td>{{ $run->items_returned }}</td>
                <td>{{ $run->items_created }}</td>
                <td>{{ $run->items_updated }}</td>
                <td>{{ $run->quota_units }}</td>
                <td class="muted small">{{ Str::limit($run->message, 60) }}</td>
                <td class="muted small nowrap">{{ $run->created_at?->format('d/m H:i') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div></div>
@endsection
