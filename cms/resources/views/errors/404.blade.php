@php
  $isFa = str_starts_with(trim(request()->path(), '/'), 'fa');
  $homeUrl = url($isFa ? '/fa' : '/');
  $workUrl = url($isFa ? '/fa/work' : '/work');
@endphp
<!DOCTYPE html>
<html lang="{{ $isFa ? 'fa' : 'en' }}" dir="{{ $isFa ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <meta name="robots" content="noindex,follow"/>
  <title>{{ $isFa ? 'صفحه پیدا نشد' : 'Page not found' }} — Amirhossein Zamanzade</title>
  @php
    $azLogoFile = public_path('assets/img/amir/az-monogram-blue.webp');
    $azLogoSrc = asset('assets/img/amir/az-monogram-blue.webp').(is_file($azLogoFile) ? '?v='.filemtime($azLogoFile) : '');
    $azFavFile = public_path('assets/img/amir/favicon.png');
    $azFavSrc = asset('assets/img/amir/favicon.png').(is_file($azFavFile) ? '?v='.filemtime($azFavFile) : '');
  @endphp
  <link rel="icon" type="image/png" href="{{ $azFavSrc }}"/>
  <link rel="stylesheet" href="{{ asset('css/typography.css') }}"/>
  <style>
    :root { --az-bg: #0b0b0d; --az-ink: #f2f2f2; --az-mute: rgba(242,242,242,.62); --az-line: rgba(255,255,255,.18); }
    * { box-sizing: border-box; }
    html, body { margin: 0; min-height: 100%; }
    body {
      min-height: 100vh; min-height: 100dvh;
      display: grid; place-items: center; padding: 32px 20px;
      background: radial-gradient(120% 80% at 50% 0%, #1b1b20 0%, var(--az-bg) 60%), var(--az-bg);
      color: var(--az-ink); font-family: var(--font-body); text-align: center;
    }
    main { max-width: 560px; animation: az-rise .7s cubic-bezier(.2,.7,.2,1) both; }
    img { width: 72px; height: auto; opacity: .9; }
    .code { font-family: var(--font-display); font-size: clamp(64px, 18vw, 140px); letter-spacing: .04em; margin: 12px 0 0; line-height: 1; font-weight: 650; }
    h1 { font-family: var(--font-display); font-weight: 600; font-size: clamp(20px, 4vw, 28px); letter-spacing: .08em; text-transform: uppercase; margin: 12px 0 8px; }
    html[dir="rtl"] h1 { letter-spacing: 0; text-transform: none; }
    p { color: var(--az-mute); margin: 0 0 28px; line-height: 1.7; }
    nav { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    a { color: var(--az-ink); text-decoration: none; border: 1px solid var(--az-line); padding: 12px 22px; min-height: 44px; display: inline-flex; align-items: center; font-family: var(--font-ui); font-weight: 600; transition: background .2s, border-color .2s; }
    a:hover, a:focus-visible { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.4); }
    @keyframes az-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { main { animation: none; } }
  </style>
</head>
<body>
  <main>
    <img src="{{ $azLogoSrc }}" alt="AZ" width="720" height="720"/>
    <p class="code" aria-hidden="true">404</p>
    <h1>{{ $isFa ? 'صفحه پیدا نشد' : 'Page not found' }}</h1>
    <p>{{ $isFa ? 'صفحه‌ای که دنبالش هستید وجود ندارد یا جابه‌جا شده است.' : 'The page you are looking for does not exist or has moved.' }}</p>
    <nav>
      <a href="{{ $homeUrl }}">{{ $isFa ? 'بازگشت به خانه' : 'Back to home' }}</a>
      <a href="{{ $workUrl }}">{{ $isFa ? 'مشاهده کارها' : 'View work' }}</a>
    </nav>
  </main>
</body>
</html>
