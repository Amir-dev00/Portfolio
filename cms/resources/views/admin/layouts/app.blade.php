<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>@yield('title', 'Admin') — AZ Portfolio</title>
  <style>
    :root { --bg:#0f1115; --panel:#171a21; --line:#2a3140; --text:#eef2f7; --muted:#9aa6b2; --accent:#d7b57a; --danger:#c45c5c; --ok:#3d9a6a; }
    * { box-sizing: border-box; }
    body { margin:0; font-family: Segoe UI, Tahoma, sans-serif; background:var(--bg); color:var(--text); }
    a { color:var(--accent); text-decoration:none; }
    .shell { display:grid; grid-template-columns:220px 1fr; min-height:100vh; }
    aside { border-right:1px solid var(--line); background:#12151c; padding:20px 14px; }
    aside h1 { font-size:15px; margin:0 0 18px; letter-spacing:.04em; }
    aside nav a { display:block; padding:9px 10px; border-radius:8px; color:var(--muted); margin-bottom:4px; }
    aside nav a:hover, aside nav a.active { background:#1d2330; color:var(--text); }
    main { padding:24px; }
    .card { background:var(--panel); border:1px solid var(--line); border-radius:12px; padding:16px; margin-bottom:16px; }
    .row { display:grid; gap:12px; grid-template-columns:1fr 1fr; }
    label { display:block; font-size:12px; color:var(--muted); margin-bottom:4px; }
    input, textarea, select { width:100%; background:#0e1218; border:1px solid var(--line); color:var(--text); border-radius:8px; padding:9px 10px; }
    textarea { min-height:88px; }
    button, .btn { display:inline-block; background:var(--accent); color:#111; border:0; border-radius:8px; padding:9px 14px; font-weight:600; cursor:pointer; }
    .btn-secondary { background:transparent; color:var(--text); border:1px solid var(--line); }
    .btn-danger { background:var(--danger); color:#fff; }
    .flash { padding:10px 12px; border-radius:8px; margin-bottom:14px; background:#1b2a22; color:#b6e2c7; }
    .flash-error { background:#2a1818; color:#f0b4b4; }
    table { width:100%; border-collapse:collapse; }
    th, td { text-align:left; padding:8px; border-bottom:1px solid var(--line); font-size:14px; vertical-align:top; }
    .badge { display:inline-block; font-size:11px; padding:2px 7px; border-radius:999px; background:#2a3140; color:var(--muted); }
    .badge.warn { background:#3a2d14; color:#e6c78b; }
    .missing { outline:1px solid #8a5a2b; }
    .topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; }
    @media (max-width:900px){ .shell{grid-template-columns:1fr;} aside{border-right:0;border-bottom:1px solid var(--line);} .row{grid-template-columns:1fr;} }
  </style>
</head>
<body>
<div class="shell">
  <aside>
    <h1>AZ Admin</h1>
    <nav>
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
      <a href="{{ route('admin.content.edit') }}" class="{{ request()->routeIs('admin.content.*') ? 'active' : '' }}">Site content</a>
      <a href="{{ route('admin.projects.index') }}" class="{{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">Projects</a>
      <a href="{{ route('admin.resume.edit') }}" class="{{ request()->routeIs('admin.resume.*') ? 'active' : '' }}">Resume</a>
      <a href="{{ route('admin.seo.edit') }}" class="{{ request()->routeIs('admin.seo.*') ? 'active' : '' }}">SEO</a>
      <a href="{{ route('admin.media.index') }}" class="{{ request()->routeIs('admin.media.*') ? 'active' : '' }}">Media</a>
      <a href="{{ route('admin.export.json') }}" target="_blank">Export JSON</a>
      <a href="{{ route('admin.preview') }}" target="_blank">Preview draft</a>
      <a href="{{ url('/') }}" target="_blank">View site</a>
    </nav>
    <form method="post" action="{{ route('admin.logout') }}" style="margin-top:18px">
      @csrf
      <button class="btn-secondary" type="submit">Log out</button>
    </form>
  </aside>
  <main>
    <div class="topbar">
      <h2 style="margin:0;font-size:22px">@yield('heading', 'Admin')</h2>
      <form method="post" action="{{ route('admin.publish') }}">
        @csrf
        <input type="hidden" name="label" value="Manual publish {{ now()->format('Y-m-d H:i') }}">
        <button type="submit">Publish live</button>
      </form>
    </div>
    @if (session('status'))
      <div class="flash">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="flash flash-error">
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
      </div>
    @endif
    @yield('content')
  </main>
</div>
</body>
</html>
