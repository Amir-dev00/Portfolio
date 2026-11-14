<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('fa') ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>@yield('title', __('Admin')) — AZ Portfolio</title>
  <link rel="stylesheet" href="{{ asset('css/typography.css') }}">
  <style>
    :root { --bg:#0f1115; --panel:#171a21; --line:#2a3140; --text:#eef2f7; --muted:#9aa6b2; --accent:#91dcf6; --danger:#c45c5c; --ok:#3d9a6a; }
    * { box-sizing: border-box; }
    body { margin:0; font-family: var(--font-en); background:var(--bg); color:var(--text); }
    html[dir="rtl"] body, [lang="fa"] { font-family:var(--font-fa); }
    button, input, textarea, select { font-family:inherit; }
    input:is([type="url"], [type="email"], [type="date"], [type="time"], [type="number"]), code { direction:ltr; }
    textarea[dir="rtl"], input[dir="rtl"] { font-family: var(--font-fa); }
    a { color:var(--accent); text-decoration:none; }
    .shell { display:grid; grid-template-columns:220px 1fr; min-height:100vh; }
    aside { border-inline-end:1px solid var(--line); background:#12151c; padding:20px 14px; }
    aside h1 { font-size:15px; margin:0 0 18px; letter-spacing:.04em; }
    aside nav a { display:block; padding:9px 10px; border-radius:8px; color:var(--muted); margin-bottom:4px; }
    aside nav a:hover, aside nav a.active { background:#1d2330; color:var(--text); }
    main { min-width:0; padding:clamp(14px,3vw,32px); }
    main > * { max-width:1200px; }
    .card { background:var(--panel); border:1px solid var(--line); border-radius:12px; padding:16px; margin-bottom:16px; }
    .row { display:grid; gap:12px; grid-template-columns:repeat(2,minmax(0,1fr)); }
    label { display:block; font-size:12px; color:var(--muted); margin-bottom:4px; }
    input, textarea, select { width:100%; background:#0e1218; border:1px solid var(--line); color:var(--text); border-radius:8px; padding:9px 10px; }
    textarea { min-height:88px; }
    button, .btn { display:inline-block; background:var(--accent); color:#111; border:0; border-radius:8px; padding:9px 14px; font-weight:600; cursor:pointer; }
    .btn-secondary { background:transparent; color:var(--text); border:1px solid var(--line); }
    .btn-danger { background:var(--danger); color:#fff; }
    .flash { padding:10px 12px; border-radius:8px; margin-bottom:14px; background:#1b2a22; color:#b6e2c7; }
    .flash-error { background:#2a1818; color:#f0b4b4; }
    table { width:100%; border-collapse:collapse; }
    th, td { text-align:start; padding:8px; border-bottom:1px solid var(--line); font-size:14px; vertical-align:top; }
    .badge { display:inline-block; font-size:11px; padding:2px 7px; border-radius:999px; background:#2a3140; color:var(--muted); }
    .badge.warn { background:#3a2d14; color:#e6c78b; }
    .missing { outline:1px solid #8a5a2b; }
    .topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; }
    :focus-visible { outline:2px solid var(--accent); outline-offset:2px; }
    input[type="checkbox"], input[type="radio"] { width:auto; }
    .admin-pills { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:14px; }
    .card .admin-pills { margin:12px 0 0; }
    .admin-pills [aria-current="page"] { border-color:var(--accent); color:var(--accent); }
    .admin-hint { color:var(--muted); font-size:13px; line-height:1.55; }
    .admin-table, .card:has(table) { overflow:auto; }
    .admin-language { display:flex; gap:6px; margin:0 0 12px; }
    .admin-language [aria-current] { border-color:var(--accent); color:var(--accent); }
    .project-delete { margin-block-start:6px; }
    .project-delete summary { color:#f0a9a9; cursor:pointer; }
    .project-delete button { margin-block-start:8px; }
    .admin-save { position:sticky; bottom:0; padding:12px 0; background:var(--bg); z-index:2; }
    .admin-thumbs { display:flex; gap:8px; flex-wrap:wrap; margin:10px 0; }
    .admin-thumbs img { display:block; width:72px; height:48px; object-fit:cover; border-radius:6px; }
    .admin-shortcuts { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,180px),1fr)); gap:10px; margin-bottom:16px; }
    .admin-shortcuts a { display:grid; gap:4px; margin:0; }
    .admin-shortcuts a:hover { border-color:var(--accent); }
    .admin-shortcuts span { color:var(--muted); font-size:13px; }
    @media (max-width:900px) {
      .shell { grid-template-columns:minmax(0,1fr); }
      aside { border-inline-end:0; border-bottom:1px solid var(--line); }
      aside nav { display:flex; flex-wrap:wrap; gap:4px; }
      aside nav a { margin:0; }
    }
    @media (max-width:600px) {
      .row { grid-template-columns:minmax(0,1fr); }
      .topbar { flex-wrap:wrap; }
    }
  </style>
</head>
<body>
<div class="shell">
  <aside>
    <h1>{{ __('AZ Admin') }}</h1>
    @include('admin.partials.language')
    <nav>
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">{{ __('Dashboard') }}</a>
      <a href="{{ route('admin.content.edit') }}" class="{{ request()->routeIs('admin.content.*') ? 'active' : '' }}">{{ __('Site content') }}</a>
      <a href="{{ route('admin.bookings.index') }}" class="{{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">{{ __('Bookings') }}</a>
      <a href="{{ route('admin.projects.index') }}" class="{{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">{{ __('Projects') }}</a>
      <a href="{{ route('admin.experience.index') }}" class="{{ request()->routeIs('admin.experience.*') ? 'active' : '' }}">{{ __('Work experience') }}</a>
      <a href="{{ route('admin.resume.edit') }}" class="{{ request()->routeIs('admin.resume.*') ? 'active' : '' }}">{{ __('Resume') }}</a>
      <a href="{{ route('admin.seo.edit') }}" class="{{ request()->routeIs('admin.seo.*') ? 'active' : '' }}">{{ __('SEO') }}</a>
      <a href="{{ route('admin.media.index') }}" class="{{ request()->routeIs('admin.media.*') ? 'active' : '' }}">{{ __('Media') }}</a>
      <a href="{{ route('admin.export.json') }}" target="_blank">{{ __('Export JSON') }}</a>
      <a href="{{ route('admin.preview') }}" target="_blank">{{ __('Preview draft') }}</a>
      <a href="{{ url('/') }}" target="_blank">{{ __('View site') }}</a>
    </nav>
    <form method="post" action="{{ route('admin.logout') }}" style="margin-top:18px">
      @csrf
      <button class="btn-secondary" type="submit">{{ __('Log out') }}</button>
    </form>
  </aside>
  <main>
    <div class="topbar">
      <h2 style="margin:0;font-size:22px">@yield('heading', __('Admin'))</h2>
      <form method="post" action="{{ route('admin.publish') }}">
        @csrf
        <input type="hidden" name="label" value="{{ __('Manual publish :date', ['date' => now()->format('Y-m-d H:i')]) }}">
        <button type="submit">{{ __('Publish live') }}</button>
      </form>
    </div>
    @if (session('status'))
      <div class="flash" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="flash flash-error" role="alert">
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
      </div>
    @endif
    @yield('content')
  </main>
</div>
</body>
</html>
