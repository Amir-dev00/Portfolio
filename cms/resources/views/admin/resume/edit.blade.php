@extends('admin.layouts.app')
@section('title','Resume')
@section('heading','Resume / CV')
@section('content')
<div class="card">
  <h3>Current CV</h3>
  @if ($current?->pdf)
    <p>Active: <strong>{{ $current->label }}</strong> ({{ $current->source }})</p>
    <p><a href="{{ route('admin.media.raw', $current->pdf) }}" target="_blank">Preview PDF</a></p>
  @else
    <p class="badge warn">No PDF uploaded yet. Public “Download CV” will stay unavailable until you upload/generate and publish.</p>
  @endif
</div>

<div class="card">
  <h3>Upload PDF</h3>
  <form method="post" action="{{ route('admin.resume.upload') }}" enctype="multipart/form-data">
    @csrf
    <label>Label</label>
    <input name="label" placeholder="English CV May 2026">
    <label>PDF file</label>
    <input type="file" name="pdf" accept="application/pdf" required>
    <p style="margin-top:10px"><button type="submit">Upload (draft)</button></p>
  </form>
</div>

@php $en = $current->fields_en ?? []; $fa = $current->fields_fa ?? []; @endphp
<form method="post" action="{{ route('admin.resume.fields') }}" class="card">
  @csrf
  <h3>Structured fields</h3>
  <div class="row">
    <div>
      <h4>English</h4>
      <label>Name</label><input name="fields_en[name]" value="{{ $en['name'] ?? 'Amirhossein Zamanzade' }}">
      <label>Headline</label><input name="fields_en[headline]" value="{{ $en['headline'] ?? 'Software Engineer' }}">
      <label>Summary</label><textarea name="fields_en[summary]">{{ $en['summary'] ?? '' }}</textarea>
      <label>Skills (comma-separated)</label><input name="fields_en[skills]" value="{{ $en['skills'] ?? '' }}">
      <label>Experience</label><textarea name="fields_en[experience]">{{ $en['experience'] ?? '' }}</textarea>
      <label>Education</label><textarea name="fields_en[education]">{{ $en['education'] ?? '' }}</textarea>
    </div>
    <div>
      <h4>Persian</h4>
      <label>Name</label><input name="fields_fa[name]" dir="rtl" value="{{ $fa['name'] ?? 'امیرحسین زمان‌زاده' }}">
      <label>Headline</label><input name="fields_fa[headline]" dir="rtl" value="{{ $fa['headline'] ?? 'مهندس نرم‌افزار' }}">
      <label>Summary</label><textarea name="fields_fa[summary]" dir="rtl">{{ $fa['summary'] ?? '' }}</textarea>
      <label>Skills</label><input name="fields_fa[skills]" dir="rtl" value="{{ $fa['skills'] ?? '' }}">
      <label>Experience</label><textarea name="fields_fa[experience]" dir="rtl">{{ $fa['experience'] ?? '' }}</textarea>
      <label>Education</label><textarea name="fields_fa[education]" dir="rtl">{{ $fa['education'] ?? '' }}</textarea>
    </div>
  </div>
  <button type="submit">Save fields</button>
</form>

<div class="card row">
  <form method="post" action="{{ route('admin.resume.generate') }}">@csrf<input type="hidden" name="locale" value="en"><button type="submit">Generate English PDF</button></form>
  <form method="post" action="{{ route('admin.resume.generate') }}">@csrf<input type="hidden" name="locale" value="fa"><button type="submit">Generate Persian PDF</button></form>
</div>

<div class="card">
  <h3>Versions</h3>
  <table>
    <thead><tr><th>ID</th><th>Label</th><th>Source</th><th></th></tr></thead>
    <tbody>
    @foreach ($versions as $version)
      <tr>
        <td>#{{ $version->id }} @if($version->is_current)<span class="badge">current</span>@endif</td>
        <td>{{ $version->label }}</td>
        <td>{{ $version->source }}</td>
        <td>
          @if(!$version->is_current)
          <form method="post" action="{{ route('admin.resume.activate', $version) }}">@csrf<button class="btn-secondary" type="submit">Activate</button></form>
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endsection
