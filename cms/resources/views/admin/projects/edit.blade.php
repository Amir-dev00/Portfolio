@extends('admin.layouts.app')
@section('title', $project->exists ? 'Edit project' : 'New project')
@section('heading', $project->exists ? 'Edit project' : 'New project')
@section('content')
@php
  $en = $project->relationLoaded('translations') ? $project->translation('en') : null;
  $fa = $project->relationLoaded('translations') ? $project->translation('fa') : null;
  $techs = $project->relationLoaded('technologies') ? $project->technologies->pluck('name')->implode(', ') : '';
@endphp
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
    <div><label>Cover media ID</label>
      <select name="cover_media_id">
        <option value="">—</option>
        @foreach ($media as $m)
          <option value="{{ $m->id }}" @selected(old('cover_media_id', $project->cover_media_id)==$m->id)>#{{ $m->id }} {{ $m->original_name }}</option>
        @endforeach
      </select>
    </div>
    <div><label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $project->is_featured))> Featured</label></div>
    <div style="grid-column:1/-1"><label>Technologies (comma-separated)</label><input name="technologies" value="{{ old('technologies', $techs) }}"></div>
  </div>
  <div class="card">
    <h3>English copy</h3>
    <label>Short</label><textarea name="en_short">{{ old('en_short', $en->short_description ?? '') }}</textarea>
    <label>Full</label><textarea name="en_full">{{ old('en_full', $en->full_description ?? '') }}</textarea>
    <label>Role</label><input name="en_role" value="{{ old('en_role', $en->role ?? '') }}">
  </div>
  <div class="card">
    <h3>Persian copy</h3>
    <label>Short</label><textarea name="fa_short" dir="rtl">{{ old('fa_short', $fa->short_description ?? '') }}</textarea>
    <label>Full</label><textarea name="fa_full" dir="rtl">{{ old('fa_full', $fa->full_description ?? '') }}</textarea>
    <label>Role</label><input name="fa_role" dir="rtl" value="{{ old('fa_role', $fa->role ?? '') }}">
    <label>Category label</label><input name="fa_category" dir="rtl" value="{{ old('fa_category', $fa->category_label ?? '') }}">
  </div>
  <button type="submit">Save draft</button>
</form>
@endsection
