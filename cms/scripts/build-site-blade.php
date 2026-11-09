<?php

$src = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'index.html';
$dst = dirname(__DIR__).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'app.blade.php';

$html = file_get_contents($src);

// Replace opening html tag
$html = preg_replace(
    '/<html[^>]*>/',
    '<html lang="{{ $locale }}" dir="{{ $dir }}" class="az-preloader-open az-lang-{{ $locale }}" data-az-lang="{{ $locale }}">',
    $html,
    1
);

// Title + meta description
$html = preg_replace(
    '/<title>.*?<\/title>/',
    '<title>{{ $seo[\'title\'] ?? ($strings[\'title.base\'] ?? \'Portfolio\') }}</title>',
    $html,
    1
);
$html = preg_replace(
    '/<meta name="description" content="[^"]*"\/>/',
    '<meta name="description" content="{{ $seo[\'description\'] ?? \'\' }}"/>'."\n".
    '    <meta name="robots" content="{{ $seo[\'robots\'] ?? \'index,follow\' }}"/>'."\n".
    '    <link rel="canonical" href="{{ $canonical }}"/>'."\n".
    '    <link rel="alternate" hreflang="en" href="{{ $hreflang[\'en\'] }}"/>'."\n".
    '    <link rel="alternate" hreflang="fa" href="{{ $hreflang[\'fa\'] }}"/>'."\n".
    '    <link rel="alternate" hreflang="x-default" href="{{ $hreflang[\'en\'] }}"/>'."\n".
    '    <meta property="og:title" content="{{ $seo[\'title\'] ?? \'\' }}"/>'."\n".
    '    <meta property="og:description" content="{{ $seo[\'description\'] ?? \'\' }}"/>'."\n".
    '    <meta property="og:image" content="{{ url($seo[\'og_image\'] ?? \'assets/img/amir/az-logo.webp\') }}"/>'."\n".
    '    <meta property="og:url" content="{{ $canonical }}"/>'."\n".
    '    <meta property="og:type" content="website"/>',
    $html,
    1
);

// Remove early lang restore that fights Blade locale; keep preloader class only.
$html = preg_replace(
    '/\/\* Restore EN\/FA before first paint[\s\S]*?\}\)\(\);\s*/',
    "window.__AZ_SERVER_LANG__ = @json(\$locale);\n",
    $html,
    1
);

// Inject bootstrap + CSRF before az-i18n.js
$inject = <<<'BLADE'
    <script>
      window.AZ_BOOTSTRAP = @json($bootstrap);
      window.AZ_CSRF_TOKEN = @json(csrf_token());
    </script>
BLADE;

$html = str_replace(
    '<script src="js/az-i18n.js"></script>',
    $inject."\n    <script src=\"{{ asset('js/az-i18n.js') }}\"></script>",
    $html
);

// Asset helper for local scripts/styles that are relative
$html = preg_replace_callback(
    '/(href|src)="((?:css|js|assets)\/[^"]+)"/',
    function ($m) {
        return $m[1].'="{{ asset(\''.$m[2].'\') }}"';
    },
    $html
);

// CV links use bootstrap cv when available
$html = preg_replace(
    '/href="assets\/cv\/Amirhossein-Zamanzade-CV\.pdf"/',
    'href="{{ $cvUrl ?: asset(\'assets/cv/Amirhossein-Zamanzade-CV.pdf\') }}"',
    $html
);

// Initial hash sync script before az-app
$html = str_replace(
    '<script src="{{ asset(\'js/az-app.js\') }}"></script>',
    <<<'BLADE'
<script>
      (function () {
        var boot = window.AZ_BOOTSTRAP || {};
        if (boot.initialHash && (!location.hash || location.hash === '#' || location.hash === '#home')) {
          if (boot.initialHash !== '#home' || location.pathname.replace(/\/$/, '') !== '' && location.pathname !== '/') {
            // Deep public URLs set the hash so the SPA opens the right view.
            if (boot.viewHint || boot.initialHash) {
              history.replaceState(null, '', boot.initialHash);
            }
          }
        }
        if (boot.initialHash && boot.initialHash !== '#home') {
          history.replaceState(null, '', boot.initialHash);
        }
      })();
    </script>
    <script src="{{ asset('js/az-app.js') }}"></script>
BLADE
    ,
    $html
);

// Escape Blade braces inside inline JS/shaders carefully — leave as-is; shaders use { } which Blade may parse.
// Wrap shader script bodies: convert to @verbatim blocks is hard. Use Blade {{-- raw --}} strategy:
// Replace <script id=...> ... </script> shader blocks with @verbatim
$html = preg_replace_callback(
    '/<script id="[^"]+"[^>]*>[\s\S]*?<\/script>/',
    function ($m) {
        return '@verbatim'."\n".$m[0]."\n".'@endverbatim';
    },
    $html
);

file_put_contents($dst, $html);
echo "Wrote {$dst} (".strlen($html)." bytes)\n";
