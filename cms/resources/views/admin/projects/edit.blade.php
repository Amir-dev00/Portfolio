@extends('admin.layouts.app')
@section('title', __($project->exists ? 'Edit project' : 'New project'))
@section('heading', __($project->exists ? 'Edit project' : 'New project'))
@section('content')
@php
  $en = $project->relationLoaded('translations') ? $project->translation('en') : null;
  $fa = $project->relationLoaded('translations') ? $project->translations->firstWhere('locale', 'fa') : null;
  $techs = $project->relationLoaded('technologies') ? $project->technologies->pluck('name')->implode(', ') : '';
@endphp
<div class="admin-pills">
  <a class="btn btn-secondary" href="{{ route('admin.projects.index') }}">{{ __('Back to projects') }}</a>
  @if($project->exists && !$project->archived_at)
    <a class="btn btn-secondary" href="{{ route('admin.seo.edit', ['target' => 'project:'.$project->slug]) }}">{{ __('Edit SEO') }}</a>
    <a class="btn btn-secondary" href="{{ route('project', ['slug' => $project->slug, 'preview' => 1]) }}" target="_blank" rel="noopener">{{ __('Preview English') }}</a>
    <a class="btn btn-secondary" href="{{ route('fa.project', ['slug' => $project->slug, 'preview' => 1]) }}" target="_blank" rel="noopener">{{ __('Preview Persian') }}</a>
  @endif
</div>
<form method="post" action="{{ $project->exists ? route('admin.projects.update', $project) : route('admin.projects.store') }}">
  @csrf
  @if($project->exists) @method('PUT') @endif
  <div class="card row">
    <div><label>{{ __('Title') }}</label><input name="title" value="{{ old('title', $project->title) }}" required></div>
    <div><label>{{ __('Slug') }}</label><input name="slug" value="{{ old('slug', $project->slug) }}"></div>
    <div><label>{{ __('Category') }}</label><input name="category" value="{{ old('category', $project->category) }}"></div>
    <div><label>{{ __('Year') }}</label><input name="year" value="{{ old('year', $project->year) }}"></div>
    <div><label>{{ __('Live URL') }}</label><input name="live_url" value="{{ old('live_url', $project->live_url) }}"></div>
    <div><label>{{ __('GitHub URL') }}</label><input name="github_url" value="{{ old('github_url', $project->github_url) }}"></div>
    <div><label>{{ __('Sort order') }}</label><input type="number" name="sort_order" value="{{ old('sort_order', $project->sort_order ?? 0) }}"></div>
    <div><label for="project-cover">{{ __('Cover from media library') }}</label>
      <select id="project-cover" name="cover_media_id">
        <option value="">—</option>
        @foreach ($media as $m)
          <option value="{{ $m->id }}" @selected(old('cover_media_id', $project->cover_media_id)==$m->id)>#{{ $m->id }} {{ $m->original_name }}</option>
        @endforeach
      </select>
    </div>
    <div><label>{{ __('Source / case study URL') }}<input name="source" type="url" value="{{ old('source', $details['source'] ?? '') }}"></label></div>
    <div><label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $project->is_featured))> {{ __('Featured') }}</label></div>
    <div style="grid-column:1/-1"><label>{{ __('Technologies (comma-separated)') }}</label><input name="technologies" value="{{ old('technologies', $techs) }}"></div>
  </div>
  <section class="card">
    <h3>{{ __('Cover & gallery') }}</h3>
    <p class="admin-hint">{{ __('Choose uploaded images or keep the bundled paths below.') }} <a href="{{ route('admin.media.index') }}" target="_blank" rel="noopener">{{ __('Upload images in Media') }}</a>.</p>
    <div class="row">
      <label>{{ __('Bundled cover path') }}<input name="cover" value="{{ old('cover', $details['cover'] ?? '') }}"></label>
      <label>{{ __('Bundled gallery paths · one per line') }}<textarea name="gallery">{{ old('gallery', implode("\n", $details['gallery'] ?? [])) }}</textarea></label>
    </div>
    @if(!empty($details['gallery']))
      <div class="admin-thumbs">
        @foreach($details['gallery'] as $path)
          <a href="{{ asset(ltrim($path, '/')) }}" target="_blank" rel="noopener"><img src="{{ asset(ltrim($path, '/')) }}" alt="{{ basename($path) }}" loading="lazy"></a>
        @endforeach
      </div>
    @endif
    <input type="hidden" name="gallery_media_present" value="1">
    <label>{{ __('Additional images from Media') }}
      <select name="gallery_media[]" multiple size="6">
        @foreach($media as $item)
          <option value="{{ $item->id }}" @selected(in_array($item->id, old('gallery_media', $project->exists ? $project->media->where('role', 'gallery')->pluck('media_asset_id')->all() : [])))>{{ $item->original_name }}</option>
        @endforeach
      </select>
    </label>
  </section>
  <div class="card">
    <h3>{{ __('English copy') }}</h3>
    <label>{{ __('Short') }}</label><textarea maxlength="255" name="en_short">{{ old('en_short', $en->short_description ?? '') }}</textarea>
    <label>{{ __('Full') }}</label><textarea name="en_full">{{ old('en_full', $en->full_description ?? '') }}</textarea>
    <label>{{ __('Role') }}</label><input name="en_role" value="{{ old('en_role', $en->role ?? '') }}">
  </div>
  <div class="card">
    <h3>{{ __('Persian copy') }}</h3>
    <label>{{ __('Short') }}</label><textarea maxlength="255" name="fa_short" dir="rtl">{{ old('fa_short', $fa->short_description ?? '') }}</textarea>
    <label>{{ __('Full') }}</label><textarea name="fa_full" dir="rtl">{{ old('fa_full', $fa->full_description ?? '') }}</textarea>
    <label>{{ __('Role') }}</label><input name="fa_role" dir="rtl" value="{{ old('fa_role', $fa->role ?? '') }}">
    <label>{{ __('Category label') }}</label><input name="fa_category" dir="rtl" value="{{ old('fa_category', $fa->category_label ?? '') }}">
  </div>
  <div class="row">
    @foreach(['en' => 'English', 'fa' => 'Persian'] as $locale => $label)
      <section class="card">
        <h3>{{ __(':language case study', ['language' => __($label)]) }}</h3>
        @if($locale === 'fa')
          <label>{{ __('Persian title') }}<input name="details[fa][title]" dir="rtl" value="{{ old('details.fa.title', $details['fa']['title'] ?? '') }}"></label>
        @endif
        <label>{{ __('Overview') }}<textarea name="details[{{ $locale }}][overview]" dir="{{ $locale === 'fa' ? 'rtl' : 'ltr' }}">{{ old('details.'.$locale.'.overview', $details[$locale]['overview'] ?? '') }}</textarea></label>
        <label>{{ __('Key features · one per line') }}<textarea name="details[{{ $locale }}][features]" dir="{{ $locale === 'fa' ? 'rtl' : 'ltr' }}">{{ old('details.'.$locale.'.features', implode("\n", $details[$locale]['features'] ?? [])) }}</textarea></label>
        <label>{{ __('Design and implementation') }}<textarea name="details[{{ $locale }}][approach]" dir="{{ $locale === 'fa' ? 'rtl' : 'ltr' }}">{{ old('details.'.$locale.'.approach', $details[$locale]['approach'] ?? '') }}</textarea></label>
      </section>
    @endforeach
  </div>
  <div class="admin-save"><button type="submit">{{ __('Save draft') }}</button></div>
</form>
@if($project->exists)
  <div class="card">@include('admin.projects.delete')</div>
@endif
@endsection
