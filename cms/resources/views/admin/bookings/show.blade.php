@extends('admin.layouts.app')
@section('title','Booking '.$booking->reference)
@section('heading','Booking '.$booking->reference)
@section('content')
<div class="card">
  <div class="row">
    <div><label>Status</label><div><span class="badge">{{ $booking->status }}</span></div></div>
    <div><label>Reference</label><div><code>{{ $booking->reference }}</code></div></div>
    <div><label>Visitor</label><div>{{ $booking->visitor_name }}</div></div>
    <div><label>Email</label><div><a href="mailto:{{ $booking->visitor_email }}">{{ $booking->visitor_email }}</a></div></div>
    <div><label>Phone</label><div>{{ $booking->visitor_phone ?: '—' }}</div></div>
    <div><label>Timezone</label><div>{{ $booking->timezone }}</div></div>
    <div><label>Starts (owner tz)</label><div>{{ $booking->starts_at?->timezone($settings['timezone'] ?? 'UTC')->toDayDateTimeString() }}</div></div>
    <div><label>Ends (owner tz)</label><div>{{ $booking->ends_at?->timezone($settings['timezone'] ?? 'UTC')->toDayDateTimeString() }}</div></div>
    <div><label>Topic</label><div>{{ $booking->topic ?: '—' }}</div></div>
    <div style="grid-column:1/-1"><label>Message</label><div>{{ $booking->message ?: '—' }}</div></div>
  </div>
  <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap">
    <a class="btn btn-secondary" href="{{ route('admin.bookings.index') }}">Back</a>
    @if ($booking->status === 'confirmed')
      <form method="post" action="{{ route('admin.bookings.complete', $booking) }}">@csrf<button type="submit">Mark completed</button></form>
      <form method="post" action="{{ route('admin.bookings.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking?')">@csrf<button class="btn-danger" type="submit">Cancel booking</button></form>
    @endif
  </div>
</div>
@endsection
