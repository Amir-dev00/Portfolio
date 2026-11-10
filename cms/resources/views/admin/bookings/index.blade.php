@extends('admin.layouts.app')
@section('title','Bookings')
@section('heading','Bookings')
@section('content')
<div class="card">
  <div class="topbar" style="margin:0">
    <div>
      <strong>{{ $upcoming }}</strong> upcoming confirmed
      <span class="badge" style="margin-inline-start:8px">self-hosted · Asia/Tehran settings</span>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn btn-secondary" href="{{ route('admin.bookings.settings') }}">Availability & settings</a>
      <a class="btn btn-secondary" href="{{ route('admin.bookings.export') }}">Export CSV</a>
    </div>
  </div>
</div>

<form method="get" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
  <div style="flex:1;min-width:180px">
    <label>Search</label>
    <input name="q" value="{{ $q }}" placeholder="name, email, reference…">
  </div>
  <div>
    <label>Status</label>
    <select name="status">
      <option value="">All</option>
      @foreach (['confirmed','cancelled','completed'] as $s)
        <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
      @endforeach
    </select>
  </div>
  <button type="submit">Filter</button>
</form>

<div class="card">
  <table>
    <thead>
      <tr>
        <th>When (UTC)</th>
        <th>Visitor</th>
        <th>Topic</th>
        <th>Ref</th>
        <th>Status</th>
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
          <td><span class="badge">{{ $booking->status }}</span></td>
          <td><a href="{{ route('admin.bookings.show', $booking) }}">View</a></td>
        </tr>
      @empty
        <tr><td colspan="6">No bookings yet.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div style="margin-top:12px">{{ $bookings->links() }}</div>
</div>
@endsection
