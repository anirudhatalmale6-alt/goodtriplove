@extends('layouts.admin')
@section('title', 'Textes légaux')
@section('content')
<h1>Textes légaux</h1>
<p class="muted small">Chaque texte est versionné par langue. Publier une nouvelle version ne modifie jamais l'ancienne : les preuves d'acceptation continuent de pointer vers le texte réellement accepté.</p>

<div class="table-wrap">
<table class="table">
    <thead>
        <tr>
            <th>Document</th>
            @foreach ($locales as $locale)<th>{{ strtoupper($locale) }}</th>@endforeach
        </tr>
    </thead>
    <tbody>
    @foreach ($keys as $key)
        <tr>
            <td><strong>{{ __('gtl.legal_'.str_replace('-', '_', $key), [], 'fr') }}</strong><div class="muted small">{{ $key }}</div></td>
            @foreach ($locales as $locale)
                @php $versions = $documents[$key.'|'.$locale] ?? collect(); $latest = $versions->first(); @endphp
                <td>
                    <a class="btn btn-sm" href="{{ route('admin.legal.edit', ['key' => $key, 'locale' => $locale]) }}">
                        @if ($latest)
                            v{{ $latest->version }}
                            @if (! $latest->published) <span class="badge badge-warning">brouillon</span> @endif
                        @else
                            <span class="muted">— ajouter</span>
                        @endif
                    </a>
                </td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
</div>

@if ($acceptances->isNotEmpty())
<div class="card mt"><div class="card-body">
    <h3>Acceptations enregistrées</h3>
    <table class="table">
        <thead><tr><th>Document</th><th>Version</th><th>Acceptations</th></tr></thead>
        <tbody>
        @foreach ($acceptances as $row)
            <tr><td>{{ $row->document_key }}</td><td>v{{ $row->version }}</td><td>{{ $row->total }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endif
@endsection
