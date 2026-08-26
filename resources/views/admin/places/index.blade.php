@extends('layouts.admin')
@section('title', 'Lieux')
@section('content')
<div class="topbar">
    <h1 style="margin:0">Lieux</h1>
    <div class="spacer"></div>
    <a class="btn btn-primary" href="{{ route('admin.places.create') }}">+ Nouveau lieu</a>
</div>

<form class="filters" method="get">
    <div class="field"><label>Statut</label>
        <select name="status" onchange="this.form.submit()">
            <option value="">Tous</option>
            @foreach (['pending' => 'En attente', 'published' => 'Publiés', 'rejected' => 'Refusés', 'draft' => 'Brouillons'] as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }} ({{ $statusCounts[$key] ?? 0 }})</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label>Recherche</label><input type="search" name="q" value="{{ request('q') }}"></div>
    <button class="btn btn-primary" type="submit">Filtrer</button>
</form>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Nom</th><th>Ville</th><th>Catégorie</th><th>Propriétaire</th><th>Vidéos</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse ($places as $place)
        <tr>
            <td>{{ $place->name }}</td>
            <td class="muted small">{{ $place->city?->displayName() }}</td>
            <td class="muted small">{{ $place->category?->displayName() }}</td>
            <td class="muted small">{{ $place->owner?->email ?? '—' }}</td>
            <td>{{ $place->videos_count }}</td>
            <td><span class="badge {{ $place->status === 'published' ? 'badge-success' : ($place->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ $place->status }}</span></td>
            <td class="nowrap">
                @if ($place->status !== 'published')
                    <form class="inline" method="post" action="{{ route('admin.places.approve', $place) }}">@csrf
                        <button class="btn btn-success btn-sm" type="submit">Publier</button>
                    </form>
                @endif
                <a class="btn btn-sm" href="{{ route('admin.places.edit', $place) }}">Ouvrir</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="muted">Aucun lieu.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="pagination">{{ $places->onEachSide(1)->links('pagination') }}</div>
@endsection
