@extends('admin.layouts.app')
@section('title','Projects')
@section('heading','Projects')
@section('content')
<p><a class="btn" href="{{ route('admin.projects.create') }}">Add project</a></p>
<div class="card">
<table>
  <thead><tr><th>Order</th><th>Title</th><th>Slug</th><th>Status</th><th></th></tr></thead>
  <tbody>
  @foreach ($projects as $project)
    <tr>
      <td>{{ $project->sort_order }}</td>
      <td>{{ $project->title }} @if($project->is_dirty)<span class="badge warn">dirty</span>@endif</td>
      <td>{{ $project->slug }}</td>
      <td>{{ $project->archived_at ? 'Archived' : 'Active' }}</td>
      <td>
        <a href="{{ route('admin.projects.edit', $project) }}">Edit</a>
        @if ($project->archived_at)
          <form style="display:inline" method="post" action="{{ route('admin.projects.restore', $project) }}">@csrf<button class="btn-secondary" type="submit">Restore</button></form>
        @else
          <form style="display:inline" method="post" action="{{ route('admin.projects.archive', $project) }}">@csrf<button class="btn-danger" type="submit">Archive</button></form>
        @endif
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
</div>
<form method="post" action="{{ route('admin.projects.reorder') }}" class="card">
  @csrf
  <h3>Reorder (IDs top to bottom)</h3>
  @foreach ($projects->whereNull('archived_at') as $project)
    <input type="hidden" name="order[]" value="{{ $project->id }}">
  @endforeach
  <p class="badge">Drag-reorder UI omitted for cPanel simplicity — edit sort_order on each project, or use the hidden order list above after manually rearranging IDs in a future update.</p>
  <button type="submit" class="btn-secondary">Keep current order marker</button>
</form>
@endsection
