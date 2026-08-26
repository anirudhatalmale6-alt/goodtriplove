@extends('layouts.admin')
@section('title', 'Vidéos')
@section('content')
<h1>Vidéos</h1>

<form class="filters" method="get">
    <div class="field"><label>Statut</label>
        <select name="status" onchange="this.form.submit()">
            <option value="">Tous ({{ $statusCounts->sum() }})</option>
            @foreach (['pending' => 'En attente', 'approved' => 'Publiées', 'rejected' => 'Refusées'] as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }} ({{ $statusCounts[$key] ?? 0 }})</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label>Pays</label>
        <select name="country" onchange="this.form.submit()">
            <option value="">Tous</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" @selected(request('country') == $country->id)>{{ $country->displayName() }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label>Catégorie</label>
        <select name="category" onchange="this.form.submit()">
            <option value="">Toutes</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->displayName() }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label>Recherche</label><input type="search" name="q" value="{{ request('q') }}"></div>
    <div class="field"><label>Sans lieu associé</label>
        <select name="unlinked" onchange="this.form.submit()">
            <option value="">Non</option>
            <option value="1" @selected(request('unlinked'))>Oui</option>
        </select>
    </div>
    <button class="btn btn-primary" type="submit">Filtrer</button>
</form>

<form method="post" action="{{ route('admin.videos.bulk') }}">@csrf
    <div class="filters">
        <select name="action" style="width:auto">
            <option value="approve">Publier la sélection</option>
            <option value="reject">Refuser la sélection</option>
            <option value="feature">Mettre en avant</option>
            <option value="unfeature">Retirer la mise en avant</option>
            <option value="rescore">Recalculer les scores</option>
        </select>
        <button class="btn btn-primary" type="submit">Appliquer</button>
    </div>

    <div class="table-wrap">
    <table class="table">
        <thead><tr>
            <th style="width:28px"></th><th></th><th>Titre</th><th>Lieu</th><th>Catégorie</th>
            <th>Vues</th><th>Popularité</th><th>Statut</th><th></th>
        </tr></thead>
        <tbody>
        @forelse ($videos as $video)
            <tr>
                <td><input type="checkbox" name="ids[]" value="{{ $video->id }}" style="width:auto"></td>
                <td><img class="thumb-sm" src="{{ $video->thumbnail() }}" alt="" loading="lazy"></td>
                <td>
                    {{ Str::limit($video->title, 64) }}
                    <div class="muted small">{{ $video->channel_title }}</div>
                    @if ($video->places->isNotEmpty())
                        <div class="small">📍 {{ $video->places->pluck('name')->implode(', ') }}</div>
                    @endif
                </td>
                <td class="muted small">{{ collect([$video->city?->displayName(), $video->country?->displayName()])->filter()->implode(', ') ?: '—' }}</td>
                <td class="muted small">{{ $video->category?->displayName() ?? '—' }}</td>
                <td>{{ \App\Support\Format::compact($video->view_count) }}</td>
                <td>{{ number_format($video->popularity_score, 2) }}</td>
                <td>
                    <span class="badge {{ $video->status === 'approved' ? 'badge-success' : ($video->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ $video->status }}</span>
                    @unless ($video->is_available)<span class="badge badge-danger">indispo</span>@endunless
                    @if ($video->is_featured)<span class="badge">★</span>@endif
                </td>
                <td class="nowrap"><a class="btn btn-sm" href="{{ route('admin.videos.edit', $video) }}">Ouvrir</a></td>
            </tr>
        @empty
            <tr><td colspan="9" class="muted">Aucune vidéo.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</form>

<div class="pagination">{{ $videos->onEachSide(1)->links('pagination') }}</div>
@endsection
