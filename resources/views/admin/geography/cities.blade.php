@extends('layouts.admin')
@section('title', 'Villes')
@section('content')
<h1>Villes</h1>

<div class="card"><div class="card-body">
    <h3>Ajouter une ville</h3>
    <form method="post" action="{{ route('admin.cities.store') }}">
        @csrf
        <div class="grid-3">
            <div class="field"><label>Pays</label>
                <select name="country_id" required>
                    @foreach ($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Latitude</label><input name="latitude"></div>
            <div class="field"><label>Longitude</label><input name="longitude"></div>
        </div>
        <div class="grid-3">
            @foreach (['fr', 'pt', 'es', 'it', 'de', 'en'] as $locale)
                <div class="field"><label>Nom {{ strtoupper($locale) }}</label><input name="name[{{ $locale }}]"></div>
            @endforeach
        </div>
        <label class="small"><input type="checkbox" name="is_popular" value="1" style="width:auto"> Ville populaire</label>
        <div class="mt"><button class="btn btn-primary" type="submit">Ajouter</button></div>
    </form>
</div></div>

<form class="filters" method="get">
    <div class="field"><label>Pays</label>
        <select name="country" onchange="this.form.submit()">
            <option value="">Tous</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" @selected(request('country') == $country->id)>{{ $country->displayName() }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Ville</th><th>Pays</th><th>Vidéos</th><th>Lieux</th><th>Populaire</th><th>Active</th><th></th></tr></thead>
    <tbody>
    @foreach ($cities as $city)
        <tr>
            <form method="post" action="{{ route('admin.cities.update', $city) }}">
                @csrf @method('PUT')
                <td><input name="name[{{ app()->getLocale() }}]" value="{{ $city->displayName() }}" style="width:180px">
                    @foreach (['fr', 'pt', 'es', 'it', 'de', 'en'] as $locale)
                        @if ($locale !== app()->getLocale())
                            <input type="hidden" name="name[{{ $locale }}]" value="{{ data_get($city->name, $locale) }}">
                        @endif
                    @endforeach
                </td>
                <td class="muted small">{{ $city->country?->displayName() }}</td>
                <td>{{ $city->videos_count }}</td>
                <td>{{ $city->places_count }}</td>
                <td><input type="checkbox" name="is_popular" value="1" @checked($city->is_popular) style="width:auto"></td>
                <td><input type="checkbox" name="is_active" value="1" @checked($city->is_active) style="width:auto"></td>
                <td><button class="btn btn-sm btn-primary" type="submit">OK</button></td>
            </form>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $cities->onEachSide(1)->links('pagination') }}</div>
@endsection
