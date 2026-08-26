@extends('layouts.admin')
@section('title', 'Publicités & annonces')
@section('content')
<h1>Régie publicitaire</h1>

<div class="card"><div class="card-body">
    <h3>Nouvel espace publicitaire</h3>
    <form method="post" action="{{ route('admin.ads.store') }}">
        @csrf
        <div class="grid-3">
            <div class="field"><label>Nom interne</label><input name="name" required></div>
            <div class="field"><label>Type</label>
                <select name="type"><option value="banner">Bannière</option><option value="promo">Promotion temporaire</option><option value="sponsor">Sponsor</option></select>
            </div>
            <div class="field"><label>Emplacement</label>
                <select name="placement">
                    @foreach ($placements as $placement)<option value="{{ $placement }}">{{ $placement }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="grid-3">
            <div class="field"><label>Titre (FR)</label><input name="title[fr]"></div>
            <div class="field"><label>Sous-titre (FR)</label><input name="subtitle[fr]"></div>
            <div class="field"><label>Bouton (FR)</label><input name="cta_label[fr]"></div>
        </div>
        <div class="grid-3">
            <div class="field"><label>Image (URL)</label><input name="image"></div>
            <div class="field"><label>Lien</label><input name="target_url" type="url"></div>
            <div class="field"><label>Ordre</label><input name="sort_order" type="number" value="0"></div>
        </div>
        <div class="grid-3">
            <div class="field"><label>Début</label><input name="starts_at" type="datetime-local"></div>
            <div class="field"><label>Fin</label><input name="ends_at" type="datetime-local"></div>
            <div class="field"><label>Pays (optionnel)</label>
                <select name="country_id"><option value="">Tous</option>
                    @foreach ($countries as $country)<option value="{{ $country->id }}">{{ $country->displayName() }}</option>@endforeach
                </select>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Créer</button>
    </form>
</div></div>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Nom</th><th>Emplacement</th><th>Période</th><th>Impressions</th><th>Clics</th><th>Active</th><th></th></tr></thead>
    <tbody>
    @forelse ($ads as $ad)
        <tr>
            <td>{{ $ad->name }}<div class="muted small">{{ $ad->translate('title') }}</div></td>
            <td class="muted small">{{ $ad->placement }}</td>
            <td class="muted small nowrap">{{ $ad->starts_at?->format('d/m/y') ?? '—' }} → {{ $ad->ends_at?->format('d/m/y') ?? '—' }}</td>
            <td>{{ $ad->impressions }}</td>
            <td>{{ $ad->clicks }}</td>
            <td><span class="badge {{ $ad->is_active ? 'badge-success' : '' }}">{{ $ad->is_active ? 'oui' : 'non' }}</span></td>
            <td class="nowrap">
                <form class="inline" method="post" action="{{ route('admin.ads.destroy', $ad) }}">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger" type="submit">Supprimer</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="muted">Aucun espace publicitaire.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<h1 class="mt">Bandeau d'annonces défilant</h1>

<div class="card"><div class="card-body">
    <form method="post" action="{{ route('admin.announcements.store') }}">
        @csrf
        <div class="grid-3">
            @foreach (['fr', 'pt', 'es', 'it', 'de', 'en'] as $locale)
                <div class="field"><label>Texte {{ strtoupper($locale) }}</label><input name="text[{{ $locale }}]" maxlength="250"></div>
            @endforeach
        </div>
        <div class="grid-3">
            <div class="field"><label>Emoji</label><input name="emoji" maxlength="16"></div>
            <div class="field"><label>Lien</label><input name="url" type="url"></div>
            <div class="field"><label>Ordre</label><input name="sort_order" type="number" value="0"></div>
        </div>
        <div class="grid-2">
            <div class="field"><label>Début</label><input name="starts_at" type="datetime-local"></div>
            <div class="field"><label>Fin</label><input name="ends_at" type="datetime-local"></div>
        </div>
        <button class="btn btn-primary" type="submit">Ajouter</button>
    </form>
</div></div>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Texte (FR)</th><th>Période</th><th>Active</th><th></th></tr></thead>
    <tbody>
    @forelse ($announcements as $announcement)
        <tr>
            <form method="post" action="{{ route('admin.announcements.update', $announcement) }}">
                @csrf @method('PUT')
                <td>
                    <input name="text[fr]" value="{{ data_get($announcement->text, 'fr') }}" style="width:100%">
                    @foreach (['pt', 'es', 'it', 'de', 'en'] as $locale)
                        <input type="hidden" name="text[{{ $locale }}]" value="{{ data_get($announcement->text, $locale) }}">
                    @endforeach
                </td>
                <td class="muted small nowrap">{{ $announcement->starts_at?->format('d/m/y') ?? '—' }} → {{ $announcement->ends_at?->format('d/m/y') ?? '—' }}</td>
                <td><input type="checkbox" name="is_active" value="1" @checked($announcement->is_active) style="width:auto"></td>
                <td class="nowrap"><button class="btn btn-sm btn-primary" type="submit">OK</button></td>
            </form>
            <td>
                <form class="inline" method="post" action="{{ route('admin.announcements.destroy', $announcement) }}">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger" type="submit">✕</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted">Aucune annonce.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
