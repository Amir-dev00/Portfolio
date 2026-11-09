@extends('admin.layouts.app')
@section('title','Media')
@section('heading','Media library')
@section('content')
<div class="card">
  <form method="post" action="{{ route('admin.media.upload') }}" enctype="multipart/form-data">
    @csrf
    <div class="row">
      <div><label>File (image/PDF)</label><input type="file" name="file" required></div>
      <div><label>Alt EN</label><input name="alt_en"></div>
      <div><label>Alt FA</label><input name="alt_fa" dir="rtl"></div>
    </div>
    <p style="margin-top:10px"><button type="submit">Upload to private drafts</button></p>
  </form>
</div>
<div class="card">
<table>
  <thead><tr><th>ID</th><th>File</th><th>Published</th><th>Used by</th><th>Alt</th></tr></thead>
  <tbody>
  @foreach ($media as $item)
    <tr>
      <td>#{{ $item->id }}</td>
      <td><a href="{{ route('admin.media.raw', $item) }}" target="_blank">{{ $item->original_name }}</a><div class="badge">{{ $item->mime }}</div></td>
      <td>{{ $item->is_published ? 'yes' : 'draft' }}<div class="badge">{{ $item->public_path }}</div></td>
      <td>{{ implode(', ', $item->used_by ?? []) }}</td>
      <td>
        <form method="post" action="{{ route('admin.media.update', $item) }}">
          @csrf @method('PUT')
          <input name="alt_en" value="{{ $item->alt_en }}" placeholder="Alt EN">
          <input name="alt_fa" value="{{ $item->alt_fa }}" placeholder="Alt FA" dir="rtl">
          <button class="btn-secondary" type="submit">Save</button>
        </form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
</div>
@endsection
