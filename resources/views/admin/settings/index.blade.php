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

<div class="card mt"><div class="card-body">
    <h3>Réglages du site</h3>
    <form method="post" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        @forelse ($settings as $setting)
            <div class="field">
                <label>{{ $setting->key }}</label>
                <input name="settings[{{ $setting->key }}]" value="{{ is_scalar($setting->value) ? $setting->value : json_encode($setting->value) }}">
            </div>
        @empty
            <p class="muted small">Aucun réglage enregistré pour l'instant.</p>
        @endforelse
        <div class="field">
            <label>Contact affiché sur le site</label>
            <input name="settings[contact_email]" value="{{ \App\Models\SiteSetting::get('contact_email') }}">
        </div>
        <button class="btn btn-primary" type="submit">Enregistrer</button>
    </form>
</div></div>
@endsection
