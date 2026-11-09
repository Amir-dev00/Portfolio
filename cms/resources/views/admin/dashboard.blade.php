@extends('admin.layouts.app')
@section('title','Dashboard')
@section('heading','Dashboard')
@section('content')
<div class="row">
  <div class="card"><strong>{{ $dirtyContent }}</strong><div class="badge warn">dirty content fields</div></div>
  <div class="card"><strong>{{ $dirtyProjects }}</strong><div class="badge warn">dirty projects</div></div>
  <div class="card"><strong>{{ $dirtySeo }}</strong><div class="badge warn">dirty SEO rows</div></div>
  <div class="card"><strong>{{ $projectCount }}</strong><div class="badge">active projects</div></div>
</div>

<div class="card">
  <h3>Publication history</h3>
  <table>
    <thead><tr><th>ID</th><th>Label</th><th>When</th><th></th></tr></thead>
    <tbody>
    @foreach ($publications as $pub)
      <tr>
        <td>#{{ $pub->id }} @if($pub->id === $currentId)<span class="badge">live</span>@endif</td>
        <td>{{ $pub->label }}</td>
        <td>{{ optional($pub->published_at)->format('Y-m-d H:i') }}</td>
        <td>
          @if ($pub->id !== $currentId)
          <form method="post" action="{{ route('admin.publish.rollback', $pub) }}" onsubmit="return confirm('Rollback public site to this snapshot?')">
            @csrf
            <button class="btn-secondary" type="submit">Rollback</button>
          </form>
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>

<div class="card">
  <h3>Recent edits</h3>
  <table>
    <thead><tr><th>When</th><th>Entity</th><th>Action</th></tr></thead>
    <tbody>
    @forelse ($recent as $rev)
      <tr>
        <td>{{ $rev->created_at }}</td>
        <td>{{ $rev->entity_type }} #{{ $rev->entity_id }}</td>
        <td>{{ $rev->action }}</td>
      </tr>
    @empty
      <tr><td colspan="3">No revisions yet.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
