@extends('admin.layouts.app')
@section('title', $project->exists ? 'Edit project' : 'New project')
@section('heading', $project->exists ? 'Edit project' : 'New project')
@section('content')
@php
  $en = $project->relationLoaded('translations') ? $project->translation('en') : null;
  $fa = $project->relationLoaded('translations') ? $project->translations->firstWhere('locale', 'fa') : null;
  $techs = $project->relationLoaded('technologies') ? $project->technologies->pluck('name')->implode(', ') : '';
@endphp
<div class="admin-pills">
  <a class="btn btn-secondary" href="{{ route('admin.projects.index') }}">Back to projects</a>
  @if($project->exists && !$project->archived_at)
    <a class="btn btn-secondary" href="{{ route('admin.seo.edit', ['target' => 'project:'.$project->slug]) }}">Edit SEO</a>
    <a class="btn btn-secondary" href="{{ route('project', ['slug' => $project->slug, 'preview' => 1]) }}" target="_blank" rel="noopener">Preview English</a>
    <a class="btn btn-secondary" href="{{ route('fa.project', ['slug' => $project->slug, 'preview' => 1]) }}" target="_blank" rel="noopener">Preview Persian</a>
  @endif
</div>
<form method="post" action="{{ $project->exists ? route('admin.projects.update', $project) : route('admin.projects.store') }}">
  @csrf
  @if($project->exists) @method('PUT') @endif
  <div class="card row">
    <div><label>Title</label><input name="title" value="{{ old('title', $project->title) }}" required></div>
    <div><label>Slug</label><input name="slug" value="{{ old('slug', $project->slug) }}"></div>
    <div><label>Category</label><input name="category" value="{{ old('category', $project->category) }}"></div>
    <div><label>Year</label><input name="year" value="{{ old('year', $project->year) }}"></div>
    <div><label>Live URL</label><input name="live_url" value="{{ old('live_url', $project->live_url) }}"></div>
    <div><label>GitHub URL</label><input name="github_url" value="{{ old('github_url', $project->github_url) }}"></div>
    <div><label>Sort order</label><input type="number" name="sort_order" value="{{ old('sort_order', $project->sort_order ?? 0) }}"></div>
    <div><label for="project-cover">Cover from media library</label>
      <select id="project-cover" name="cover_media_id">
        <option value="">—</option>
        @foreach ($media as $m)
          <option value="{{ $m->id }}" @selected(old('cover_media_id', $project->cover_media_id)==$m->id)>#{{ $m->id }} {{ $m->original_name }}</option>
        @endforeach
      </select>
    </div>
    <div><label>Source / case study URL<input name="source" type="url" value="{{ old('source', $details['source'] ?? '') }}"></label></div>
    <div><label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $project->is_featured))> Featured</label></div>
    <div style="grid-column:1/-1"><label>Technologies (comma-separated)</label><input name="technologies" value="{{ old('technologies', $techs) }}"></div>
  </div>
  <section class="card">
    <h3>Cover &amp; gallery</h3>
    <p class="admin-hint">Choose uploaded images or keep the bundled paths below. <a href="{{ route('admin.media.index') }}" target="_blank" rel="noopener">Upload images in Media</a>.</p>
    <div class="row">
      <label>Bundled cover path<input name="cover" value="{{ old('cover', $details['cover'] ?? '') }}"></label>
      <label>Bundled gallery paths · one per line<textarea name="gallery">{{ old('gallery', implode("\n", $details['gallery'] ?? [])) }}</textarea></label>
    </div>
    @if(!empty($details['gallery']))
      <div class="admin-thumbs">
        @foreach($details['gallery'] as $path)
          <a href="{{ asset(ltrim($path, '/')) }}" target="_blank" rel="noopener"><img src="{{ asset(ltrim($path, '/')) }}" alt="{{ basename($path) }}" loading="lazy"></a>
        @endforeach
      </div>
    @endif
    <input type="hidden" name="gallery_media_present" value="1">
    <label>Additional images from Media
      <select name="gallery_media[]" multiple size="6">
        @foreach($media as $item)
          <option value="{{ $item->id }}" @selected(in_array($item->id, old('gallery_media', $project->exists ? $project->media->where('role', 'gallery')->pluck('media_asset_id')->all() : [])))>{{ $item->original_name }}</option>
        @endforeach
      </select>
    </label>
  </section>
  <div class="card">
    <h3>English copy</h3>
    <label>Short</label><textarea maxlength="255" name="en_short">{{ old('en_short', $en->short_description ?? '') }}</textarea>
    <label>Full</label><textarea name="en_full">{{ old('en_full', $en->full_description ?? '') }}</textarea>
    <label>Role</label><input name="en_role" value="{{ old('en_role', $en->role ?? '') }}">
  </div>
  <div class="card">
    <h3>Persian copy</h3>
    <label>Short</label><textarea maxlength="255" name="fa_short" dir="rtl">{{ old('fa_short', $fa->short_description ?? '') }}</textarea>
    <label>Full</label><textarea name="fa_full" dir="rtl">{{ old('fa_full', $fa->full_description ?? '') }}</textarea>
    <label>Role</label><input name="fa_role" dir="rtl" value="{{ old('fa_role', $fa->role ?? '') }}">
    <label>Category label</label><input name="fa_category" dir="rtl" value="{{ old('fa_category', $fa->category_label ?? '') }}">
  </div>
  <div class="row">
    @foreach(['en' => 'English', 'fa' => 'Persian'] as $locale => $label)
      <section class="card">
        <h3>{{ $label }} case study</h3>
        @if($locale === 'fa')
          <label>Persian title<input name="details[fa][title]" dir="rtl" value="{{ old('details.fa.title', $details['fa']['title'] ?? '') }}"></label>
        @endif
        <label>Overview<textarea name="details[{{ $locale }}][overview]" dir="{{ $locale === 'fa' ? 'rtl' : 'ltr' }}">{{ old('details.'.$locale.'.overview', $details[$locale]['overview'] ?? '') }}</textarea></label>
        <label>Key features · one per line<textarea name="details[{{ $locale }}][features]" dir="{{ $locale === 'fa' ? 'rtl' : 'ltr' }}">{{ old('details.'.$locale.'.features', implode("\n", $details[$locale]['features'] ?? [])) }}</textarea></label>
        <label>Design and implementation<textarea name="details[{{ $locale }}][approach]" dir="{{ $locale === 'fa' ? 'rtl' : 'ltr' }}">{{ old('details.'.$locale.'.approach', $details[$locale]['approach'] ?? '') }}</textarea></label>
      </section>
    @endforeach
  </div>
  <div class="admin-save"><button type="submit">Save draft</button></div>
</form>
@endsection
