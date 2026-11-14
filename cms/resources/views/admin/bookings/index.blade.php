@extends('admin.layouts.app')
@section('title',__('Bookings'))
@section('heading',__('Bookings'))
@section('content')
<div class="card">
  <div class="topbar" style="margin:0">
    <div>
      <strong>{{ $upcoming }}</strong> {{ __('upcoming confirmed') }}
      <span class="badge" style="margin-inline-start:8px">{{ __('self-hosted · Asia/Tehran settings') }}</span>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn btn-secondary" href="{{ route('admin.bookings.settings') }}">{{ __('Availability & settings') }}</a>
      <a class="btn btn-secondary" href="{{ route('admin.bookings.export') }}">{{ __('Export CSV') }}</a>
    </div>
  </div>
</div>

<form method="get" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
  <div style="flex:1;min-width:180px">
    <label>{{ __('Search') }}</label>
    <input name="q" value="{{ $q }}" placeholder="{{ __('name, email, reference…') }}">
  </div>
  <div>
    <label>{{ __('Status') }}</label>
    <select name="status">
      <option value="">{{ __('All') }}</option>
      @foreach (['confirmed','cancelled','completed'] as $s)
        <option value="{{ $s }}" @selected($status === $s)>{{ app()->isLocale('fa') ? __($s) : ucfirst($s) }}</option>
      @endforeach
    </select>
  </div>
  <button type="submit">{{ __('Filter') }}</button>
</form>

<div class="card">
  <table>
    <thead>
      <tr>
        <th>{{ __('When (UTC)') }}</th>
        <th>{{ __('Visitor') }}</th>
        <th>{{ __('Topic') }}</th>
        <th>{{ __('Ref') }}</th>
        <th>{{ __('Status') }}</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($bookings as $booking)
        <tr>
          <td>
            <div>{{ $booking->starts_at?->timezone($settings['timezone'] ?? 'UTC')->format('Y-m-d H:i') }}</div>
            <small style="color:var(--muted)">{{ $booking->timezone }}</small>
          </td>
          <td>
            <div>{{ $booking->visitor_name }}</div>
            <small style="color:var(--muted)">{{ $booking->visitor_email }}</small>
          </td>
          <td>{{ $booking->topic ?: '—' }}</td>
          <td><code>{{ $booking->reference }}</code></td>
          <td><span class="badge">{{ __($booking->status) }}</span></td>
          <td><a href="{{ route('admin.bookings.show', $booking) }}">{{ __('View') }}</a></td>
        </tr>
      @empty
        <tr><td colspan="6">{{ __('No bookings yet.') }}</td></tr>
      @endforelse
    </tbody>
  </table>
  <div style="margin-top:12px">{{ $bookings->links('pagination::simple-tailwind') }}</div>
</div>
@endsection
