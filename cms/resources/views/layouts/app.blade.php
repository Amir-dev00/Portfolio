<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="az-laravel az-lang-{{ $locale }}" data-az-lang="{{ $locale }}" data-az-route="{{ ($viewName ?? 'home') === 'project' ? 'work' : ($viewName ?? 'home') }}">
  
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
      $azLogoSrc = $assetV(ltrim($profile['logo'] ?? 'assets/img/amir/az-monogram-display.webp', '/'));
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
    <link rel="stylesheet" href="{{ $assetV('css/az-shell.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-tokens.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-preloader.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/typography.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/navbar-glass.css') }}" data-az-site-styles/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-profile.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-app.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-i18n.css') }}"/>
    {{-- Noncritical feature CSS: fetch without blocking first paint. --}}
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-home-arrows.css') }}" media="print" onload="this.media='all'"/>
    <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-assistant.css') }}" media="print" onload="this.media='all'"/>
    <noscript>
      <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-home-arrows.css') }}"/>
      <link rel="stylesheet" type="text/css" href="{{ $assetV('css/az-assistant.css') }}"/>
    </noscript>
    @if(($viewName ?? 'home') === 'home')
      <link rel="preload" as="image" href="{{ $assetV('assets/img/amir/home/home-mobile-bg-01-640.webp') }}" type="image/webp" fetchpriority="high" media="(max-width: 520px)"/>
      <link rel="preload" as="image" href="{{ asset('assets/img/amir/home/home-mobile-bg-01.webp') }}" type="image/webp" fetchpriority="high" media="(520px < width <= 767px)"/>
      <link rel="preload" as="image" href="{{ asset('assets/img/amir/home/home-bg-01.webp') }}" type="image/webp" fetchpriority="high" media="(width > 767px)"/>
    @endif
    <link rel="preload" href="{{ $azLogoSrc }}" as="image"/>
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
    <div>
      @include('components.site.preloader')
      <script src="{{ $assetV('js/az-preloader.js') }}" async
        onerror="document.documentElement.classList.remove('az-preloader-open', 'az-loading')"></script>
      @include('components.site.language-switcher')
      @include('components.site.header')
      @include('components.site.navbar')
      <!-- PAGE MAIN -->
      <div class="js-smooth-scroll bg-dark-1" id="page-wrapper">
        <main class="page-wrapper__content az-app" id="az-app">
@yield('content')
        </main>
        <!-- PAGE FOOTER -->
        <!-- - PAGE FOOTER -->
      </div>
      <!-- - PAGE MAIN -->
    </div>
    <canvas id="js-webgl"></canvas>
    <div class="az-route-status" id="az-route-status" role="status" hidden>
      <span></span>
      <button type="button" hidden></button>
    </div>
    @include('components.site.welcome-tour')
    @include('components.site.shaders')
    @include('components.site.scripts')
</body>

<!-- Mirrored from https://artemsemkin.com/rhye/html/index.html by HTTrack Website Copier/3.x [XR&CO], Mon, 09 Nov 2026 08:30:27 GMT -->
</html>
