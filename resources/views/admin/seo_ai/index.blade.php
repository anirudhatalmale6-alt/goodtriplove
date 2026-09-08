@extends('layouts.admin')
@section('title', 'SEO AI Manager')
@section('content')
<h1>SEO AI Manager</h1>
<p class="muted">Génération contrôlée de pages SEO multipages en 6 langues. Une seule nouvelle page automatique par semaine.</p>

<div class="card"><div class="card-body">
<h3>Configuration</h3>
<form method="post" action="{{ route('admin.seo-ai.settings') }}">@csrf @method('put')
<div class="grid-3">
<div class="field"><label>Modèle OpenAI</label><input name="model" value="{{ $settings->model }}" required></div>
<div class="field"><label>Seuil qualité /100</label><input type="number" min="50" max="95" name="quality_threshold" value="{{ $settings->quality_threshold }}" required></div>
<div class="field"><label>Nouvelle clé API (laisser vide pour conserver)</label><input type="password" name="api_key" autocomplete="new-password"></div>
</div>
<div class="field"><label><input type="checkbox" name="enabled" value="1" @checked($settings->enabled)> Module actif</label><br><label><input type="checkbox" name="auto_publish" value="1" @checked($settings->auto_publish)> Publication auto si score suffisant</label><br><small class="muted">Création automatique : chaque lundi à 04:45.</small></div>
<button class="btn btn-primary">Enregistrer</button>
</form>
<form method="post" action="{{ route('admin.seo-ai.generate') }}" style="margin-top:12px">@csrf<button class="btn">Générer maintenant pour validation</button></form>
</div></div>

<div class="card"><div class="card-body">
<h3>Pages générées</h3>
<table class="table"><thead><tr><th>ID</th><th>Sujet</th><th>Score</th><th>Langues</th><th>Statut</th><th>Action</th></tr></thead><tbody>
@forelse($pages as $page)
<tr><td>#{{ $page->id }}</td><td><code>{{ $page->topic_key }}</code>@if($page->last_error)<div class="text-danger">{{ $page->last_error }}</div>@endif</td><td>{{ $page->quality_score }}/100</td><td>{{ $page->translations->pluck('locale')->map(fn($l)=>strtoupper($l))->implode(', ') }}</td><td>{{ $page->status }}</td><td>@if($page->status==='published')<form method="post" action="{{ route('admin.seo-ai.unpublish',$page) }}">@csrf<button class="btn">Dépublier</button></form>@elseif($page->translations->isNotEmpty())<form method="post" action="{{ route('admin.seo-ai.publish',$page) }}">@csrf<button class="btn btn-primary">Publier</button></form>@endif</td></tr>
@empty<tr><td colspan="6">Aucune page générée.</td></tr>@endforelse
</tbody></table>
{{ $pages->links() }}
</div></div>
@endsection
