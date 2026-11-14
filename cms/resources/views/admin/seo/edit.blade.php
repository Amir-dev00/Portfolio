@extends('admin.layouts.app')
@section('title','SEO')
@section('heading','Search & sharing')
@section('content')
<form method="get" class="card">
  <label for="seo-target">Page or project</label>
  <select name="target" id="seo-target">
    <optgroup label="Pages">
      @foreach($pages as $page)
        <option value="{{ $page }}" @selected($target === $page)>{{ ucfirst($page) }}</option>
      @endforeach
    </optgroup>
    <optgroup label="Projects">
      @foreach($projects as $slug => $title)
        <option value="project:{{ $slug }}" @selected($target === 'project:'.$slug)>{{ $title }}</option>
      @endforeach
    </optgroup>
  </select>
  <div class="admin-pills"><button class="btn-secondary">Open editor</button></div>
</form>
<form method="post" action="{{ route('admin.seo.update') }}">
  @csrf
  <input type="hidden" name="target" value="{{ $target }}">
  <div class="row">
    @foreach(['en' => 'English', 'fa' => 'Persian'] as $locale => $language)
      @php($entry = $entries->get($locale))
      <section class="card">
        <h3>{{ $language }}</h3>
        <label>Search title<input name="seo[{{ $locale }}][title]" maxlength="190" dir="auto" value="{{ old("seo.$locale.title", $entry?->title) }}"></label>
        <label>Description<textarea name="seo[{{ $locale }}][description]" dir="auto">{{ old("seo.$locale.description", $entry?->description) }}</textarea></label>
        <label>Sharing image path or URL<input name="seo[{{ $locale }}][og_image]" value="{{ old("seo.$locale.og_image", $entry?->og_image) }}"></label>
        <label>Search indexing
          <select name="seo[{{ $locale }}][robots]">
            @foreach(['index,follow', 'noindex,follow', 'noindex,nofollow', 'index,nofollow'] as $option)
              <option @selected(old("seo.$locale.robots", $entry?->robots ?? 'index,follow') === $option)>{{ $option }}</option>
            @endforeach
          </select>
        </label>
        <div class="card" style="margin-top:12px;background:#10141c">
          <span class="badge">Saved preview</span>
          <p dir="auto" style="color:#8ab4f8">{{ $entry?->title ?: $label }}</p>
          <p class="admin-hint" style="overflow-wrap:anywhere">{{ $paths[$locale] }}</p>
          <p class="admin-hint" dir="auto">{{ $entry?->description }}</p>
        </div>
      </section>
    @endforeach
  </div>
  <div class="admin-save"><button type="submit">Save SEO draft</button></div>
</form>
@endsection
