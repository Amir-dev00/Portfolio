@extends('admin.layouts.app')
@section('title','SEO')
@section('heading','SEO')
@section('content')
<form method="post" action="{{ route('admin.seo.update') }}">
  @csrf
  @foreach ($entries->groupBy(fn($e) => $e->scope.'|'.$e->scope_key) as $groupKey => $rows)
    @php [$scope,$key] = explode('|', $groupKey, 2); @endphp
    <div class="card">
      <h3>{{ strtoupper($scope) }} {{ $key !== '' ? '— '.$key : '' }}</h3>
      <div class="row">
      @foreach ($rows as $entry)
        <div>
          <h4>{{ strtoupper($entry->locale) }}</h4>
          <input type="hidden" name="seo[{{ $entry->id }}][_]" value="1">
          <label>Title</label>
          <input name="seo[{{ $entry->id }}][title]" value="{{ $entry->title }}">
          <label>Description</label>
          <textarea name="seo[{{ $entry->id }}][description]">{{ $entry->description }}</textarea>
          <label>OG image path</label>
          <input name="seo[{{ $entry->id }}][og_image]" value="{{ $entry->og_image }}">
          <label>Robots</label>
          <input name="seo[{{ $entry->id }}][robots]" value="{{ $entry->robots }}">
          <div class="card" style="margin-top:10px;background:#10141c">
            <div class="badge">Search preview</div>
            <div style="color:#8ab4f8;font-size:14px">{{ $entry->title }}</div>
            <div style="color:#3c8f5a;font-size:12px">{{ url($entry->locale === 'fa' ? '/fa' : '/') }}</div>
            <div style="color:#9aa6b2;font-size:12px">{{ \Illuminate\Support\Str::limit($entry->description, 150) }}</div>
          </div>
        </div>
      @endforeach
      </div>
    </div>
  @endforeach
  <button type="submit">Save SEO draft</button>
</form>
@endsection
