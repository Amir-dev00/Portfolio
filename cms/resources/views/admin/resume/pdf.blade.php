<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
    h1 { font-size: 22px; margin: 0 0 6px; }
    h2 { font-size: 14px; margin: 18px 0 6px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
    p { margin: 0 0 8px; line-height: 1.45; }
  </style>
</head>
<body>
  <h1>{{ $name }}</h1>
  <p>{{ $fields['headline'] ?? '' }}</p>
  <h2>{{ $locale === 'fa' ? 'خلاصه' : 'Summary' }}</h2>
  <p>{{ $fields['summary'] ?? '' }}</p>
  <h2>{{ $locale === 'fa' ? 'مهارت‌ها' : 'Skills' }}</h2>
  <p>{{ $fields['skills'] ?? '' }}</p>
  <h2>{{ $locale === 'fa' ? 'تجربه' : 'Experience' }}</h2>
  <p>{!! nl2br(e($fields['experience'] ?? '')) !!}</p>
  <h2>{{ $locale === 'fa' ? 'تحصیلات' : 'Education' }}</h2>
  <p>{!! nl2br(e($fields['education'] ?? '')) !!}</p>
</body>
</html>
