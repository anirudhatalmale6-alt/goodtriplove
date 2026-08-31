@extends('layouts.admin')

@php
    use App\Support\SocialPlatform;
    $label = SocialPlatform::label($platform);
    $colour = SocialPlatform::colour($platform);
    $metadata = SocialPlatform::metadataLevel($platform);
@endphp

@section('title', $label.' — vidéos sociales')

@section('content')
<div>
    <div>
        <h1><span class="dot" style="background:{{ $colour }}"></span> {{ $label }} — vidéos sociales</h1>
        <p class="muted">Importer, vérifier et gérer les vidéos {{ $label }} depuis l'administration GoodTripLove.</p>
    </div>
</div>

@if (session('status'))
    <div class="alert alert--ok">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="alert alert--err">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert--err">
        @foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach
    </div>
@endif

@unless ($enabled)
    <div class="alert alert--err">
        L'import {{ $label }} est désactivé. Les vidéos déjà publiées restent en ligne, mais aucune nouvelle ne peut être ajoutée.
        <a href="{{ route('admin.system.index') }}">Réactiver dans Clés &amp; sécurité</a>.
    </div>
@endunless

<div class="stat-grid">
    <div class="stat">
        <div class="stat__label">Total</div>
        <div class="stat__value">{{ $counts['total'] }}</div>
        <div class="stat__hint">vidéos {{ $label }} enregistrées</div>
    </div>
    <div class="stat">
        <div class="stat__label">En attente</div>
        <div class="stat__value">{{ $counts['pending'] }}</div>
        <div class="stat__hint">à valider</div>
    </div>
    <div class="stat">
        <div class="stat__label">Publiées</div>
        <div class="stat__value">{{ $counts['approved'] }}</div>
        <div class="stat__hint">visibles sur GoodTripLove</div>
    </div>
    <div class="stat">
        <div class="stat__label">Retirées</div>
        <div class="stat__value">{{ $counts['rejected'] }}</div>
        <div class="stat__hint">refusées ou dépubliées</div>
    </div>
</div>

<div class="card">
    <h2>Ajouter une vidéo {{ $label }}</h2>
    <p class="muted">
        Colle l'adresse de la vidéo. La plateforme est reconnue automatiquement, donc une adresse d'une autre plateforme fonctionne aussi.
        @if ($metadata === 'full')
            {{ $label }} renvoie le titre, l'auteur et la miniature : les autres champs sont facultatifs.
        @elseif ($metadata === 'partial')
            {{ $label }} ne renvoie pas toujours le titre. S'il manque, saisis-le ici.
        @else
            {{ $label }} ne renvoie ni titre ni miniature sans application Meta approuvée : le titre est obligatoire.
        @endif
    </p>

    <form method="post" action="{{ route('admin.social.store', ['platform' => $platform]) }}" class="stack">
        @csrf
        <div class="grid-2">
            <label>
                <span>Adresse de la vidéo</span>
                <input type="text" name="url" value="{{ old('url') }}" required
                       placeholder="https://www.{{ $platform }}.com/…" autocomplete="off">
            </label>
            <label>
                <span>Titre @if ($metadata === 'none')(obligatoire)@else(facultatif)@endif</span>
                <input type="text" name="title" value="{{ old('title') }}" maxlength="250"
                       placeholder="Laisser vide pour utiliser le titre de la plateforme">
            </label>
        </div>
        <div class="grid-3">
            <label>
                <span>Pays</span>
                <select name="country_id">
                    <option value="">—</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->id }}" @selected(old('country_id') == $country->id)>{{ $country->displayName() }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Ville</span>
                <select name="city_id">
                    <option value="">—</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected(old('city_id') == $city->id)>{{ $city->slug }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Catégorie</span>
                <select name="category_id">
                    <option value="">—</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->displayName() }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div>
            <button class="btn btn-primary" type="submit" @disabled(! $enabled)>Ajouter la vidéo</button>
            <span class="muted">
                @if ($requiresApproval) Elle arrivera en attente de validation. @else Elle sera publiée immédiatement. @endif
            </span>
        </div>
    </form>
</div>

