@extends('layouts.admin')
@section('title', 'Doublons')
@section('content')
<h1>Doublons de vidéos</h1>

<p class="muted">
    La base refuse déjà deux fois le même identifiant YouTube. Ce que cette page trouve, c'est la même
    vidéo remise en ligne sous un nouvel identifiant : le titre est comparé après nettoyage (minuscules,
    hashtags, ponctuation et emoji retirés).
    Rien n'est supprimé : les copies écartées passent en « rejetée » et restent consultables.
</p>

@if ($groups->isEmpty())
    <div class="card"><div class="card-body">
        <p class="muted">Aucun doublon détecté.</p>
    </div></div>
@else
    <p><strong>{{ $groups->count() }}</strong> groupe(s), soit <strong>{{ $total }}</strong> copie(s) en trop.</p>

    @foreach ($groups as $group)
    <div class="card mt"><div class="card-body">
        <h3>{{ $group['videos']->count() }} copies &mdash; {{ Str::limit($group['videos']->first()->title, 80) }}</h3>

        <form method="post" action="{{ route('admin.videos.duplicates.resolve') }}">
            @csrf
            <table class="table">
                <thead><tr><th>Garder</th><th>Titre</th><th>Chaîne</th><th>Vues</th><th>Statut</th><th>Publiée</th><th></th></tr></thead>
                <tbody>
                @foreach ($group['videos'] as $video)
                    <tr>
                        <td>
                            <input type="radio" name="keep" value="{{ $video->id }}"
                                   @checked($video->id === $group['keeper']->id) style="width:auto">
                            <input type="hidden" name="ids[]" value="{{ $video->id }}">
                        </td>
                        <td class="small">{{ Str::limit($video->title, 60) }}</td>
                        <td class="muted small">{{ Str::limit((string) $video->channel_title, 24) }}</td>
                        <td class="muted small">{{ number_format((int) $video->view_count, 0, ',', ' ') }}</td>
                        <td>
                            @if ($video->status === 'approved')
                                <span class="badge badge-success">publiée</span>
                            @else
                                <span class="badge">{{ $video->status }}</span>
                            @endif
                        </td>
                        <td class="muted small nowrap">{{ $video->published_at?->format('d/m/y') ?? '—' }}</td>
                        <td class="small">
                            <a href="{{ route('admin.videos.edit', $video->id) }}">ouvrir</a>
                            &middot;
                            <a href="https://www.youtube.com/watch?v={{ $video->provider_video_id }}"
                               target="_blank" rel="noopener noreferrer">YouTube</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <button class="btn btn-primary" type="submit">Garder celle-ci, écarter les autres</button>
            <span class="muted small">La copie proposée est celle déjà publiée, sinon la plus vue, sinon la plus ancienne.</span>
        </form>
    </div></div>
    @endforeach
@endif
@endsection
