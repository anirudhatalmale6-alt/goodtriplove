@extends('layouts.admin')
@section('title', 'Utilisateurs')
@section('content')
<h1>Utilisateurs</h1>

<form class="filters" method="get">
    <div class="field"><label>Rôle</label>
        <select name="role" onchange="this.form.submit()">
            <option value="">Tous</option>
            @foreach (['user', 'business', 'moderator', 'admin', 'super_admin'] as $role)
                <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }} ({{ $roleCounts[$role] ?? 0 }})</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label>Recherche</label><input type="search" name="q" value="{{ request('q') }}"></div>
    <div class="field"><label>Comptes supprimés</label>
        <select name="deleted" onchange="this.form.submit()">
            <option value="">Masqués</option>
            <option value="1" @selected(request('deleted') === '1')>Afficher ({{ $trashedCount }})</option>
        </select>
    </div>
    <button class="btn btn-primary" type="submit">Filtrer</button>
</form>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Lieux</th><th>Vérifié</th><th>2FA</th><th>Inscrit</th><th>Dernière connexion</th><th>Actif</th><th></th><th></th></tr></thead>
    <tbody>
    @foreach ($users as $user)
        <tr>
            <form method="post" action="{{ route('admin.users.update', $user) }}">
                @csrf @method('PUT')
                <td>{{ $user->name }}</td>
                <td class="muted small">{{ $user->email }}</td>
                <td>
                    <select name="role" style="width:130px" @disabled(auth()->user()->role !== 'super_admin' || $user->id === auth()->id())>
                        @foreach (['user', 'business', 'moderator', 'admin', 'super_admin'] as $role)
                            <option value="{{ $role }}" @selected($user->role === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                </td>
                <td>{{ $user->places_count }}</td>
                <td>{!! $user->email_verified_at ? '<span class="badge badge-success">oui</span>' : '<span class="badge badge-warning">non</span>' !!}</td>
                <td>{!! $user->two_factor_enabled ? '<span class="badge badge-success">on</span>' : '<span class="badge">off</span>' !!}</td>
                <td class="muted small nowrap">{{ $user->created_at?->format('d/m/y') }}</td>
                <td class="muted small nowrap">{{ $user->last_login_at?->format('d/m/y H:i') ?? '—' }}</td>
                <td><input type="checkbox" name="is_active" value="1" @checked($user->is_active) style="width:auto"></td>
                <td><button class="btn btn-sm btn-primary" type="submit" @disabled(auth()->user()->role !== 'super_admin' || $user->id === auth()->id())>OK</button></td>
                <td><a href="{{ route('admin.users.show', $user->id) }}">fiche</a></td>
            </form>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $users->onEachSide(1)->links('pagination') }}</div>
@endsection
