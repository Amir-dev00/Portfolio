@extends('admin.layouts.app')
@section('title',__('Dashboard'))
@section('heading',__('Dashboard'))
@section('content')
<div class="admin-shortcuts">
  <a class="card" href="{{ route('admin.content.edit') }}"><strong>{{ __('Site settings') }}</strong><span>{{ __('Profile, team, links and backgrounds') }}</span></a>
  <a class="card" href="{{ route('admin.content.edit', ['group' => 'About']) }}"><strong>{{ __('About text') }}</strong><span>{{ __('Bio, education, team and skills labels') }}</span></a>
  <a class="card" href="{{ route('admin.content.edit', ['group' => 'Miro']) }}"><strong>{{ __('Miro') }}</strong><span>{{ __('Tour, chat and suggested questions') }}</span></a>
  <a class="card" href="{{ route('admin.projects.index') }}"><strong>{{ __('Projects') }}</strong><span>{{ __('Details, galleries and publishing status') }}</span></a>
  <a class="card" href="{{ route('admin.bookings.settings') }}"><strong>{{ __('Bookings') }}</strong><span>{{ __('Availability and notifications') }}</span></a>
  <a class="card" href="{{ route('admin.seo.edit') }}"><strong>{{ __('SEO') }}</strong><span>{{ __('Page titles and sharing metadata') }}</span></a>
</div>
<div class="row">
  <div class="card"><strong>{{ $dirtyContent }}</strong><div class="badge warn">{{ __('dirty content fields') }}</div></div>
  <div class="card"><strong>{{ $dirtyProjects }}</strong><div class="badge warn">{{ __('dirty projects') }}</div></div>
  <div class="card"><strong>{{ $dirtySeo }}</strong><div class="badge warn">{{ __('dirty SEO rows') }}</div></div>
  <div class="card"><strong>{{ $projectCount }}</strong><div class="badge">{{ __('active projects') }}</div></div>
</div>

<div class="card admin-table">
  <h3>{{ __('Publication history') }}</h3>
  <table>
    <thead><tr><th>{{ __('ID') }}</th><th>{{ __('Label') }}</th><th>{{ __('When') }}</th><th></th></tr></thead>
    <tbody>
    @foreach ($publications as $pub)
      <tr>
        <td>#{{ $pub->id }} @if($pub->id === $currentId)<span class="badge">{{ __('live') }}</span>@endif</td>
        <td>{{ $pub->label }}</td>
        <td>{{ optional($pub->published_at)->format('Y-m-d H:i') }}</td>
        <td>
          @if ($pub->id !== $currentId)
          <form method="post" action="{{ route('admin.publish.rollback', $pub) }}" onsubmit="return confirm(this.dataset.confirm)" data-confirm="{{ __('Rollback public site to this snapshot?') }}">
            @csrf
            <button class="btn-secondary" type="submit">{{ __('Rollback') }}</button>
          </form>
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>

<div class="card admin-table">
  <h3>{{ __('Recent edits') }}</h3>
  <table>
    <thead><tr><th>{{ __('When') }}</th><th>{{ __('Entity') }}</th><th>{{ __('Action') }}</th></tr></thead>
    <tbody>
    @forelse ($recent as $rev)
      <tr>
        <td>{{ $rev->created_at }}</td>
        <td>{{ __(ucfirst(str_replace('_', ' ', $rev->entity_type))) }} #{{ $rev->entity_id }}</td>
        <td>{{ __($rev->action) }}</td>
      </tr>
    @empty
      <tr><td colspan="3">{{ __('No revisions yet.') }}</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
