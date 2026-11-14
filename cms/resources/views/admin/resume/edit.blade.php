@extends('admin.layouts.app')
@section('title',__('Resume'))
@section('heading',__('Resume / CV'))
@section('content')
<div class="card">
  <h3>{{ __('Current CV') }}</h3>
  @if ($current?->pdf)
    <p>{{ __('Active:') }} <strong>{{ $current->label }}</strong> ({{ __($current->source) }})</p>
    <p><a href="{{ route('admin.media.raw', $current->pdf) }}" target="_blank">{{ __('Preview PDF') }}</a></p>
  @else
    <p class="badge warn">{{ __('No custom PDF uploaded. The public site uses the default résumé until you publish another.') }}</p>
  @endif
</div>

<div class="card">
  <h3>{{ __('Upload PDF') }}</h3>
  <form method="post" action="{{ route('admin.resume.upload') }}" enctype="multipart/form-data">
    @csrf
    <label>{{ __('Label') }}</label>
    <input name="label" placeholder="{{ __('English CV May 2026') }}">
    <label>{{ __('PDF file') }}</label>
    <input type="file" name="pdf" accept="application/pdf" required>
    <p style="margin-top:10px"><button type="submit">{{ __('Upload (draft)') }}</button></p>
  </form>
</div>

@php $en = $current->fields_en ?? []; $fa = $current->fields_fa ?? []; @endphp
<form method="post" action="{{ route('admin.resume.fields') }}" class="card">
  @csrf
  <h3>{{ __('Structured fields') }}</h3>
  <div class="row">
    <div>
      <h4>{{ __('English') }}</h4>
      <label>{{ __('Name') }}</label><input name="fields_en[name]" value="{{ $en['name'] ?? 'Amirhossein Zamanzade' }}">
      <label>{{ __('Headline') }}</label><input name="fields_en[headline]" value="{{ $en['headline'] ?? 'Software Engineer' }}">
      <label>{{ __('Summary') }}</label><textarea name="fields_en[summary]">{{ $en['summary'] ?? '' }}</textarea>
      <label>{{ __('Skills (comma-separated)') }}</label><input name="fields_en[skills]" value="{{ $en['skills'] ?? '' }}">
      <label>{{ __('Experience') }}</label><textarea name="fields_en[experience]">{{ $en['experience'] ?? '' }}</textarea>
      <label>{{ __('Education') }}</label><textarea name="fields_en[education]">{{ $en['education'] ?? '' }}</textarea>
    </div>
    <div>
      <h4>{{ __('Persian') }}</h4>
      <label>{{ __('Name') }}</label><input name="fields_fa[name]" dir="rtl" value="{{ $fa['name'] ?? 'امیرحسین زمان‌زاده' }}">
      <label>{{ __('Headline') }}</label><input name="fields_fa[headline]" dir="rtl" value="{{ $fa['headline'] ?? 'مهندس نرم‌افزار' }}">
      <label>{{ __('Summary') }}</label><textarea name="fields_fa[summary]" dir="rtl">{{ $fa['summary'] ?? '' }}</textarea>
      <label>{{ __('Skills') }}</label><input name="fields_fa[skills]" dir="rtl" value="{{ $fa['skills'] ?? '' }}">
      <label>{{ __('Experience') }}</label><textarea name="fields_fa[experience]" dir="rtl">{{ $fa['experience'] ?? '' }}</textarea>
      <label>{{ __('Education') }}</label><textarea name="fields_fa[education]" dir="rtl">{{ $fa['education'] ?? '' }}</textarea>
    </div>
  </div>
  <button type="submit">{{ __('Save fields') }}</button>
</form>

<div class="card row">
  <form method="post" action="{{ route('admin.resume.generate') }}">@csrf<input type="hidden" name="locale" value="en"><button type="submit">{{ __('Generate English PDF') }}</button></form>
  <form method="post" action="{{ route('admin.resume.generate') }}">@csrf<input type="hidden" name="locale" value="fa"><button type="submit">{{ __('Generate Persian PDF') }}</button></form>
</div>

<div class="card">
  <h3>{{ __('Versions') }}</h3>
  <table>
    <thead><tr><th>{{ __('ID') }}</th><th>{{ __('Label') }}</th><th>{{ __('Source') }}</th><th></th></tr></thead>
    <tbody>
    @foreach ($versions as $version)
      <tr>
        <td>#{{ $version->id }} @if($version->is_current)<span class="badge">{{ __('current') }}</span>@endif</td>
        <td>{{ $version->label }}</td>
        <td>{{ __($version->source) }}</td>
        <td>
          @if(!$version->is_current)
          <form method="post" action="{{ route('admin.resume.activate', $version) }}">@csrf<button class="btn-secondary" type="submit">{{ __('Activate') }}</button></form>
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endsection
