@extends('layouts.admin')
@section('title', 'SEO')
@section('content')
<h1>SEO par page et par langue</h1>

<p class="muted">
    Ce qui est enregistré ici remplace le titre, la description, le lien canonique
    et la directive d'indexation de la page correspondante. Laisser un champ vide
    conserve la valeur automatique du site.
</p>

<div class="card"><div class="card-body">
    <h3>Ajouter ou remplacer</h3>
    <form method="post" action="{{ route('admin.seo.store') }}">
        @csrf
        <div class="grid-3">
            <div class="field">
                <label>Page</label>
                <select name="page_type" required>
                    @foreach ($pages as $name => $label)
                        <option value="{{ $name }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Clé de page</label>
                <input name="page_key" value="*" required>
                <small class="muted">« * » pour une page unique (accueil). Sinon le slug, par exemple « portugal/lisbon ».</small>
            </div>
            <div class="field">
                <label>Langue</label>
                <select name="locale" required>
                    @foreach ($locales as $code)
                        <option value="{{ $code }}" @selected($code === $locale)>{{ strtoupper($code) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid-2">
            <div class="field"><label>Titre (balise title)</label><input name="title" maxlength="180"></div>
            <div class="field"><label>Lien canonique</label><input name="canonical_url" type="url" placeholder="https://…"></div>
        </div>
        <div class="field">
            <label>Méta description</label>
            <textarea name="description" rows="2" maxlength="320"></textarea>
        </div>
        <div class="field">
            <label><input type="checkbox" name="indexable" value="1" checked> Autoriser l'indexation par les moteurs de recherche</label>
            <small class="muted">Décoché, la page envoie noindex,nofollow.</small>
        </div>
        <button class="btn btn-primary" type="submit">Enregistrer</button>
    </form>
</div></div>

<div class="card"><div class="card-body">
    <h3>Règles enregistrées</h3>

    @if ($overrides->isEmpty())
        <p class="muted">Aucune règle pour le moment : toutes les pages utilisent les valeurs automatiques.</p>
    @else
    <table class="table">
        <thead><tr><th>Page</th><th>Clé</th><th>Langue</th><th>Titre</th><th>Indexée</th><th></th></tr></thead>
        <tbody>
        @foreach ($overrides as $override)
            <tr>
                <td>
                    <form method="post" action="{{ route('admin.seo.update', $override->id) }}" id="seo-{{ $override->id }}">
                        @csrf @method('put')
                        <input type="hidden" name="page_type" value="{{ $override->page_type }}">
                        <input type="hidden" name="page_key" value="{{ $override->page_key }}">
                        <input type="hidden" name="locale" value="{{ $override->locale }}">
                        {{ $override->page_type }}
                    </form>
                </td>
                <td><code>{{ $override->page_key }}</code></td>
                <td>{{ strtoupper($override->locale) }}</td>
                <td>
                    <input form="seo-{{ $override->id }}" name="title" value="{{ $override->title }}" maxlength="180">
                    <textarea form="seo-{{ $override->id }}" name="description" rows="2" maxlength="320">{{ $override->description }}</textarea>
                    <input form="seo-{{ $override->id }}" name="canonical_url" type="url" value="{{ $override->canonical_url }}" placeholder="Lien canonique">
                </td>
                <td>
                    <label><input form="seo-{{ $override->id }}" type="checkbox" name="indexable" value="1" @checked($override->indexable)> oui</label>
                </td>
                <td class="nowrap">
                    <button class="btn" form="seo-{{ $override->id }}" type="submit">Enregistrer</button>
                    <form method="post" action="{{ route('admin.seo.destroy', $override->id) }}" class="inline">
                        @csrf @method('delete')
                        <button class="btn btn-danger" type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $overrides->links() }}
    @endif
</div></div>
@endsection
