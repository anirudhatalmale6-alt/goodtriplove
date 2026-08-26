@extends('layouts.admin')
@section('title', $place->exists ? 'Modifier le lieu' : 'Nouveau lieu')
@section('content')
<div class="topbar">
    <h1 style="margin:0">{{ $place->exists ? $place->name : 'Nouveau lieu' }}</h1>
    <div class="spacer"></div>
    <a class="btn btn-sm" href="{{ route('admin.places.index') }}">← Retour</a>
</div>

<div class="card"><div class="card-body">
<form method="post" action="{{ $place->exists ? route('admin.places.update', $place) : route('admin.places.store') }}">
    @csrf
    @if ($place->exists) @method('PUT') @endif

    <div class="grid-2">
        <div class="field"><label>Nom</label><input name="name" value="{{ old('name', $place->name) }}" required></div>
        <div class="field"><label>Statut</label>
            <select name="status">
                @foreach (['pending' => 'En attente', 'published' => 'Publié', 'rejected' => 'Refusé', 'draft' => 'Brouillon'] as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $place->status) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid-3">
        <div class="field"><label>Pays</label>
            <select id="country_id" data-cities-for="{{ url('/admin/countries/__ID__/cities') }}" data-cities-target="#city_id">
                <option value="">—</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}" @selected($place->country_id === $country->id)>{{ $country->displayName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label>Ville</label>
            <select id="city_id" name="city_id" required>
                <option value="">—</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" @selected($place->city_id === $city->id)>{{ $city->displayName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label>Catégorie</label>
            <select name="category_id">
                <option value="">—</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($place->category_id === $category->id)>{{ $category->parent_id ? '— ' : '' }}{{ $category->displayName() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @foreach (['fr', 'pt', 'es', 'it', 'de', 'en'] as $locale)
        <div class="field">
            <label>Description ({{ strtoupper($locale) }})</label>
            <textarea name="description[{{ $locale }}]" rows="2">{{ old('description.'.$locale, data_get($place->description, $locale)) }}</textarea>
        </div>
    @endforeach

    <div class="grid-3">
        <div class="field"><label>Adresse</label><input name="address" value="{{ old('address', $place->address) }}"></div>
        <div class="field"><label>Latitude</label><input name="latitude" value="{{ old('latitude', $place->latitude) }}"></div>
        <div class="field"><label>Longitude</label><input name="longitude" value="{{ old('longitude', $place->longitude) }}"></div>
    </div>

    <div class="grid-3">
        <div class="field"><label>Téléphone</label><input name="phone" value="{{ old('phone', $place->phone) }}"></div>
        <div class="field"><label>Site web</label><input name="website" type="url" value="{{ old('website', $place->website) }}"></div>
        <div class="field"><label>Gamme de prix</label>
            <select name="price_level">
                <option value="">—</option>
                @for ($i = 1; $i <= 4; $i++)
                    <option value="{{ $i }}" @selected($place->price_level == $i)>{{ str_repeat('€', $i) }}</option>
                @endfor
            </select>
        </div>
    </div>

    <label><input type="checkbox" name="is_featured" value="1" @checked($place->is_featured) style="width:auto"> Mettre en avant</label>

    <div class="mt"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
</form>
</div></div>
@push('scripts')<script src="{{ asset('js/gtl.js') }}" defer></script>@endpush
@endsection
