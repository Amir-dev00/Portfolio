@extends('admin.layouts.app')
@section('title','Projects')
@section('heading','Projects')
@section('content')
<div class="admin-pills">
  <a class="btn" href="{{ route('admin.projects.create') }}">Add project</a>
  @if(count($unmanaged))
    <form method="post" action="{{ route('admin.projects.import') }}">
      @csrf
      <button type="submit" class="btn-secondary">Import {{ count($unmanaged) }} existing portfolio projects</button>
    </form>
  @endif
</div>
<form method="get" class="card">
  <div class="row">
    <label>Find a project<input type="search" name="q" value="{{ is_string(request('q')) ? request('q') : '' }}" placeholder="Title or slug"></label>
    <label>Status<select name="status">
      <option value="">All</option>
      <option value="active" @selected(request('status') === 'active')>Active</option>
      <option value="archived" @selected(request('status') === 'archived')>Archived</option>
    </select></label>
  </div>
  <div class="admin-pills"><button class="btn-secondary">Filter</button><a href="{{ route('admin.projects.index') }}">Clear</a></div>
</form>
<div class="card admin-table">
<table>
  <thead><tr><th>Order</th><th>Title</th><th>Slug</th><th>Status</th><th></th></tr></thead>
  <tbody>
  @forelse ($projects as $project)
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
  @empty
    <tr><td colspan="5">No projects match this filter.</td></tr>
  @endforelse
  </tbody>
</table>
</div>
@endsection
