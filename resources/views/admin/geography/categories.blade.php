@extends('layouts.admin')
@section('title', 'Catégories')
@section('content')
<h1>Catégories</h1>
<p class="muted small">Les mots-clés servent au collecteur : ce sont les termes envoyés à l'API YouTube pour cette catégorie, dans chaque langue. Séparez-les par des virgules.</p>

@foreach ($categories as $category)
<div class="card"><div class="card-body">
    <form method="post" action="{{ route('admin.categories.update', $category) }}">
        @csrf @method('PUT')
        <div class="topbar">
            <strong>{{ $category->icon }} {{ $category->displayName() }}</strong>
            <div class="spacer"></div>
            <label class="small"><input type="checkbox" name="is_active" value="1" @checked($category->is_active) style="width:auto"> Active</label>
            <label class="small"><input type="checkbox" name="show_on_home" value="1" @checked($category->show_on_home) style="width:auto"> Accueil</label>
            <button class="btn btn-primary btn-sm" type="submit">Enregistrer</button>
        </div>
        <div class="grid-3">
            @foreach ($locales as $locale)
                <div class="field"><label>Nom {{ strtoupper($locale) }}</label>
                    <input name="name[{{ $locale }}]" value="{{ data_get($category->name, $locale) }}">
                </div>
            @endforeach
        </div>
        <div class="grid-3">
            @foreach ($locales as $locale)
                <div class="field"><label>Mots-clés {{ strtoupper($locale) }}</label>
                    <input name="search_terms[{{ $locale }}]" value="{{ implode(', ', (array) data_get($category->search_terms, $locale, [])) }}">
                </div>
            @endforeach
        </div>
        <div class="grid-3">
            <div class="field"><label>Icône (emoji)</label><input name="icon" value="{{ $category->icon }}"></div>
            <div class="field"><label>Couleur</label><input name="accent_color" value="{{ $category->accent_color }}"></div>
            <div class="field"><label>Ordre</label><input name="sort_order" type="number" value="{{ $category->sort_order }}"></div>
        </div>
    </form>

    @if ($category->children->isNotEmpty())
        <p class="small muted">Sous-catégories : {{ $category->children->map(fn ($c) => $c->displayName())->implode(', ') }}</p>
    @endif
</div></div>
@endforeach
@endsection
