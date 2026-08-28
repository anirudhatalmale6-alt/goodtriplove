@extends('layouts.admin')
@section('title', 'Paramètres')
@section('content')
<h1>Paramètres</h1>

<div class="card"><div class="card-body">
    <h3>Application mobile</h3>
    <p class="muted small">L'empreinte SHA-256 est calculée ici, à partir du fichier réellement servi au téléchargement.</p>
    <form method="post" action="{{ route('admin.settings.releases.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="grid-3">
            <div class="field"><label>Plateforme</label>
                <select name="platform"><option value="android">Android</option><option value="ios">iOS</option></select>
            </div>
            <div class="field"><label>Version</label><input name="version" required placeholder="1.0.0"></div>
            <div class="field"><label>Version code</label><input name="version_code" type="number"></div>
        </div>
        <div class="grid-2">
            <div class="field"><label>Lien store (optionnel)</label><input name="store_url" type="url"></div>
            <div class="field"><label>Fichier APK (optionnel)</label><input name="apk" type="file" accept=".apk"></div>
        </div>
        <button class="btn btn-primary" type="submit">Publier la version</button>
    </form>
</div></div>

@if ($releases->isNotEmpty())
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Plateforme</th><th>Version</th><th>Taille</th><th>SHA-256</th><th>Publiée</th><th>Active</th><th>Téléchargements</th></tr></thead>
    <tbody>
    @foreach ($releases as $release)
        <tr>
            <td>{{ $release->platform }}</td>
            <td>{{ $release->version }}</td>
            <td class="muted small">{{ $release->sizeForHumans() ?? '—' }}</td>
            <td class="muted small" style="word-break:break-all;max-width:280px">{{ $release->sha256 ?? '—' }}</td>
            <td class="muted small">{{ $release->released_at?->format('d/m/y') }}</td>
            <td>{!! $release->is_active ? '<span class="badge badge-success">oui</span>' : '<span class="badge">non</span>' !!}</td>
            <td>{{ $release->downloads }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endif

<form method="post" action="{{ route('admin.settings.update') }}">
    @csrf @method('PUT')

    @foreach ($definitions as $groupKey => $group)
    <div class="card mt"><div class="card-body">
        <h3>{{ $group['label'] }}</h3>

        @foreach ($group['items'] as $key => $definition)
            <div class="field">
                <label>{{ $definition['label'] ?? $key }}</label>

                @if ($definition['translatable'] ?? false)
                    <p class="muted small">Une valeur par langue. Une langue laissée vide reprend le français.</p>
                    @foreach ($locales as $locale)
                        <div class="field-inline">
                            <span class="badge">{{ strtoupper($locale) }}</span>
                            @if ($definition['type'] === 'textarea')
                                <textarea name="settings[{{ $key }}][{{ $locale }}]" rows="2">{{ $current[$key][$locale] ?? '' }}</textarea>
                            @else
                                <input name="settings[{{ $key }}][{{ $locale }}]" value="{{ $current[$key][$locale] ?? '' }}">
                            @endif
                        </div>
                    @endforeach
                @elseif ($definition['type'] === 'textarea')
                    <textarea name="settings[{{ $key }}]" rows="2">{{ $current[$key] }}</textarea>
                @else
                    <input type="{{ $definition['type'] === 'bool' ? 'text' : $definition['type'] }}"
                           name="settings[{{ $key }}]" value="{{ $current[$key] }}">
                @endif
            </div>
        @endforeach
    </div></div>
    @endforeach

    <div class="card mt"><div class="card-body">
        <button class="btn btn-primary" type="submit">Enregistrer les réglages</button>
        <p class="muted small" style="margin-top:8px">
            Ces valeurs sont affichées sur le site public (pied de page, coordonnées, réseaux sociaux).
            Une valeur laissée vide n'affiche rien plutôt qu'un espace vide.
        </p>
    </div></div>
</form>
@endsection
