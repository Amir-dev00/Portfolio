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
    <h3>Booking (Book a Call)</h3>
    <p style="color:var(--muted);font-size:13px;margin:0 0 12px">
      Scheduling is <strong>self-hosted</strong> on this site (no Cal.com / Calendly).
      Manage weekly hours, blocked dates, and appointments in
      <a href="{{ route('admin.bookings.settings') }}">Admin → Bookings</a>.
      Button labels and modal copy are edited in the <a href="#content-booking">Booking</a> text group below.
      Toggle the public button here, then <strong>Publish live</strong> for text changes.
    </p>
    <div class="row">
      <div>
        <label>Book a Call button</label>
        <input type="hidden" name="booking[enabled]" value="0">
        <label style="display:flex;gap:8px;align-items:center;color:var(--text);font-size:14px;margin-top:8px">
          <input type="checkbox" name="booking[enabled]" value="1" style="width:auto" @checked(old('booking.enabled', $booking['enabled'] ?? true))>
          Show “Book a Call” as the primary contact button (off = show “Email me” instead)
        </label>
      </div>
      <div>
        <label>Duration shown in UI (minutes)</label>
        <input name="booking[duration]" type="number" min="5" max="480" step="5" value="{{ old('booking.duration', $booking['duration'] ?? 30) }}">
        <p style="color:var(--muted);font-size:12px;margin:6px 0 0">Also editable under Bookings → settings (source of truth for slots).</p>
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
