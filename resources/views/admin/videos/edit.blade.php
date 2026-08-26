@extends('layouts.admin')
@section('title', 'Vidéo')
@section('content')
<div class="topbar">
    <h1 style="margin:0">{{ Str::limit($video->title, 70) }}</h1>
    <div class="spacer"></div>
    <a class="btn btn-sm" href="{{ route('admin.videos.index') }}">← Retour</a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <img src="{{ $video->thumbnail() }}" alt="" style="width:100%;border-radius:10px">
            <p class="muted small mt">
                {{ $video->channel_title }} · {{ \App\Support\Format::compact($video->view_count) }} vues ·
                {{ \App\Support\Format::compact($video->like_count) }} likes · {{ $video->durationForHumans() }}
            </p>
            <p class="small">
                Popularité {{ number_format($video->popularity_score, 3) }} ·
                Tendance {{ number_format($video->trending_score, 3) }} ·
                Pertinence {{ number_format($video->relevance_score, 3) }}
            </p>
            <p class="small muted">
                Classification : {{ $video->classified_by ?? '—' }}
                @if ($video->classification_confidence) (confiance {{ number_format($video->classification_confidence * 100, 0) }} %) @endif
            </p>
            <a class="btn btn-sm" href="{{ $video->watchUrl() }}" target="_blank" rel="noopener">↗ Voir sur YouTube</a>
        </div></div>

        <div class="card"><div class="card-body">
            <h3>Lieux associés</h3>
            @forelse ($video->places as $place)
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span>{{ $place->name }}</span>
                    <span class="badge">{{ number_format($place->pivot->match_score, 2) }} · {{ $place->pivot->match_reason }}</span>
                    @if ($place->pivot->confirmed)<span class="badge badge-success">confirmé</span>@endif
                    <form class="inline" method="post" action="{{ route('admin.videos.places.detach', [$video, $place]) }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">Retirer</button>
                    </form>
                </div>
            @empty
                <p class="muted small">Aucun lieu associé.</p>
            @endforelse

            <form method="post" action="{{ route('admin.videos.places.attach', $video) }}" class="mt">
                @csrf
                <div class="field"><label>Associer un lieu</label>
                    <select name="place_id" required>
                        <option value="">—</option>
                        @foreach ($places as $place)
                            <option value="{{ $place->id }}">{{ $place->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary btn-sm" type="submit">Associer</button>
            </form>
        </div></div>
    </div>

    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <form method="post" action="{{ route('admin.videos.update', $video) }}">
                @csrf @method('PUT')
                <div class="field"><label>Titre</label><input name="title" value="{{ old('title', $video->title) }}" required></div>
                <div class="grid-2">
                    <div class="field"><label>Pays</label>
                        <select name="country_id">
                            <option value="">—</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" @selected($video->country_id === $country->id)>{{ $country->displayName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Ville</label>
                        <select name="city_id">
                            <option value="">—</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}" @selected($video->city_id === $city->id)>{{ $city->displayName() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="field"><label>Catégorie</label>
                        <select name="category_id">
                            <option value="">—</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($video->category_id === $category->id)>{{ $category->displayName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Langue</label><input name="language" value="{{ $video->language }}" maxlength="8"></div>
                </div>
                <div class="field">
                    <label><input type="checkbox" name="is_featured" value="1" @checked($video->is_featured) style="width:auto"> Mettre en avant</label>
                    <label><input type="checkbox" name="is_tv_eligible" value="1" @checked($video->is_tv_eligible) style="width:auto"> Éligible GoodTripLove TV</label>
                </div>
                <button class="btn btn-primary" type="submit">Enregistrer</button>
            </form>

            <div class="mt" style="display:flex;gap:8px">
                <form method="post" action="{{ route('admin.videos.approve', $video) }}">@csrf
                    <button class="btn btn-success" type="submit">Publier</button>
                </form>
                <form method="post" action="{{ route('admin.videos.reject', $video) }}" style="display:flex;gap:8px">@csrf
                    <input name="reason" placeholder="Motif du refus" style="width:210px">
                    <button class="btn btn-danger" type="submit">Refuser</button>
                </form>
            </div>
        </div></div>
    </div>
</div>
@endsection
