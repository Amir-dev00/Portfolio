@extends('admin.layouts.app')
@section('title',__('Media'))
@section('heading',__('Media library'))
@section('content')
<div class="card">
  <form method="post" action="{{ route('admin.media.upload') }}" enctype="multipart/form-data">
    @csrf
    <div class="row">
      <div><label>{{ __('File (image/PDF)') }}</label><input type="file" name="file" required></div>
      <div><label>{{ __('Alt EN') }}</label><input name="alt_en"></div>
      <div><label>{{ __('Alt FA') }}</label><input name="alt_fa" dir="rtl"></div>
    </div>
    <p style="margin-top:10px"><button type="submit">{{ __('Upload to private drafts') }}</button></p>
  </form>
</div>
<form method="get" class="card">
  <div class="row">
    <label>{{ __('Search by filename') }}<input name="q" type="search" value="{{ is_string(request('q')) ? request('q') : '' }}"></label>
    <label>{{ __('File type') }}<select name="type">
      <option value="">{{ __('All') }}</option>
      <option value="images" @selected(request('type') === 'images')>{{ __('Images') }}</option>
      <option value="pdf" @selected(request('type') === 'pdf')>{{ __('PDFs') }}</option>
    </select></label>
  </div>
  <div class="admin-pills"><button class="btn-secondary">{{ __('Filter') }}</button><a href="{{ route('admin.media.index') }}">{{ __('Clear') }}</a></div>
</form>
<div class="card admin-table">
<table>
  <thead><tr><th>{{ __('ID') }}</th><th>{{ __('File') }}</th><th>{{ __('Published') }}</th><th>{{ __('Used by') }}</th><th>{{ __('Alt') }}</th></tr></thead>
  <tbody>
  @forelse ($media as $item)
    <tr>
      <td>#{{ $item->id }}</td>
      <td><a href="{{ route('admin.media.raw', $item) }}" target="_blank">{{ $item->original_name }}</a><div class="badge">{{ $item->mime }}</div></td>
      <td>{{ __($item->is_published ? 'yes' : 'draft') }}<div class="badge">{{ $item->public_path }}</div></td>
      <td>{{ implode(', ', $item->used_by ?? []) }}</td>
      <td>
        <form method="post" action="{{ route('admin.media.update', $item) }}">
          @csrf @method('PUT')
          <input name="alt_en" value="{{ $item->alt_en }}" placeholder="{{ __('Alt EN') }}">
          <input name="alt_fa" value="{{ $item->alt_fa }}" placeholder="{{ __('Alt FA') }}" dir="rtl">
          <button class="btn-secondary" type="submit">{{ __('Save') }}</button>
        </form>
      </td>
    </tr>
  @empty
    <tr><td colspan="5">{{ __('No files match this filter.') }}</td></tr>
  @endforelse
  </tbody>
</table>
@if($media->hasPages())
  <nav class="admin-pills" aria-label="{{ __('Media pages') }}">
    @if($media->previousPageUrl())<a class="btn btn-secondary" href="{{ $media->previousPageUrl() }}">{{ __('Previous') }}</a>@endif
    <span class="admin-hint">{{ __('Page :page of :total', ['page' => $media->currentPage(), 'total' => $media->lastPage()]) }}</span>
    @if($media->nextPageUrl())<a class="btn btn-secondary" href="{{ $media->nextPageUrl() }}">{{ __('Next') }}</a>@endif
  </nav>
@endif
</div>
@endsection
