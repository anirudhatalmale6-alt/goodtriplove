@extends('layouts.admin')
@section('title', 'Texte légal')
@section('content')
<div class="topbar">
    <h1 style="margin:0">{{ $key }} — {{ strtoupper($locale) }}</h1>
    <div class="spacer"></div>
    <a class="btn btn-sm" href="{{ route('admin.legal.index') }}">← Retour</a>
</div>

@if (! $current && $reference)
    <div class="alert alert--ok">
        Aucun texte publié dans cette langue. Le site affiche actuellement la version
        <strong>{{ strtoupper($reference->locale) }}</strong> avec une mention visible.
        Le contenu ci-dessous est prérempli à partir de cette version : traduisez-le puis publiez.
    </div>
@endif

<div class="card"><div class="card-body">
    <form method="post" action="{{ route('admin.legal.store', ['key' => $key, 'locale' => $locale]) }}">
        @csrf
        <div class="grid-2">
            <div class="field"><label>Titre</label>
                <input name="title" value="{{ old('title', $current?->title ?? $reference?->title) }}" required>
            </div>
            <div class="field"><label>Nouvelle version</label>
                <input name="version" value="{{ old('version', $current ? '' : '1.0') }}" placeholder="1.1" required>
            </div>
        </div>
        <div class="field">
            <label>Contenu (HTML simple : &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;strong&gt;)</label>
            <textarea name="content" rows="22" required>{{ old('content', $current?->content ?? $reference?->content) }}</textarea>
        </div>
        <label class="small"><input type="checkbox" name="publish" value="1" checked style="width:auto"> Publier immédiatement</label>
        <div class="mt"><button class="btn btn-primary" type="submit">Enregistrer la version</button></div>
    </form>
</div></div>

@if ($history->isNotEmpty())
<div class="card"><div class="card-body">
    <h3>Historique</h3>
    <table class="table">
        <thead><tr><th>Version</th><th>Titre</th><th>Publiée</th><th>Date</th><th></th></tr></thead>
        <tbody>
        @foreach ($history as $document)
            <tr>
                <td>v{{ $document->version }}</td>
                <td>{{ $document->title }}</td>
                <td>{!! $document->published ? '<span class="badge badge-success">oui</span>' : '<span class="badge badge-warning">brouillon</span>' !!}</td>
                <td class="muted small">{{ $document->published_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td>
                    @unless ($document->published)
                        <form class="inline" method="post" action="{{ route('admin.legal.publish', ['document' => $document->id]) }}">@csrf
                            <button class="btn btn-sm btn-primary" type="submit">Publier</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endif
@endsection
