@extends('admin.layouts.app')
@section('title', __('Work experience'))
@section('heading', __('Work experience'))
@section('content')
  <p><a class="btn" href="{{ route('admin.experience.create') }}">{{ __('Add experience') }}</a>
    <a class="btn btn-secondary" href="{{ url('/work?preview=1') }}">{{ __('Preview work page') }}</a></p>
  @forelse ($entries as $entry)
    @php($experience = $entry->value)
    @php($id = substr($entry->key, strlen(\App\Models\SiteSetting::EXPERIENCE_PREFIX)))
    <article class="card">
      <h3>{{ $experience['role_'.app()->getLocale()] ?? $experience['role_en'] }} · {{ $experience['company_'.app()->getLocale()] ?? $experience['company_en'] }}</h3>
      <p>{{ $experience['start_month'] }} – {{ $experience['end_month'] ?? __('Present') }}</p>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="btn btn-secondary" href="{{ route('admin.experience.edit', $id) }}">{{ __('Edit') }}</a>
        <form method="post" action="{{ route('admin.experience.destroy', $id) }}" onsubmit="return confirm(this.dataset.confirm)" data-confirm="{{ __('Remove this experience from the draft?') }}">
          @csrf
          @method('DELETE')
          <button class="btn-danger" type="submit">{{ __('Remove') }}</button>
        </form>
      </div>
    </article>
  @empty
    <p>{{ __('Add your first role to start building your work history.') }}</p>
  @endforelse
@endsection
