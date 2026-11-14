@extends('admin.layouts.app')
@section('title', __($entry ? 'Edit experience' : 'Add experience'))
@section('heading', __($entry ? 'Edit experience' : 'Add experience'))
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
            <label for="{{ $name }}">{{ __($label) }} ({{ __($language) }})</label>
            <input id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $experience[$name] ?? '') }}"
              lang="{{ $lang }}" dir="{{ $lang === 'fa' ? 'rtl' : 'ltr' }}" maxlength="150" required>
          </div>
        @endforeach
      @endforeach
      <div>
        <label for="start_month">{{ __('Start month (Gregorian)') }}</label>
        <input type="month" id="start_month" name="start_month" value="{{ old('start_month', $experience['start_month'] ?? '') }}" placeholder="YYYY-MM" required>
      </div>
      <div>
        <label for="end_month">{{ __('End month (Gregorian, blank if current)') }}</label>
        <input type="month" id="end_month" name="end_month" value="{{ old('end_month', $experience['end_month'] ?? '') }}" placeholder="YYYY-MM">
      </div>
      <div>
        <label for="type">{{ __('Employment type') }}</label>
        <select id="type" name="type">
          <option value="">{{ __('Select a type') }}</option>
          @foreach ($types as $key => $labels)
            <option value="{{ $key }}" @selected(old('type', $experience['type'] ?? '') === $key)>{{ $labels[app()->getLocale()] }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="website">{{ __('Company website') }}</label>
        <input type="url" id="website" name="website" value="{{ old('website', $experience['website'] ?? '') }}" placeholder="https://example.com" maxlength="500">
      </div>
      <div>
        <label for="logo">{{ __('Company logo (PNG, JPG or WebP, up to 2 MB)') }}</label>
        <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
        @if ($logo || !empty($experience['logo_path']))
          <img src="{{ $logo ? route('admin.media.raw', $logo) : asset($experience['logo_path']) }}" width="48" height="48" alt="" style="object-fit:contain;margin-top:8px">
          <label><input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo')) style="width:auto"> {{ __('Remove current logo') }}</label>
        @endif
      </div>
      @foreach (['en' => 'English', 'fa' => 'Persian'] as $lang => $language)
        @php($name = 'description_'.$lang)
        <div>
          <label for="{{ $name }}">{{ __('Responsibilities and achievements') }} ({{ __($language) }})</label>
          <textarea id="{{ $name }}" name="{{ $name }}" lang="{{ $lang }}" dir="{{ $lang === 'fa' ? 'rtl' : 'ltr' }}" maxlength="2500">{{ old($name, $experience[$name] ?? '') }}</textarea>
        </div>
      @endforeach
    </div>
    <p><button type="submit">{{ __('Save draft') }}</button> <a class="btn btn-secondary" href="{{ route('admin.experience.index') }}">{{ __('Cancel') }}</a></p>
  </form>
@endsection
