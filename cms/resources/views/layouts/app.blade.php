<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="az-preloader-open az-lang-{{ $locale }}" data-az-lang="{{ $locale }}">
  
<!-- Mirrored from https://artemsemkin.com/rhye/html/index.html by HTTrack Website Copier/3.x [XR&CO], Mon, 09 Nov 2026 08:30:04 GMT -->
<head>
    @php
      // Cache-busted public assets (same helper the SPA shell uses for CSS/JS).
      if (! isset($assetV) || ! is_callable($assetV)) {
          $assetV = static function (string $path): string {
              $file = public_path($path);

              return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
          };
      }
      $azLogoSrc = $assetV('assets/img/amir/az-monogram-blue.webp');
    @endphp
    <meta charset="utf-8"/>
    <meta http-equiv="x-ua-compatible" content="ie=edge"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>{{ $seo['title'] ?? ($strings['title.base'] ?? 'Portfolio') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover"/>
    <meta content="telephone=no" name="format-detection"/>
    <meta name="HandheldFriendly" content="true"/>
    <meta name="description" content="{{ $seo['description'] ?? '' }}"/>
    <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}"/>
    <link rel="canonical" href="{{ $canonical }}"/>
    <link rel="alternate" hreflang="en" href="{{ $hreflang['en'] }}"/>
    <link rel="alternate" hreflang="fa" href="{{ $hreflang['fa'] }}"/>
    <link rel="alternate" hreflang="x-default" href="{{ $hreflang['en'] }}"/>
    <meta property="og:title" content="{{ $seo['title'] ?? '' }}"/>
    <meta property="og:description" content="{{ $seo['description'] ?? '' }}"/>
    <meta property="og:image" content="{{ url($seo['og_image'] ?? 'assets/img/amir/az-monogram-blue.webp') }}"/>
    <meta property="og:url" content="{{ $canonical }}"/>
    <meta property="og:type" content="{{ ($viewName ?? '') === 'project' ? 'article' : 'website' }}"/>
    <meta property="og:locale" content="{{ $locale === 'fa' ? 'fa_IR' : 'en_US' }}"/>
    <meta property="og:locale:alternate" content="{{ $locale === 'fa' ? 'en_US' : 'fa_IR' }}"/>
    <meta name="twitter:card" content="summary_large_image"/>
    <script>
      /* Open before any stylesheet/script so cached reloads still paint the AZ loader. */
      document.documentElement.classList.add("az-preloader-open", "az-loading");
      window.__AZ_SERVER_LANG__ = @json($locale);
</script>
    {{-- Always preload Manrope (preloader + EN). Estedad only when the first paint is FA. --}}
    <link rel="preload" href="{{ asset('assets/fonts/manrope/manrope-variable.woff2') }}" as="font" type="font/woff2" crossorigin/>
    @if (($locale ?? 'en') === 'fa')
      <link rel="preload" href="{{ asset('assets/fonts/estedad/estedad-variable.woff2') }}" as="font" type="font/woff2" crossorigin/>
    @endif
    {{-- Critical path for first usable loader paint (before Rhye / feature CSS). --}}
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-tokens.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-preloader.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/typography.css') }}"/>
    <!-- Template Vendor's Stylesheet -->
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/vendor.css') }}"/>
    <!-- Template Main Stylesheet -->
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/main.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/navbar-glass.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-profile.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-app.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-i18n.css') }}"/>
    {{-- Guide CSS is small and needed for the idle homepage sprite. --}}
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-guide.css') }}"/>
    {{-- Noncritical feature CSS: fetch without blocking first paint. --}}
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-home-arrows.css') }}" media="print" onload="this.media='all'"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-booking.css') }}" media="print" onload="this.media='all'"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-assistant.css') }}" media="print" onload="this.media='all'"/>
    <noscript>
      <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-home-arrows.css') }}"/>
      <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-booking.css') }}"/>
      <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-assistant.css') }}"/>
    </noscript>
    {{-- Material Icons are not needed for the loader / first view; load after paint. --}}
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css2?family=Material+Icons&amp;display=swap" media="print" onload="this.media='all'"/>
    @if(($viewName ?? 'home') === 'home')
      <link rel="preload" as="image" href="{{ asset('assets/img/amir/home/home-mobile-bg.webp') }}" type="image/webp" media="(max-width: 767px)"/>
      <link rel="preload" as="image" href="{{ asset('assets/img/amir/home/home-bg-01.webp') }}" type="image/webp" media="(min-width: 768px)"/>
      @php
        $atlasFile = public_path('assets/img/amir/az-companion-atlas.webp');
        $atlasSrc = asset('assets/img/amir/az-companion-atlas.webp').(is_file($atlasFile) ? '?v='.filemtime($atlasFile) : '');
      @endphp
      <link rel="preload" as="image" href="{{ $atlasSrc }}" type="image/webp"/>
    @endif
    <link rel="preload" href="{{ $azLogoSrc }}" as="image" type="image/webp"/>
    <link rel="preload" href="{{ $profilePhoto ?? asset('assets/img/amir/profile.webp') }}" as="image" type="image/webp"/>
    <style>
      /* While open, beat Rhye's early inline display:none on cached reloads. */
      html.az-preloader-open #js-preloader.az-preloader--ring{
        position:fixed!important;inset:0!important;z-index:99999!important;
        display:flex!important;align-items:stretch!important;justify-content:stretch!important;
        width:100%!important;height:100%!important;min-height:100vh!important;min-height:100dvh!important;
        background:var(--az-bg,#0b1220)!important;opacity:1!important;visibility:visible!important;
        pointer-events:auto!important;overflow:hidden!important;
      }
      html.az-preloader-open #js-preloader.az-preloader--ring .preloader__content.az-preloader__layout{
        position:relative!important;inset:auto!important;top:auto!important;left:auto!important;
        transform:none!important;width:100%!important;height:100%!important;max-width:none!important;max-height:none!important;
        display:flex!important;flex-direction:column!important;opacity:1!important;visibility:visible!important;
      }
      /* Blade-first waiting motion if az-preloader.css is still in flight. */
      html.az-loading:not(.az-preloader-js) #js-preloader .az-preloader__ring-fill{
        animation:az-boot-wait-sweep 2.4s cubic-bezier(.45,.05,.55,.95) infinite;
      }
      html.az-loading:not(.az-preloader-js) #js-preloader .az-preloader__ring-head{
        opacity:1;animation:az-boot-wait-spin 2.4s linear infinite;
      }
      @keyframes az-boot-wait-sweep{0%{stroke-dashoffset:92}50%{stroke-dashoffset:74}100%{stroke-dashoffset:92}}
      @keyframes az-boot-wait-spin{to{transform:rotate(360deg)}}
      @media (prefers-reduced-motion:reduce){
        html.az-loading:not(.az-preloader-js) #js-preloader .az-preloader__ring-fill,
        html.az-loading:not(.az-preloader-js) #js-preloader .az-preloader__ring-head{animation:none!important}
      }
      .logo-wordmark{display:inline-block;font-family:var(--font-en),"Manrope",sans-serif;font-size:15px;font-weight:700;letter-spacing:.1em;line-height:20px;white-space:nowrap}
      .logo__img-primary.logo-wordmark{color:#111}
      .logo__img-secondary.logo-wordmark{color:#fff}
    </style>
    <!-- Favicons & App Icons (regenerated from az-monogram-blue.webp) -->
    <link rel="apple-touch-icon" sizes="57x57" href="{{ $assetV('assets/img/content/apple-icon-57x57.png') }}"/>
    <link rel="apple-touch-icon" sizes="60x60" href="{{ $assetV('assets/img/content/apple-icon-60x60.png') }}"/>
    <link rel="apple-touch-icon" sizes="72x72" href="{{ $assetV('assets/img/content/apple-icon-72x72.png') }}"/>
    <link rel="apple-touch-icon" sizes="76x76" href="{{ $assetV('assets/img/content/apple-icon-76x76.png') }}"/>
    <link rel="apple-touch-icon" sizes="114x114" href="{{ $assetV('assets/img/content/apple-icon-114x114.png') }}"/>
    <link rel="apple-touch-icon" sizes="120x120" href="{{ $assetV('assets/img/content/apple-icon-120x120.png') }}"/>
    <link rel="apple-touch-icon" sizes="144x144" href="{{ $assetV('assets/img/content/apple-icon-144x144.png') }}"/>
    <link rel="apple-touch-icon" sizes="152x152" href="{{ $assetV('assets/img/content/apple-icon-152x152.png') }}"/>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $assetV('assets/img/content/apple-icon-180x180.png') }}"/>
    <link rel="icon" type="image/png" sizes="192x192" href="{{ $assetV('assets/img/content/android-icon-192x192.png') }}"/>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $assetV('assets/img/content/favicon-32x32.png') }}"/>
    <link rel="icon" type="image/png" sizes="96x96" href="{{ $assetV('assets/img/content/favicon-96x96.png') }}"/>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $assetV('assets/img/content/favicon-16x16.png') }}"/>
    <link rel="manifest" href="{{ asset('assets/img/content/manifest.json') }}"/>
    <meta name="msapplication-TileColor" content="#0b1c2c"/>
    <meta name="msapplication-TileImage" content="{{ asset('assets/img/content/ms-icon-144x144.png') }}"/>
    <meta name="theme-color" content="#0b1c2c"/>
    <link rel="icon" type="image/png" href="{{ $assetV('assets/img/amir/favicon.png') }}"/>
  </head>
  <body>
    <div data-barba="wrapper">
      @include('components.site.preloader')
      {{-- Take over the ring ASAP so milestones start before vendor.js (~1.2MB) downloads. --}}
      <script src="{{ $assetV('js/az-preloader.js') }}"></script>
      @include('components.site.language-switcher')
      @include('components.site.guide')
      @include('components.site.chrome')
      @include('components.site.header')
      @include('components.site.navbar')
      <!-- PAGE MAIN -->
      <div class="js-smooth-scroll bg-dark-1" id="page-wrapper" data-barba="container">
        <main class="page-wrapper__content az-app" id="az-app">
@yield('content')
        </main>
        <!-- PAGE FOOTER -->
        <!-- - PAGE FOOTER -->
      </div>
      <!-- - PAGE MAIN -->
    </div>
    <canvas id="js-webgl"></canvas>
    @include('components.site.photoswipe')
    @include('components.site.shaders')
    @include('components.site.scripts')
</body>

<!-- Mirrored from https://artemsemkin.com/rhye/html/index.html by HTTrack Website Copier/3.x [XR&CO], Mon, 09 Nov 2026 08:30:27 GMT -->
</html>
