@extends('admin.layouts.app')
@section('title',__('Projects'))
@section('heading',__('Projects'))
@section('content')
<div class="admin-pills">
  <a class="btn" href="{{ route('admin.projects.create') }}">{{ __('Add project') }}</a>
  @if(count($unmanaged))
    <form method="post" action="{{ route('admin.projects.import') }}">
      @csrf
      <button type="submit" class="btn-secondary">{{ __('Import :count existing portfolio projects', ['count' => count($unmanaged)]) }}</button>
    </form>
  @endif
</div>
@if($orderableProjects->count() > 1)
  <details class="card" @if($errors->has('order') || $errors->has('order.*')) open @endif>
    <summary>{{ __('Reorder projects') }}</summary>
    <p class="admin-hint">{{ __('Use the arrows to move projects. Save the order, then publish to update Work.') }}</p>
    @if(count($unmanaged))
      <p class="admin-hint">{{ __('Import existing portfolio projects above to put them in the same order list.') }}</p>
    @endif
    <form method="post" action="{{ route('admin.projects.reorder') }}" data-project-order>
      @csrf
      <ol>
        @foreach($orderableProjects as $item)
          <li>
            <input type="hidden" name="order[]" value="{{ $item->id }}">
            <span>{{ $item->title }}</span>
            <span class="admin-pills" style="display:inline-flex;margin:4px 8px">
              <button type="button" class="btn-secondary" data-move="up" aria-label="{{ __('Move :title up', ['title' => $item->title]) }}" @disabled($loop->first)>↑</button>
              <button type="button" class="btn-secondary" data-move="down" aria-label="{{ __('Move :title down', ['title' => $item->title]) }}" @disabled($loop->last)>↓</button>
            </span>
          </li>
        @endforeach
      </ol>
      <button type="submit">{{ __('Save project order') }}</button>
    </form>
  </details>
  <script>
    (() => {
      const form = document.querySelector('[data-project-order]');
      const list = form.querySelector('ol');

      form.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-move]');
        if (!button) return;

        const row = button.closest('li');
        const moveUp = button.dataset.move === 'up';
        const neighbor = moveUp ? row.previousElementSibling : row.nextElementSibling;
        if (!neighbor) return;

        if (moveUp) list.insertBefore(row, neighbor);
        else list.insertBefore(neighbor, row);

        for (const item of list.children) {
          item.querySelector('[data-move="up"]').disabled = item === list.firstElementChild;
          item.querySelector('[data-move="down"]').disabled = item === list.lastElementChild;
        }

        (button.disabled ? row.querySelector('button:not(:disabled)') : button).focus();
      });
    })();
  </script>
@endif
<form method="get" class="card">
  <div class="row">
    <label>{{ __('Find a project') }}<input type="search" name="q" value="{{ is_string(request('q')) ? request('q') : '' }}" placeholder="{{ __('Title or slug') }}"></label>
    <label>{{ __('Status') }}<select name="status">
      <option value="">{{ __('All') }}</option>
      <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
      <option value="archived" @selected(request('status') === 'archived')>{{ __('Archived') }}</option>
    </select></label>
  </div>
  <div class="admin-pills"><button class="btn-secondary">{{ __('Filter') }}</button><a href="{{ route('admin.projects.index') }}">{{ __('Clear') }}</a></div>
</form>
<div class="card admin-table">
<table>
  <thead><tr><th>{{ __('Order') }}</th><th>{{ __('Title') }}</th><th>{{ __('Slug') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
  <tbody>
  @forelse ($projects as $project)
    <tr>
      <td>{{ $project->sort_order }}</td>
      <td>{{ $project->title }} @if($project->is_dirty)<span class="badge warn">{{ __('dirty') }}</span>@endif</td>
      <td>{{ $project->slug }}</td>
      <td>{{ __($project->archived_at ? 'Archived' : 'Active') }}</td>
      <td>
        <a href="{{ route('admin.projects.edit', $project) }}">{{ __('Edit') }}</a>
        @if ($project->archived_at)
          <form style="display:inline" method="post" action="{{ route('admin.projects.restore', $project) }}">@csrf<button class="btn-secondary" type="submit">{{ __('Restore') }}</button></form>
        @else
          <form style="display:inline" method="post" action="{{ route('admin.projects.archive', $project) }}">@csrf<button class="btn-danger" type="submit">{{ __('Archive') }}</button></form>
        @endif
        @include('admin.projects.delete')
      </td>
    </tr>
  @empty
    <tr><td colspan="5">{{ __('No projects match this filter.') }}</td></tr>
  @endforelse
  </tbody>
</table>
</div>
@endsection
