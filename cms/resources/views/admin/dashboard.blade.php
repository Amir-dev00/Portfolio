@extends('admin.layouts.app')
@section('title','Dashboard')
@section('heading','Dashboard')
@section('content')
<div class="admin-shortcuts">
  <a class="card" href="{{ route('admin.content.edit') }}"><strong>Site settings</strong><span>Profile, team, links and backgrounds</span></a>
  <a class="card" href="{{ route('admin.content.edit', ['group' => 'About']) }}"><strong>About text</strong><span>Bio, education, team and skills labels</span></a>
  <a class="card" href="{{ route('admin.content.edit', ['group' => 'Miro']) }}"><strong>Miro</strong><span>Tour, chat and suggested questions</span></a>
  <a class="card" href="{{ route('admin.projects.index') }}"><strong>Projects</strong><span>Details, galleries and publishing status</span></a>
  <a class="card" href="{{ route('admin.bookings.settings') }}"><strong>Bookings</strong><span>Availability and notifications</span></a>
  <a class="card" href="{{ route('admin.seo.edit') }}"><strong>SEO</strong><span>Page titles and sharing metadata</span></a>
</div>
<div class="row">
  <div class="card"><strong>{{ $dirtyContent }}</strong><div class="badge warn">dirty content fields</div></div>
  <div class="card"><strong>{{ $dirtyProjects }}</strong><div class="badge warn">dirty projects</div></div>
  <div class="card"><strong>{{ $dirtySeo }}</strong><div class="badge warn">dirty SEO rows</div></div>
  <div class="card"><strong>{{ $projectCount }}</strong><div class="badge">active projects</div></div>
</div>

<div class="card admin-table">
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

<div class="card admin-table">
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
