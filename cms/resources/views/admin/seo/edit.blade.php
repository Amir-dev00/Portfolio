@extends('admin.layouts.app')
@section('title',__('SEO'))
@section('heading',__('Search & sharing'))
@section('content')
<form method="get" class="card">
  <label for="seo-target">{{ __('Page or project') }}</label>
  <select name="target" id="seo-target">
    <optgroup label="{{ __('Pages') }}">
      @foreach($pages as $page)
        <option value="{{ $page }}" @selected($target === $page)>{{ __(ucfirst($page)) }}</option>
      @endforeach
    </optgroup>
    <optgroup label="{{ __('Projects') }}">
      @foreach($projects as $slug => $title)
        <option value="project:{{ $slug }}" @selected($target === 'project:'.$slug)>{{ $title }}</option>
      @endforeach
    </optgroup>
  </select>
  <div class="admin-pills"><button class="btn-secondary">{{ __('Open editor') }}</button></div>
</form>
<form method="post" action="{{ route('admin.seo.update') }}">
  @csrf
  <input type="hidden" name="target" value="{{ $target }}">
  <div class="row">
    @foreach(['en' => 'English', 'fa' => 'Persian'] as $locale => $language)
      @php($entry = $entries->get($locale))
      <section class="card">
        <h3>{{ __($language) }}</h3>
        <label>{{ __('Search title') }}<input name="seo[{{ $locale }}][title]" maxlength="190" dir="auto" value="{{ old("seo.$locale.title", $entry?->title) }}"></label>
        <label>{{ __('Description') }}<textarea name="seo[{{ $locale }}][description]" dir="auto">{{ old("seo.$locale.description", $entry?->description) }}</textarea></label>
        <label>{{ __('Sharing image path or URL') }}<input name="seo[{{ $locale }}][og_image]" value="{{ old("seo.$locale.og_image", $entry?->og_image) }}"></label>
        <label>{{ __('Search indexing') }}
          <select name="seo[{{ $locale }}][robots]">
            @foreach(['index,follow' => 'Index and follow', 'noindex,follow' => 'Do not index; follow links', 'noindex,nofollow' => 'Do not index or follow links', 'index,nofollow' => 'Index without following links'] as $option => $optionLabel)
              <option value="{{ $option }}" @selected(old("seo.$locale.robots", $entry?->robots ?? 'index,follow') === $option)>{{ __($optionLabel) }}</option>
            @endforeach
          </select>
        </label>
        <div class="card" style="margin-top:12px;background:#10141c">
          <span class="badge">{{ __('Saved preview') }}</span>
          <p dir="auto" style="color:#8ab4f8">{{ $entry?->title ?: $label }}</p>
          <p class="admin-hint" style="overflow-wrap:anywhere">{{ $paths[$locale] }}</p>
          <p class="admin-hint" dir="auto">{{ $entry?->description }}</p>
        </div>
      </section>
    @endforeach
  </div>
  <div class="admin-save"><button type="submit">{{ __('Save SEO draft') }}</button></div>
</form>
@endsection
