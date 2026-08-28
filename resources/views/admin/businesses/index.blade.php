@extends('layouts.admin')
@section('title', 'Professionnels')
@section('content')
<h1>Comptes professionnels</h1>

<p class="muted">
    {{ $totals['accounts'] }} compte(s) professionnel(s) &middot;
    {{ $totals['unverified'] }} sans e-mail vérifié &middot;
    {{ $totals['pendingPlaces'] }} lieu(x) en attente de décision.
    Les comptes ayant un lieu en attente sont affichés en premier.
</p>

<form class="filters" method="get">
    <div class="field"><label>Recherche</label>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="nom, e-mail ou établissement">
    </div>
    <div class="field"><label>Comptes supprimés</label>
        <select name="deleted" onchange="this.form.submit()">
            <option value="">Masqués</option>
            <option value="1" @selected(request('deleted') === '1')>Afficher</option>
        </select>
    </div>
    <button class="btn btn-primary" type="submit">Filtrer</button>
</form>

<div class="table-wrap">
<table class="table">
    <thead><tr>
        <th>Établissement</th><th>Contact</th><th>Téléphone</th>
        <th>Lieux</th><th>En attente</th><th>Publiés</th>
        <th>E-mail vérifié</th><th>Inscrit</th><th>Actif</th><th></th>
    </tr></thead>
    <tbody>
    @forelse ($businesses as $business)
        <tr>
            <td>{{ $business->company_name ?: '—' }}</td>
            <td>
                {{ $business->name }}
                <div class="muted small">{{ $business->email }}</div>
            </td>
            <td class="muted small">{{ $business->phone ?: '—' }}</td>
            <td>{{ $business->places_count }}</td>
            <td>
                @if ($business->pending_places_count)
                    <span class="badge badge-warning">{{ $business->pending_places_count }}</span>
                @else
                    <span class="muted">0</span>
                @endif
            </td>
            <td>{{ $business->published_places_count }}</td>
            <td>{!! $business->email_verified_at
                ? '<span class="badge badge-success">oui</span>'
                : '<span class="badge badge-warning">non</span>' !!}</td>
            <td class="muted small nowrap">{{ $business->created_at?->format('d/m/y') }}</td>
            <td>{!! $business->trashed()
                ? '<span class="badge badge-warning">supprimé</span>'
                : ($business->is_active ? '<span class="badge badge-success">oui</span>' : '<span class="badge">non</span>') !!}</td>
            <td><a href="{{ route('admin.users.show', $business->id) }}">fiche</a></td>
        </tr>
    @empty
        <tr><td colspan="10" class="muted">Aucun compte professionnel pour ces critères.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="pagination">{{ $businesses->onEachSide(1)->links('pagination') }}</div>
@endsection
