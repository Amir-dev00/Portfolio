@extends('admin.layouts.app')
@section('title','Site content')
@section('heading','Site content')
@section('content')
<form method="post" action="{{ route('admin.content.update') }}">
  @csrf
  <div class="card">
    <h3>Profile & social</h3>
    <div class="row">
      <div><label>Profile name</label><input name="profile[name]" value="{{ $profile['name'] ?? '' }}"></div>
      <div><label>Profile photo path</label><input name="profile[photo]" value="{{ $profile['photo'] ?? '' }}"></div>
      <div><label>Email</label><input name="social[email]" value="{{ $social['email'] ?? '' }}"></div>
      <div><label>LinkedIn</label><input name="social[linkedin]" value="{{ $social['linkedin'] ?? '' }}"></div>
      <div><label>GitHub</label><input name="social[github]" value="{{ $social['github'] ?? '' }}"></div>
      <div><label>Telegram</label><input name="social[telegram]" value="{{ $social['telegram'] ?? '' }}"></div>
      <div><label>Featured project slugs (comma-separated)</label><input name="featured_slugs" value="{{ implode(',', $featured ?? []) }}"></div>
    </div>
  </div>

  @foreach ($grouped as $group => $entries)
    <div class="card">
      <h3>{{ $group }}</h3>
      @foreach ($entries as $entry)
        <div class="row" style="margin-bottom:10px">
          <div>
            <label>{{ $entry->key }} (EN) @if(!filled($entry->value_en))<span class="badge warn">missing</span>@endif</label>
            <textarea name="entries[{{ $entry->id }}][en]" class="{{ !filled($entry->value_en) ? 'missing' : '' }}">{{ $entry->value_en }}</textarea>
          </div>
          <div>
            <label>{{ $entry->key }} (FA) @if(!filled($entry->value_fa))<span class="badge warn">missing</span>@endif</label>
            <textarea name="entries[{{ $entry->id }}][fa]" dir="rtl" class="{{ !filled($entry->value_fa) ? 'missing' : '' }}">{{ $entry->value_fa }}</textarea>
          </div>
        </div>
      @endforeach
    </div>
  @endforeach

  <button type="submit">Save draft</button>
</form>
@endsection
