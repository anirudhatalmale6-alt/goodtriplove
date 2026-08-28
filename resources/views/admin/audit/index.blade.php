@extends('layouts.admin')
@section('title', 'Journal des actions')
@section('content')
<h1>Journal des actions administratives</h1>

<p class="muted">
    Lecture seule : ce journal ne peut être ni modifié ni supprimé depuis l'admin.
    Les mots de passe, jetons et clés d'API y sont remplacés par [REDACTED].
</p>

<div class="card"><div class="card-body">
    <form method="get" class="grid-3">
        <div class="field">
            <label>Action</label>
            <select name="action">
                <option value="">Toutes</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>Administrateur</label>
            <select name="actor">
                <option value="">Tous</option>
                @foreach ($actors as $actor)
                    <option value="{{ $actor->id }}" @selected((int) request('actor') === $actor->id)>{{ $actor->email }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>Résultat</label>
            <select name="success">
                <option value="">Tous</option>
                <option value="ok" @selected(request('success') === 'ok')>Réussi</option>
                <option value="ko" @selected(request('success') === 'ko')>Échoué</option>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Filtrer</button>
    </form>
</div></div>

<div class="card"><div class="card-body">
    @if ($entries->isEmpty())
        <p class="muted">Aucune entrée pour ces critères.</p>
    @else
    <table class="table">
        <thead><tr><th>Date</th><th>Administrateur</th><th>Action</th><th>Modification</th><th>IP</th><th>Résultat</th></tr></thead>
        <tbody>
        @foreach ($entries as $entry)
            <tr>
                <td class="nowrap">{{ $entry->created_at?->format('d/m/Y H:i:s') }}</td>
                <td>{{ $entry->actor?->email ?? 'Tâche automatique' }}</td>
                <td><code>{{ $entry->action }}</code></td>
                <td>
                    @php $changes = $entry->changes(); @endphp
                    @forelse ($changes as $field => $pair)
                        <div>
                            <strong>{{ $field }}</strong> :
                            <span class="muted">{{ is_scalar($pair[0]) ? \Illuminate\Support\Str::limit((string) $pair[0], 40) : json_encode($pair[0]) }}</span>
                            &rarr;
                            {{ is_scalar($pair[1]) ? \Illuminate\Support\Str::limit((string) $pair[1], 40) : json_encode($pair[1]) }}
                        </div>
                    @empty
                        <span class="muted">&mdash;</span>
                    @endforelse
                </td>
                <td class="nowrap">{{ $entry->ip_address }}</td>
                <td>{{ $entry->success ? 'OK' : 'Échec' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $entries->links() }}
    @endif
</div></div>
@endsection
