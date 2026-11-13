@extends('admin.layouts.app')
@section('title', $entry ? 'Edit experience' : 'Add experience')
@section('heading', $entry ? 'Edit experience' : 'Add experience')
@section('content')
  @php($id = $entry ? substr($entry->key, strlen(\App\Models\SiteSetting::EXPERIENCE_PREFIX)) : null)
  <form class="card" method="post" action="{{ $entry ? route('admin.experience.update', $id) : route('admin.experience.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($entry)
      @method('PUT')
    @endif
    <div class="row">
      @foreach (['company' => 'Company name', 'role' => 'Job title'] as $field => $label)
        @foreach (['en' => 'English', 'fa' => 'Persian'] as $lang => $language)
          @php($name = $field.'_'.$lang)
          <div>
            <label for="{{ $name }}">{{ $label }} ({{ $language }})</label>
            <input id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $experience[$name] ?? '') }}"
              lang="{{ $lang }}" dir="{{ $lang === 'fa' ? 'rtl' : 'ltr' }}" maxlength="150" required>
          </div>
        @endforeach
      @endforeach
      <div>
        <label for="start_month">Start month (Gregorian)</label>
        <input type="month" id="start_month" name="start_month" value="{{ old('start_month', $experience['start_month'] ?? '') }}" placeholder="YYYY-MM" required>
      </div>
      <div>
        <label for="end_month">End month (Gregorian, blank if current)</label>
        <input type="month" id="end_month" name="end_month" value="{{ old('end_month', $experience['end_month'] ?? '') }}" placeholder="YYYY-MM">
      </div>
      <div>
        <label for="type">Employment type</label>
        <select id="type" name="type" required>
          <option value="">Select a type</option>
          @foreach ($types as $key => $labels)
            <option value="{{ $key }}" @selected(old('type', $experience['type'] ?? '') === $key)>{{ $labels['en'] }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="website">Company website</label>
        <input type="url" id="website" name="website" value="{{ old('website', $experience['website'] ?? '') }}" placeholder="https://example.com" maxlength="500">
      </div>
      <div>
        <label for="logo">Company logo (PNG, JPG or WebP, up to 2 MB)</label>
        <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
        @if ($logo)
          <img src="{{ route('admin.media.raw', $logo) }}" width="48" height="48" alt="" style="object-fit:contain;margin-top:8px">
          <label><input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo')) style="width:auto"> Remove current logo</label>
        @endif
      </div>
      @foreach (['en' => 'English', 'fa' => 'Persian'] as $lang => $language)
        @php($name = 'description_'.$lang)
        <div>
          <label for="{{ $name }}">Responsibilities and achievements ({{ $language }})</label>
          <textarea id="{{ $name }}" name="{{ $name }}" lang="{{ $lang }}" dir="{{ $lang === 'fa' ? 'rtl' : 'ltr' }}" maxlength="2500">{{ old($name, $experience[$name] ?? '') }}</textarea>
        </div>
      @endforeach
    </div>
    <p><button type="submit">Save draft</button> <a class="btn btn-secondary" href="{{ route('admin.experience.index') }}">Cancel</a></p>
  </form>
@endsection
