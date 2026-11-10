{{--
  Public site entry view. Every URL (/, /work, /about, /contact, /work/{slug}, /fa/*)
  renders the same SPA shell so client-side transitions keep working; the server marks
  the active view and fills it with published CMS content for direct loads and SEO.
--}}
@extends('layouts.app')

@php
  $assetUrl = static function ($path, string $fallback) {
      $path = trim((string) $path);
      if ($path === '') {
          return asset($fallback);
      }
      return preg_match('#^https?://#i', $path) ? $path : asset(ltrim($path, '/'));
  };

  // Versioned asset URL so browsers pick up new CSS/JS right after a deploy.
  $assetV = static function (string $path): string {
      $file = public_path($path);
      return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
  };

  $cvHref = $assetUrl($cvUrl ?? '', 'assets/cv/Amirhossein-Zamanzade-CV.pdf');
  $activeView = $viewName ?? 'home';

  $social = array_merge([
      'email' => 'itsamirzamani@gmail.com',
      'linkedin' => 'https://linkedin.com/in/amirhossein-zamanzade',
      'github' => 'https://github.com/Amir-dev00',
      'telegram' => 'https://t.me/Amir_dev00',
      'telegram_handle' => '@Amir_dev00',
  ], array_filter((array) ($social ?? []), fn ($v) => is_string($v) && trim($v) !== ''));

  $profilePhoto = $assetUrl(($profile ?? [])['photo'] ?? '', 'assets/img/amir/profile.jpg');
@endphp

@section('content')
          @include('pages.home')

          @include('pages.work')

          @include('pages.project')

          @include('pages.about')

          @include('pages.contact')
@endsection
