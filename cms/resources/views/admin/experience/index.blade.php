@extends('admin.layouts.app')
@section('title', __('Work experience'))
@section('heading', __('Work experience'))
@section('content')
  <div class="admin-pills">
    <a class="btn" href="{{ route('admin.experience.create') }}">{{ __('Add experience') }}</a>
    <a class="btn btn-secondary" href="{{ url('/work?preview=1#work-experience') }}">{{ __('Preview work page') }} ({{ __('English') }})</a>
    <a class="btn btn-secondary" href="{{ url('/fa/work?preview=1#work-experience') }}">{{ __('Preview work page') }} ({{ __('Persian') }})</a>
  </div>

  @if ($entries->isEmpty())
    <p>{{ __('Add your first role to start building your work history.') }}</p>
  @else
    <form method="post" action="{{ route('admin.experience.reorder') }}" data-admin-order>
      @csrf
      @if ($entries->count() > 1)
        <p class="admin-hint">{{ __('Use the arrows to rearrange experience. Save the order, then publish to update Work.') }}</p>
      @endif
      <ol class="admin-experience-list" role="list">
        @foreach ($entries as $id => $entry)
          @php
            $experience = $entry->value;
            $company = $experience['company_'.app()->getLocale()] ?? $experience['company_en'];
            $role = $experience['role_'.app()->getLocale()] ?? $experience['role_en'];
            $logo = $logos->get($experience['logo_id'] ?? null);
            $logoSrc = $logo ? route('admin.media.raw', $logo) : (!empty($experience['logo_path']) ? asset($experience['logo_path']) : null);
          @endphp
          <li class="card admin-experience-card">
            <input type="hidden" name="order[]" value="{{ $id }}">
            <span class="admin-experience-logo" aria-hidden="true">
              @if ($logoSrc)
                <img src="{{ $logoSrc }}" alt="" width="48" height="48">
              @else
                {{ \Illuminate\Support\Str::substr($company, 0, 1) }}
              @endif
            </span>
            <div class="admin-experience-copy">
              <h3 dir="auto">{{ $role }}</h3>
              <p dir="auto">{{ $company }}</p>
              <p class="admin-hint">
                {{ $experience['start_label_'.app()->getLocale()] ?? $experience['start_month'] }}
                <span aria-hidden="true">–</span>
                {{ $experience['end_month'] ?? __('Present') }}
              </p>
            </div>
            <div class="admin-pills">
              @if ($entries->count() > 1)
                <button type="button" class="btn-secondary" data-move="up" aria-label="{{ __('Move :title up', ['title' => $company]) }}" @disabled($loop->first)>↑</button>
                <button type="button" class="btn-secondary" data-move="down" aria-label="{{ __('Move :title down', ['title' => $company]) }}" @disabled($loop->last)>↓</button>
              @endif
              <a class="btn btn-secondary" href="{{ route('admin.experience.edit', $id) }}">{{ __('Edit') }}</a>
              <button class="btn-danger" type="submit" form="experience-remove-{{ $id }}">{{ __('Remove') }}</button>
            </div>
          </li>
        @endforeach
      </ol>
      @if ($entries->count() > 1)
        <div class="admin-save"><button type="submit">{{ __('Save experience order') }}</button></div>
      @endif
    </form>
    @foreach ($entries as $id => $entry)
      <form id="experience-remove-{{ $id }}" method="post" action="{{ route('admin.experience.destroy', $id) }}" onsubmit="return confirm(this.dataset.confirm)" data-confirm="{{ __('Remove this experience from the draft?') }}" hidden>
        @csrf
        @method('DELETE')
      </form>
    @endforeach
    @if ($entries->count() > 1)
      @include('admin.partials.reorder-script')
    @endif
  @endif
@endsection
