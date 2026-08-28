@extends('layouts.admin')
@section('title', 'Fiche membre')
@section('content')

<p><a href="{{ route('admin.users.index') }}">&larr; Retour à la liste</a></p>

<h1>{{ $user->name }}
    @if ($user->trashed())
        <span class="badge badge-warning">compte supprimé</span>
    @elseif (! $user->is_active)
        <span class="badge badge-warning">désactivé</span>
    @endif
</h1>

<div class="card"><div class="card-body">
    <h3>Identité</h3>
    <table class="table">
        <tr><th style="width:220px">E-mail</th><td>{{ $user->email }}</td></tr>
        <tr><th>Type de compte</th><td>{{ $user->role }}</td></tr>
        @if ($user->company_name)
            <tr><th>Établissement</th><td>{{ $user->company_name }}</td></tr>
        @endif
        @if ($user->phone)
            <tr><th>Téléphone</th><td>{{ $user->phone }}</td></tr>
        @endif
        <tr><th>Langue</th><td>{{ strtoupper($user->locale ?: '—') }}</td></tr>
        <tr><th>Pays</th><td>{{ $user->country_code ?: '—' }}</td></tr>
        <tr><th>Inscrit le</th><td>{{ $user->created_at?->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Dernière connexion</th><td>{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'jamais' }}</td></tr>
        <tr><th>E-mail vérifié</th>
            <td>{!! $user->email_verified_at
                ? '<span class="badge badge-success">oui</span> <span class="muted small">'.$user->email_verified_at->format('d/m/Y H:i').'</span>'
                : '<span class="badge badge-warning">non</span>' !!}</td></tr>
        <tr><th>Double authentification</th>
            <td>{!! $user->two_factor_enabled ? '<span class="badge badge-success">activée</span>' : '<span class="badge">désactivée</span>' !!}</td></tr>
        <tr><th>Compte actif</th>
            <td>{!! $user->is_active ? '<span class="badge badge-success">oui</span>' : '<span class="badge badge-warning">non</span>' !!}</td></tr>
        @if ($user->trashed())
            <tr><th>Supprimé le</th><td>{{ $user->deleted_at?->format('d/m/Y H:i') }}</td></tr>
        @endif
        <tr><th>Lieux · favoris · appareils</th>
            <td>{{ $user->places_count }} · {{ $user->favorites_count }} · {{ $user->devices_count }}</td></tr>
    </table>
</div></div>

<div class="card mt"><div class="card-body">
    <h3>Actions</h3>
    @if (auth()->user()->role !== 'super_admin')
        <p class="muted small">Seul un super administrateur peut modifier ce compte.</p>
    @elseif ($user->id === auth()->id())
        <p class="muted small">Vous ne pouvez pas modifier votre propre compte depuis cet écran : c'est ce qui garantit qu'il reste toujours un accès en cas d'erreur.</p>
    @elseif ($user->trashed())
        <form method="post" action="{{ route('admin.users.restore', $user->id) }}">
            @csrf
            <button class="btn btn-primary" type="submit">Restaurer le compte</button>
        </form>
    @else
        <form method="post" action="{{ route('admin.users.update', $user->id) }}" class="filters">
            @csrf @method('PUT')
            <div class="field"><label>Rôle</label>
                <select name="role">
                    @foreach (['user', 'business', 'moderator', 'admin', 'super_admin'] as $role)
                        <option value="{{ $role }}" @selected($user->role === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Actif</label>
                <input type="checkbox" name="is_active" value="1" @checked($user->is_active) style="width:auto">
            </div>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </form>

        <form method="post" action="{{ route('admin.users.destroy', $user->id) }}" style="margin-top:14px"
              onsubmit="return confirm('Supprimer ce compte ? Il pourra être restauré ensuite.')">
            @csrf @method('DELETE')
            <button class="btn" type="submit">Supprimer le compte</button>
            <span class="muted small">Suppression réversible : les lieux du membre sont conservés et le compte peut être restauré.</span>
        </form>
    @endif
</div></div>

<div class="card mt"><div class="card-body">
    <h3>Lieux proposés ({{ $user->places_count }})</h3>
    @if ($places->isEmpty())
        <p class="muted small">Aucun lieu proposé par ce membre.</p>
    @else
    <table class="table">
        <thead><tr><th>Nom</th><th>Ville</th><th>Statut</th><th>Proposé le</th><th></th></tr></thead>
        <tbody>
        @foreach ($places as $place)
            <tr>
                <td>{{ $place->name }}</td>
                <td class="muted small">{{ $place->city?->name ?? '—' }}</td>
                <td>{{ $place->status }}</td>
                <td class="muted small nowrap">{{ $place->created_at?->format('d/m/y') }}</td>
                <td><a href="{{ route('admin.places.edit', $place->id) }}">ouvrir</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div></div>

<div class="card mt"><div class="card-body">
    <h3>Historique de modération</h3>
    <p class="muted small">Ce qui a été fait sur ce compte, et ce que ce compte a fait s'il est administrateur.</p>
    @if ($history->isEmpty())
        <p class="muted small">Aucune action enregistrée.</p>
    @else
    <table class="table">
        <thead><tr><th>Date</th><th>Par</th><th>Action</th><th>Modification</th></tr></thead>
        <tbody>
        @foreach ($history as $entry)
            <tr>
                <td class="nowrap muted small">{{ $entry->created_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $entry->actor?->email ?? 'tâche automatique' }}</td>
                <td><code>{{ $entry->action }}</code></td>
                <td>
                    @forelse ($entry->changes() as $field => $pair)
                        <div class="small"><strong>{{ $field }}</strong> :
                            <span class="muted">{{ is_scalar($pair[0]) ? $pair[0] : json_encode($pair[0]) }}</span>
                            &rarr; {{ is_scalar($pair[1]) ? $pair[1] : json_encode($pair[1]) }}</div>
                    @empty
                        <span class="muted">&mdash;</span>
                    @endforelse
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div></div>

<div class="card mt"><div class="card-body">
    <h3>Journal de sécurité</h3>
    @if ($securityLog->isEmpty())
        <p class="muted small">Aucun évènement.</p>
    @else
    <table class="table">
        <thead><tr><th>Date</th><th>Évènement</th><th>Résultat</th><th>IP</th></tr></thead>
        <tbody>
        @foreach ($securityLog as $line)
            <tr>
                <td class="nowrap muted small">{{ $line->created_at?->format('d/m/Y H:i') }}</td>
                <td><code>{{ $line->event }}</code></td>
                <td>{{ $line->success ? 'OK' : 'échec' }}</td>
                <td class="muted small">{{ $line->ip_address }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div></div>
@endsection
