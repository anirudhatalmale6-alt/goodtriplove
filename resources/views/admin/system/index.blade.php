@extends('layouts.admin')
@section('title', 'Clés et sécurité')
@section('content')
<h1>Clés et sécurité</h1>
<p class="muted">Tout ce qui se règle ici se réglait auparavant dans le fichier .env, en SSH. Les clés secrètes sont chiffrées en base et ne sont jamais réaffichées.</p>

@if (session('test_ok'))
    <div class="alert alert--ok" style="white-space:pre-line">{{ session('test_ok') }}</div>
@endif
@if (session('test_error'))
    <div class="alert alert--err" style="white-space:pre-line">{{ session('test_error') }}</div>
@endif

<div class="card"><div class="card-body">
    <h3>État actuel</h3>
    <table class="table">
        <tbody>
            <tr>
                <td>Turnstile (anti-robot)</td>
                <td>{!! $turnstileActive
                    ? '<span class="badge badge-success">actif</span>'
                    : '<span class="badge">inactif</span>' !!}</td>
                <td class="muted small">{{ $turnstileActive ? 'Les formulaires publics sont protégés.' : 'Les formulaires publics ne sont pas protégés par le captcha.' }}</td>
            </tr>
            <tr>
                <td>2FA administrateurs</td>
                <td>{!! $current['admin_2fa_required']
                    ? '<span class="badge badge-success">exigée</span>'
                    : '<span class="badge badge-danger">désactivée</span>' !!}</td>
                <td class="muted small">{{ $current['admin_2fa_required'] ? 'Un code Google Authenticator est demandé à chaque nouvelle session.' : 'Le mot de passe seul suffit pour entrer dans l\'administration.' }}</td>
            </tr>
            <tr>
                <td>Clé API YouTube</td>
                <td>{!! $youtubeConfigured
                    ? '<span class="badge badge-success">enregistrée</span>'
                    : '<span class="badge badge-danger">absente</span>' !!}</td>
                <td class="muted small">{{ $youtubeConfigured ? 'Le collecteur peut importer et rafraîchir les vidéos.' : 'Le collecteur ne peut rien importer.' }}</td>
            </tr>
            <tr>
                <td>Envoi des emails</td>
                <td>{!! $mailConfigured
                    ? '<span class="badge badge-success">configuré</span>'
                    : '<span class="badge badge-danger">absent</span>' !!}</td>
                <td class="muted small">{{ config('mail.mailers.smtp.host') }}:{{ config('mail.mailers.smtp.port') }}</td>
            </tr>
        </tbody>
    </table>
</div></div>

<form method="post" action="{{ route('admin.system.update') }}">
    @csrf @method('PUT')

    @foreach ($definitions as $groupKey => $group)
        <div class="card"><div class="card-body">
            <h3>{{ $group['label'] }}</h3>
            @if (! empty($group['help']))
                <p class="muted small">{{ $group['help'] }}</p>
            @endif

            @foreach ($group['items'] as $key => $definition)
                <div class="field">
                    @if ($definition['type'] === 'bool')
                        <label class="field-inline">
                            <input type="hidden" name="settings[{{ $key }}]" value="0">
                            <input type="checkbox" name="settings[{{ $key }}]" value="1"
                                   @checked($current[$key])>
                            <span>{{ $definition['label'] }}</span>
                        </label>

                    @elseif ($definition['type'] === 'select')
                        <label>{{ $definition['label'] }}</label>
                        <select name="settings[{{ $key }}]">
                            @foreach ($definition['options'] as $value => $label)
                                <option value="{{ $value }}" @selected((string) $current[$key] === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>

                    @elseif ($definition['type'] === 'secret')
                        <label>{{ $definition['label'] }}</label>
                        @if (filled($current[$key]))
                            <p class="muted small">Actuellement enregistrée : <code>{{ $current[$key] }}</code></p>
                        @endif
                        <input type="password" name="settings[{{ $key }}]" autocomplete="new-password"
                               placeholder="{{ filled($current[$key]) ? 'Laisser vide pour conserver la clé actuelle' : 'Coller la clé ici' }}">
                        @if (filled($current[$key]))
                            <label class="field-inline">
                                <input type="checkbox" name="clear[{{ $key }}]" value="1">
                                <span class="muted small">Effacer la valeur enregistrée</span>
                            </label>
                        @endif

                    @else
                        <label>{{ $definition['label'] }}</label>
                        <input name="settings[{{ $key }}]"
                               type="{{ $definition['type'] === 'int' ? 'number' : 'text' }}"
                               value="{{ $current[$key] }}">
                    @endif

                    @if (! empty($definition['help']))
                        <p class="muted small">{{ $definition['help'] }}</p>
                    @endif
                </div>
            @endforeach
        </div></div>
    @endforeach

    <button class="btn btn-primary" type="submit">Enregistrer</button>
</form>

<div class="card"><div class="card-body">
    <h3>Tester</h3>
    <p class="muted small">Enregistre d'abord, puis teste : chaque test interroge le vrai service avec la valeur enregistrée et rapporte sa réponse.</p>

    <div class="grid-2">
        <div>
            <h4>Clé YouTube</h4>
            <p class="muted small">Appelle l'API de Google. Coût : 1 unité de quota sur 10 000.</p>
            <form method="post" action="{{ route('admin.system.test.youtube') }}">
                @csrf
                <button class="btn" type="submit">Tester la clé YouTube</button>
            </form>
        </div>

        <div>
            <h4>Turnstile</h4>
            <p class="muted small">Demande à Cloudflare de valider la Secret Key.</p>
            <form method="post" action="{{ route('admin.system.test.turnstile') }}">
                @csrf
                <button class="btn" type="submit">Tester la Secret Key</button>
            </form>
        </div>
    </div>

    <hr>

    <h4>Envoi d'un email</h4>
    <p class="muted small">Envoie un vrai message. En cas d'échec, le refus exact du serveur mail est affiché.</p>
    <form method="post" action="{{ route('admin.system.test.mail') }}">
        @csrf
        <div class="field">
            <label>Envoyer un email de test à</label>
            <input name="to" type="email" required placeholder="ton.adresse@exemple.com" value="{{ old('to') }}">
        </div>
        <button class="btn" type="submit">Envoyer l'email de test</button>
    </form>
</div></div>
@endsection
