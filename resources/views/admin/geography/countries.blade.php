@extends('layouts.admin')
@section('title', 'Pays')
@section('content')
<h1>Pays</h1>

<div class="card"><div class="card-body">
    <h3>Ajouter un pays</h3>
    <form method="post" action="{{ route('admin.countries.store') }}">
        @csrf
        <div class="grid-3">
            <div class="field"><label>Code ISO (2 lettres)</label><input name="code" maxlength="2" required></div>
            <div class="field"><label>Drapeau</label><input name="flag_emoji" maxlength="16" placeholder="🇵🇹"></div>
            <div class="field"><label>Ordre</label><input name="sort_order" type="number" value="0"></div>
        </div>
        <div class="grid-3">
            @foreach (['fr', 'pt', 'es', 'it', 'de', 'en'] as $locale)
                <div class="field"><label>Nom {{ strtoupper($locale) }}</label><input name="name[{{ $locale }}]"></div>
            @endforeach
        </div>
        <button class="btn btn-primary" type="submit">Ajouter</button>
    </form>
</div></div>

@foreach ($countries as $country)
<div class="card"><div class="card-body">
    <form method="post" action="{{ route('admin.countries.update', $country) }}">
        @csrf @method('PUT')
        <div class="topbar">
            <strong>{{ $country->flag_emoji }} {{ $country->displayName() }}</strong>
            <span class="badge">{{ $country->cities_count }} villes</span>
            <span class="badge">{{ $country->videos_count }} vidéos</span>
            <div class="spacer"></div>
            <label class="small"><input type="checkbox" name="is_active" value="1" @checked($country->is_active) style="width:auto"> Actif</label>
            <label class="small"><input type="checkbox" name="is_featured" value="1" @checked($country->is_featured) style="width:auto"> En avant</label>
            <button class="btn btn-primary btn-sm" type="submit">Enregistrer</button>
        </div>
        <div class="grid-3">
            @foreach (['fr', 'pt', 'es', 'it', 'de', 'en'] as $locale)
                <div class="field"><label>Nom {{ strtoupper($locale) }}</label>
                    <input name="name[{{ $locale }}]" value="{{ data_get($country->name, $locale) }}">
                </div>
            @endforeach
        </div>
        <div class="grid-2">
            <div class="field"><label>Drapeau</label><input name="flag_emoji" value="{{ $country->flag_emoji }}"></div>
            <div class="field"><label>Ordre</label><input name="sort_order" type="number" value="{{ $country->sort_order }}"></div>
        </div>
    </form>
</div></div>
@endforeach
@endsection