<div class="card">
    <h2>File {{ $label }}</h2>

    <form method="get" class="filters">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Titre, compte, adresse…">
        <select name="status">
            <option value="">Tous les statuts</option>
            <option value="pending" @selected(request('status') === 'pending')>En attente</option>
            <option value="approved" @selected(request('status') === 'approved')>Publiées</option>
            <option value="rejected" @selected(request('status') === 'rejected')>Retirées</option>
        </select>
        <select name="country">
            <option value="">Tous les pays</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" @selected(request('country') == $country->id)>{{ $country->displayName() }}</option>
            @endforeach
        </select>
        <select name="category">
            <option value="">Toutes les catégories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->displayName() }}</option>
            @endforeach
        </select>
        <button class="btn" type="submit">Filtrer</button>
    </form>

    @if ($videos->isEmpty())
        <p class="muted">Aucune vidéo {{ $label }} pour l'instant.</p>
    @else
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Aperçu</th>
                    <th>Contenu</th>
                    <th>Compte</th>
                    <th>Lieu</th>
                    <th>Catégorie</th>
                    <th>Identifiant</th>
                    <th>Adresse d'origine</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($videos as $video)
                <tr>
                    <td>
                        <img src="{{ $video->thumbnail() }}" alt="" width="88" height="50"
                             style="border-radius:6px;object-fit:cover;background:#0d1520" loading="lazy">
                    </td>
                    <td>
                        <strong>{{ \Illuminate\Support\Str::limit($video->title, 60) }}</strong>
                        <div class="muted">{{ $video->durationForHumans() ?? '—' }}</div>
                    </td>
                    <td>{{ $video->channel_title ?? '—' }}</td>
                    <td>{{ collect([$video->country?->displayName(), $video->city?->slug])->filter()->implode(' / ') ?: '—' }}</td>
                    <td>{{ $video->category?->displayName() ?? '—' }}</td>
                    <td><code>{{ \Illuminate\Support\Str::limit($video->provider_video_id, 18) }}</code></td>
                    <td>
                        @if ($video->original_url)
                            <a href="{{ $video->original_url }}" target="_blank" rel="noopener nofollow">
                                {{ \Illuminate\Support\Str::limit(preg_replace('~^https?://(www\.)?~', '', $video->original_url), 30) }}
                            </a>
                        @else — @endif
                    </td>
                    <td>
                        @if ($video->status === 'approved' && $video->is_available)
                            <span class="badge badge-success">Publiée</span>
                        @elseif ($video->status === 'pending')
                            <span class="badge badge-warning">En attente</span>
                        @else
                            <span class="badge">Retirée</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap">
                        <a class="btn btn-sm" href="{{ route('admin.videos.edit', ['video' => $video->id]) }}">Modifier</a>
                        @if ($video->status === 'approved' && $video->is_available)
                            <form method="post" action="{{ route('admin.social.disable', ['video' => $video->id]) }}" style="display:inline">
                                @csrf
                                <button class="btn btn-sm" type="submit">Retirer</button>
                            </form>
                        @else
                            <form method="post" action="{{ route('admin.social.enable', ['video' => $video->id]) }}" style="display:inline">
                                @csrf
                                <button class="btn btn-sm" type="submit">Publier</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $videos->links() }}
    @endif
</div>

<div class="card">
    <h2>État de la connexion {{ $label }}</h2>
    <ul class="status-list">
        <li>
            <span class="dot {{ $enabled ? 'dot--ok' : 'dot--off' }}"></span>
            Import {{ $label }} : <strong>{{ $enabled ? 'activé' : 'désactivé' }}</strong>
        </li>
        <li>
            <span class="dot {{ $credentialPresent ? 'dot--ok' : 'dot--warn' }}"></span>
            Identifiants API : <strong>{{ $credentialPresent ? 'enregistrés' : 'absents' }}</strong>
            <span class="muted">{{ $definition['credential_hint'] }}</span>
        </li>
        <li>
            <span class="dot {{ $metadata === 'full' ? 'dot--ok' : ($metadata === 'partial' ? 'dot--warn' : 'dot--off') }}"></span>
            Informations automatiques :
            <strong>
                @if ($metadata === 'full') titre, auteur et miniature
                @elseif ($metadata === 'partial') partielles
                @else aucune sans application approuvée
                @endif
            </strong>
        </li>
        <li>
            <span class="dot {{ $duplicateCheck ? 'dot--ok' : 'dot--warn' }}"></span>
            Contrôle anti-doublon : <strong>{{ $duplicateCheck ? 'actif' : 'désactivé' }}</strong>
        </li>
        <li>
            <span class="dot {{ $requiresApproval ? 'dot--ok' : 'dot--warn' }}"></span>
            Validation manuelle : <strong>{{ $requiresApproval ? 'obligatoire' : 'désactivée' }}</strong>
        </li>
        <li>
            <span class="dot dot--off"></span>
            Dernier ajout manuel :
            <strong>{{ $lastImport ? \Illuminate\Support\Carbon::parse($lastImport)->diffForHumans() : 'jamais' }}</strong>
        </li>
    </ul>
    <p class="muted">
        Tous ces réglages se modifient dans <a href="{{ route('admin.system.index') }}">Clés &amp; sécurité</a>, sans toucher au code ni au fichier .env.
        Les jetons sont stockés chiffrés sur le serveur et ne sont jamais renvoyés au navigateur.
    </p>
</div>

<style>
/* Only what the admin stylesheet does not already define. */
.dot { display:inline-block;width:9px;height:9px;border-radius:50%;background:#4a5a6e;margin-right:6px }
.dot--ok { background:#2ecc71 }
.dot--warn { background:#f2b134 }
.dot--off { background:#4a5a6e }
.status-list { list-style:none;padding:0;margin:0 0 12px;display:grid;gap:8px }
.status-list .muted { margin-left:6px }
.stat__hint { font-size:12px;color:var(--muted-2) }
.stack { display:grid;gap:12px }
.stack label span { display:block;font-size:12px;color:var(--muted-2);margin-bottom:4px }
.stack input, .stack select { width:100%; }
</style>
@endsection
