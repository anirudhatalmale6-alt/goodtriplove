@extends('layouts.admin')
@section('title', 'Signalement #'.$notice->id)
@section('content')
<div class="topbar">
    <h1 style="margin:0">Signalement #{{ $notice->id }}</h1>
    <span class="badge {{ $notice->status === 'closed' ? 'badge-success' : 'badge-warning' }}">{{ $notice->status }}</span>
    <div class="spacer"></div>
    <a class="btn btn-sm" href="{{ route('admin.notices.index') }}">← Retour</a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h3>Contenu signalé</h3>
            <table class="table">
                <tbody>
                    <tr><th>Type</th><td>{{ $notice->target_type }}</td></tr>
                    <tr><th>Identifiant</th><td>{{ $notice->target_id ?? '—' }}</td></tr>
                    <tr><th>URL</th><td style="word-break:break-all">{{ $notice->target_url ?? '—' }}</td></tr>
                    <tr><th>Motif</th><td>{{ $notice->reason }}</td></tr>
                    <tr><th>Déclarant</th><td>{{ $notice->reporter_email ?? '—' }}</td></tr>
                    <tr><th>Reçu le</th><td>{{ $notice->created_at?->format('d/m/Y H:i') }}</td></tr>
                </tbody>
            </table>
            <h3 class="mt">Explication</h3>
            <p class="small">{{ $notice->explanation }}</p>
        </div></div>
    </div>

    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h3>Traitement</h3>
            @if ($notice->status !== 'closed')
                <form method="post" action="{{ route('admin.notices.triage', ['notice' => $notice->id]) }}" style="margin-bottom:14px">
                    @csrf
                    <div class="field"><label>Étape</label>
                        <select name="status">
                            <option value="triage" @selected($notice->status === 'triage')>En tri</option>
                            <option value="under_review" @selected($notice->status === 'under_review')>En examen</option>
                        </select>
                    </div>
                    <button class="btn btn-sm" type="submit">Mettre à jour</button>
                </form>

                <form method="post" action="{{ route('admin.notices.decide', ['notice' => $notice->id]) }}">
                    @csrf
                    <div class="field"><label>Décision</label>
                        <select name="decision" required>
                            @foreach ($decisions as $decision)
                                <option value="{{ $decision }}">{{ $decision }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Motivation (conservée dans le journal)</label>
                        <textarea name="decision_reason" rows="4" maxlength="2000" required></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Enregistrer la décision</button>
                </form>
            @else
                <p class="small"><strong>Décision :</strong> {{ $notice->decision }}</p>
                <p class="small muted">{{ $notice->decision_reason }}</p>
                <p class="small muted">Clos le {{ $notice->reviewed_at?->format('d/m/Y H:i') }}</p>

                @unless ($notice->notified_at)
                    <form method="post" action="{{ route('admin.notices.notified', ['notice' => $notice->id]) }}">@csrf
                        <button class="btn btn-sm" type="submit">Marquer le déclarant comme notifié</button>
                    </form>
                @else
                    <p class="small"><span class="badge badge-success">Déclarant notifié</span> {{ $notice->notified_at->format('d/m/Y H:i') }}</p>
                @endunless
            @endif
        </div></div>
    </div>
</div>
@endsection
