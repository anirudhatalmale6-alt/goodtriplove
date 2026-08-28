@php
    $pendingVideos = \App\Models\Video::where('status', 'pending')->count();
    $pendingPlaces = \App\Models\Place::where('status', 'pending')->count();
    $openNotices = \App\Models\ContentNotice::whereIn('status', ['received', 'triage', 'under_review'])->count();
    $adminAssetVersion = @filemtime(public_path('css/gtl-admin.css')) ?: 1;
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow">
<title>@yield('title', 'Administration') — GoodTripLove</title>
<link rel="stylesheet" href="{{ asset('css/gtl-admin.css') }}?v={{ $adminAssetVersion }}">
@stack('head')
</head>
<body>
<div class="admin">
    <aside class="sidebar">
        <div class="sidebar__logo">
            <span>Good<span class="b">Trip</span>Love</span>
        </div>

        <div class="sidebar__group">Contenu</div>
        <a class="nav-link {{ request()->is('admin') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">Tableau de bord</a>
        <a class="nav-link {{ request()->is('admin/videos*') ? 'is-active' : '' }}" href="{{ route('admin.videos.index') }}">
            Vidéos @if ($pendingVideos) <span class="pill">{{ $pendingVideos }}</span> @endif
        </a>
        <a class="nav-link {{ request()->is('admin/places*') ? 'is-active' : '' }}" href="{{ route('admin.places.index') }}">
            Lieux @if ($pendingPlaces) <span class="pill">{{ $pendingPlaces }}</span> @endif
        </a>
        <a class="nav-link {{ request()->is('admin/collector*') ? 'is-active' : '' }}" href="{{ route('admin.collector.index') }}">Collecteur vidéo</a>

        <div class="sidebar__group">Structure</div>
        <a class="nav-link {{ request()->is('admin/countries*') ? 'is-active' : '' }}" href="{{ route('admin.countries.index') }}">Pays</a>
        <a class="nav-link {{ request()->is('admin/cities*') ? 'is-active' : '' }}" href="{{ route('admin.cities.index') }}">Villes</a>
        <a class="nav-link {{ request()->is('admin/categories*') ? 'is-active' : '' }}" href="{{ route('admin.categories.index') }}">Catégories</a>

        <div class="sidebar__group">Régie & audience</div>
        <a class="nav-link {{ request()->is('admin/ads*') ? 'is-active' : '' }}" href="{{ route('admin.ads.index') }}">Publicités & annonces</a>
        <a class="nav-link {{ request()->is('admin/growth-ops*') ? 'is-active' : '' }}" href="{{ route('admin.growth-ops') }}">Growth & Operations</a>
        <a class="nav-link {{ request()->is('admin/data-quality*') ? 'is-active' : '' }}" href="{{ route('admin.data-quality') }}">Qualité des données</a>
        <a class="nav-link {{ request()->is('admin/notices*') ? 'is-active' : '' }}" href="{{ route('admin.notices.index') }}">
            Signalements @if ($openNotices) <span class="pill">{{ $openNotices }}</span> @endif
        </a>
        <a class="nav-link {{ request()->is('admin/moderation*') ? 'is-active' : '' }}" href="{{ route('admin.moderation') }}">Modération</a>
        <a class="nav-link {{ request()->is('admin/seo*') ? 'is-active' : '' }}" href="{{ route('admin.seo.index') }}">SEO</a>
        <a class="nav-link {{ request()->is('admin/legal*') ? 'is-active' : '' }}" href="{{ route('admin.legal.index') }}">Textes légaux</a>

        <div class="sidebar__group">Exploitation</div>
        <a class="nav-link {{ request()->is('admin/operations/status*') ? 'is-active' : '' }}" href="{{ route('admin.operations.status') }}">État des services</a>
        <a class="nav-link {{ request()->is('admin/operations/youtube-quota*') ? 'is-active' : '' }}" href="{{ route('admin.operations.youtube-quota') }}">Quota YouTube</a>
        <a class="nav-link {{ request()->is('admin/operations/features*') ? 'is-active' : '' }}" href="{{ route('admin.operations.features') }}">Fonctionnalités</a>
        <a class="nav-link {{ request()->is('admin/operations/errors*') ? 'is-active' : '' }}" href="{{ route('admin.operations.errors') }}">Centre d'erreurs</a>

        <div class="sidebar__group">Système</div>
        <a class="nav-link {{ request()->is('admin/users*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">Utilisateurs</a>
        <a class="nav-link {{ request()->is('admin/security-center*') ? 'is-active' : '' }}" href="{{ route('admin.security-center') }}">Security Center</a>
        <a class="nav-link {{ request()->is('admin/audit*') ? 'is-active' : '' }}" href="{{ route('admin.audit.index') }}">Journal des actions</a>
        <a class="nav-link {{ request()->is('admin/settings*') ? 'is-active' : '' }}" href="{{ route('admin.settings.index') }}">Paramètres</a>

        <div class="sidebar__group">&nbsp;</div>
        <a class="nav-link" href="{{ route('home', ['locale' => 'fr']) }}" target="_blank">↗ Voir le site</a>
        <form action="{{ route('logout', ['locale' => 'fr']) }}" method="post" style="padding:0 10px">
            @csrf
            <button class="btn btn-sm" type="submit" style="width:100%">Déconnexion</button>
        </form>
    </aside>

    <main class="main">
        @if (session('status'))
            <div class="alert alert--ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert--err">
                @foreach ($errors->all() as $error) <div>{{ $error }}</div> @endforeach
            </div>
        @endif

        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
