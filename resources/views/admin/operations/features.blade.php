@extends('layouts.admin')
@section('title', 'Fonctionnalités')
@section('content')
<h1>Fonctionnalités</h1>
<p class="muted small">Activer ou désactiver une fonction sans redéploiement. Le mode maintenance renvoie une page 503 à tout le monde sauf aux super admins.</p>

<div class="card"><div class="card-body">
    <form method="post" action="{{ route('admin.operations.features.update') }}">
        @csrf @method('PUT')
        @foreach ($flags as $key => $flag)
            <label style="display:flex;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--line)">
                <input type="checkbox" name="flags[{{ $key }}]" value="1" @checked($flag['enabled']) style="width:auto;margin-top:3px">
                <span>
                    <strong>{{ $key }}</strong>
                    <div class="muted small">{{ $flag['label'] }}</div>
                </span>
            </label>
        @endforeach
        <div class="mt"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
    </form>
</div></div>
@endsection
