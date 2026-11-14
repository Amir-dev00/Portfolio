@extends('admin.layouts.app')
@section('title',__('Booking :reference', ['reference' => $booking->reference]))
@section('heading',__('Booking :reference', ['reference' => $booking->reference]))
@section('content')
<div class="card">
  <div class="row">
    <div><label>{{ __('Status') }}</label><div><span class="badge">{{ __($booking->status) }}</span></div></div>
    <div><label>{{ __('Reference') }}</label><div><code>{{ $booking->reference }}</code></div></div>
    <div><label>{{ __('Visitor') }}</label><div>{{ $booking->visitor_name }}</div></div>
    <div><label>{{ __('Email') }}</label><div><a href="mailto:{{ $booking->visitor_email }}">{{ $booking->visitor_email }}</a></div></div>
    <div><label>{{ __('Phone') }}</label><div>{{ $booking->visitor_phone ?: '—' }}</div></div>
    <div><label>{{ __('Timezone') }}</label><div>{{ $booking->timezone }}</div></div>
    <div><label>{{ __('Starts (owner tz)') }}</label><div>{{ $booking->starts_at?->timezone($settings['timezone'] ?? 'UTC')->toDayDateTimeString() }}</div></div>
    <div><label>{{ __('Ends (owner tz)') }}</label><div>{{ $booking->ends_at?->timezone($settings['timezone'] ?? 'UTC')->toDayDateTimeString() }}</div></div>
    <div><label>{{ __('Topic') }}</label><div>{{ $booking->topic ?: '—' }}</div></div>
    <div style="grid-column:1/-1"><label>{{ __('Message') }}</label><div>{{ $booking->message ?: '—' }}</div></div>
    <div><label>{{ __('Owner SMS') }}</label><div>
      @if ($booking->owner_sms_status)
        <span class="badge">{{ __($booking->owner_sms_status) }}</span>
        @if ($booking->owner_sms_provider_message_id)
          <code style="margin-inline-start:6px">{{ $booking->owner_sms_provider_message_id }}</code>
        @endif
        @if ($booking->owner_sms_error)
          <div style="opacity:.75;margin-top:4px">{{ $booking->owner_sms_error }}</div>
        @endif
      @else
        —
      @endif
    </div></div>
  </div>
  <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap">
    <a class="btn btn-secondary" href="{{ route('admin.bookings.index') }}">{{ __('Back') }}</a>
    @if ($booking->status === 'confirmed')
      <form method="post" action="{{ route('admin.bookings.complete', $booking) }}">@csrf<button type="submit">{{ __('Mark completed') }}</button></form>
      <form method="post" action="{{ route('admin.bookings.cancel', $booking) }}" onsubmit="return confirm(this.dataset.confirm)" data-confirm="{{ __('Cancel this booking?') }}">@csrf<button class="btn-danger" type="submit">{{ __('Cancel booking') }}</button></form>
    @endif
  </div>
</div>
@endsection
