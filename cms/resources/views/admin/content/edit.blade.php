@extends('admin.layouts.app')
@section('title','Site content')
@section('heading','Site content')
@section('content')
<form method="post" action="{{ route('admin.content.update') }}">
  @csrf
  <div class="card">
    <h3>Profile & social</h3>
    <div class="row">
      <div><label>Profile name</label><input name="profile[name]" value="{{ $profile['name'] ?? '' }}"></div>
      <div><label>Profile photo path</label><input name="profile[photo]" value="{{ $profile['photo'] ?? '' }}"></div>
      <div><label>Email</label><input name="social[email]" value="{{ $social['email'] ?? '' }}"></div>
      <div><label>LinkedIn</label><input name="social[linkedin]" value="{{ $social['linkedin'] ?? '' }}"></div>
      <div><label>GitHub</label><input name="social[github]" value="{{ $social['github'] ?? '' }}"></div>
      <div><label>Telegram</label><input name="social[telegram]" value="{{ $social['telegram'] ?? '' }}"></div>
      <div><label>Featured project slugs (comma-separated)</label><input name="featured_slugs" value="{{ implode(',', $featured ?? []) }}"></div>
    </div>
  </div>

  <div class="card" id="booking">
    <h3>Booking (Book a Call)
      @if (!filled(old('booking.url', $booking['url'])) && !filled($bookingEnvUrl))
        <span class="badge warn">no booking URL — visitors see an “email me” fallback</span>
      @elseif (!filled(old('booking.url', $booking['url'])))
        <span class="badge">using BOOKING_URL from .env</span>
      @endif
    </h3>
    <p style="color:var(--muted);font-size:13px;margin:0 0 12px">
      Paste your own scheduling link. Cal.com and Calendly open inside the site with live availability and real confirmations;
      any other https link opens in a new tab. Calendar sync, reminders and video links are configured in your Cal.com / Calendly account.
      Button label, title and intro text are edited in the <a href="#content-booking">Booking</a> section below. Changes go live after <strong>Publish live</strong>.
    </p>
    <div class="row">
      <div>
        <label for="booking-url">Booking URL (https)</label>
        <input id="booking-url" name="booking[url]" type="url" inputmode="url" placeholder="https://cal.com/your-name/30min" value="{{ old('booking.url', $booking['url']) }}">
      </div>
      <div>
        <label for="booking-provider">Provider</label>
        <select id="booking-provider" name="booking[provider]">
          @foreach (['auto' => 'Detect from URL', 'calcom' => 'Cal.com (incl. self-hosted)', 'calendly' => 'Calendly', 'link' => 'Open link in a new tab'] as $value => $label)
            <option value="{{ $value }}" @selected(old('booking.provider', $booking['provider'] ?? 'auto') === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="booking-duration">Meeting length shown in the dialog (minutes, optional)</label>
        <input id="booking-duration" name="booking[duration]" type="number" min="5" max="480" step="5" value="{{ old('booking.duration', $booking['duration']) }}">
      </div>
      <div>
        <label>Book a Call button</label>
        <input type="hidden" name="booking[enabled]" value="0">
        <label style="display:flex;gap:8px;align-items:center;color:var(--text);font-size:14px;margin-top:8px">
          <input type="checkbox" name="booking[enabled]" value="1" style="width:auto" @checked(old('booking.enabled', $booking['enabled'] ?? true))>
          Show “Book a Call” as the primary contact button (off = show “Email me” instead)
        </label>
      </div>
    </div>
  </div>

  @foreach ($grouped as $group => $entries)
    <div class="card" id="content-{{ \Illuminate\Support\Str::slug($group) }}">
      <h3>{{ $group }}</h3>
      @foreach ($entries as $entry)
        <div class="row" style="margin-bottom:10px">
          <div>
            <label>{{ $entry->key }} (EN) @if(!filled($entry->value_en))<span class="badge warn">missing</span>@endif</label>
            <textarea name="entries[{{ $entry->id }}][en]" class="{{ !filled($entry->value_en) ? 'missing' : '' }}">{{ $entry->value_en }}</textarea>
          </div>
          <div>
            <label>{{ $entry->key }} (FA) @if(!filled($entry->value_fa))<span class="badge warn">missing</span>@endif</label>
            <textarea name="entries[{{ $entry->id }}][fa]" dir="rtl" class="{{ !filled($entry->value_fa) ? 'missing' : '' }}">{{ $entry->value_fa }}</textarea>
          </div>
        </div>
      @endforeach
    </div>
  @endforeach

  <button type="submit">Save draft</button>
</form>
@endsection
