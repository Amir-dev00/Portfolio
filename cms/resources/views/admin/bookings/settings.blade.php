@extends('admin.layouts.app')
@section('title','Booking settings')
@section('heading','Booking availability')
@section('content')
<p style="color:var(--muted);margin-top:0">Fully self-hosted. Changes apply to the public calendar immediately — no Cal.com / Calendly.</p>

<form method="post" action="{{ route('admin.bookings.settings.update') }}" class="card">
  @csrf
  <h3>Meeting settings</h3>
  <div class="row">
    <div>
      <label>Book a Call enabled</label>
      <label style="display:flex;gap:8px;align-items:center;color:var(--text);margin-top:8px">
        <input type="checkbox" name="enabled" value="1" style="width:auto" @checked($settings['enabled'] ?? true)> Show booking button
      </label>
    </div>
    <div><label>Duration (minutes)</label><input type="number" name="duration" min="5" max="480" step="5" value="{{ old('duration', $settings['duration']) }}"></div>
    <div><label>Buffer between meetings (minutes)</label><input type="number" name="buffer" min="0" max="240" step="5" value="{{ old('buffer', $settings['buffer']) }}"></div>
    <div><label>Slot step (minutes)</label><input type="number" name="slot_interval" min="5" max="240" step="5" value="{{ old('slot_interval', $settings['slot_interval']) }}"></div>
    <div><label>Minimum notice (minutes)</label><input type="number" name="min_notice" min="0" max="10080" step="15" value="{{ old('min_notice', $settings['min_notice']) }}"></div>
    <div><label>Bookable window (days ahead)</label><input type="number" name="max_days" min="1" max="365" value="{{ old('max_days', $settings['max_days']) }}"></div>
    <div><label>Owner timezone</label><input name="timezone" value="{{ old('timezone', $settings['timezone']) }}" placeholder="Asia/Tehran"></div>
    <div>
      <label>Email notifications (optional SMTP)</label>
      <label style="display:flex;gap:8px;align-items:center;color:var(--text);margin-top:8px"><input type="checkbox" name="owner_notify" value="1" style="width:auto" @checked($settings['owner_notify'] ?? true)> Notify me</label>
      <label style="display:flex;gap:8px;align-items:center;color:var(--text);margin-top:8px"><input type="checkbox" name="visitor_notify" value="1" style="width:auto" @checked($settings['visitor_notify'] ?? true)> Notify visitor</label>
    </div>
  </div>
  <button type="submit" style="margin-top:12px">Save settings</button>
</form>

<form method="post" action="{{ route('admin.bookings.availability') }}" class="card" id="availability">
  @csrf
  <h3>Weekly availability ({{ $settings['timezone'] }})</h3>
  <p style="color:var(--muted);font-size:13px">Weekday uses PHP week numbers: 0=Sunday … 6=Saturday. Add multiple rows for split shifts.</p>
  <div id="rule-rows">
    @forelse ($rules as $i => $rule)
      <div class="row rule-row" style="margin-bottom:8px;align-items:end">
        <div>
          <label>Weekday</label>
          <select name="rules[{{ $i }}][weekday]">
            @foreach ($weekdays as $num => $label)
              <option value="{{ $num }}" @selected((int)$rule->weekday === $num)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div><label>Start</label><input type="time" name="rules[{{ $i }}][start_time]" value="{{ substr((string)$rule->start_time,0,5) }}"></div>
        <div><label>End</label><input type="time" name="rules[{{ $i }}][end_time]" value="{{ substr((string)$rule->end_time,0,5) }}"></div>
        <div>
          <label>Active</label>
          <label style="display:flex;gap:8px;align-items:center;color:var(--text);margin-top:8px">
            <input type="checkbox" name="rules[{{ $i }}][is_active]" value="1" style="width:auto" @checked($rule->is_active)>
          </label>
        </div>
      </div>
    @empty
      <p style="color:var(--muted)">No rules yet — add a window below.</p>
    @endforelse
  </div>
  <button type="button" class="btn-secondary" id="add-rule" style="margin-top:8px">Add window</button>
  <button type="submit" style="margin-top:12px;margin-inline-start:8px">Save weekly hours</button>
</form>

<div class="card">
  <h3>Blocked dates / vacations</h3>
  <form method="post" action="{{ route('admin.bookings.blocks.store') }}" class="row" style="align-items:end">
    @csrf
    <div><label>Date</label><input type="date" name="date" required></div>
    <div>
      <label>All day</label>
      <label style="display:flex;gap:8px;align-items:center;color:var(--text);margin-top:8px"><input type="checkbox" name="all_day" value="1" style="width:auto" checked> Full day</label>
    </div>
    <div><label>Start time</label><input type="time" name="start_time"></div>
    <div><label>End time</label><input type="time" name="end_time"></div>
    <div><label>Reason</label><input name="reason" placeholder="Holiday / travel"></div>
    <div><button type="submit">Block</button></div>
  </form>
  <table style="margin-top:14px">
    <thead><tr><th>From</th><th>To</th><th>Reason</th><th></th></tr></thead>
    <tbody>
      @forelse ($blocks as $block)
        <tr>
          <td>{{ $block->starts_at?->timezone($settings['timezone'])->format('Y-m-d H:i') }}</td>
          <td>{{ $block->ends_at?->timezone($settings['timezone'])->format('Y-m-d H:i') }}</td>
          <td>{{ $block->reason ?: ($block->all_day ? 'All day' : '—') }}</td>
          <td>
            <form method="post" action="{{ route('admin.bookings.blocks.destroy', $block) }}" onsubmit="return confirm('Remove this block?')">
              @csrf @method('DELETE')
              <button class="btn-danger" type="submit">Remove</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="4">No blocked slots.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<script>
(function () {
  var wrap = document.getElementById('rule-rows');
  var btn = document.getElementById('add-rule');
  if (!wrap || !btn) return;
  var weekdays = @json($weekdays);
  btn.addEventListener('click', function () {
    var i = wrap.querySelectorAll('.rule-row').length;
    var opts = Object.keys(weekdays).map(function (k) {
      return '<option value="' + k + '">' + weekdays[k] + '</option>';
    }).join('');
    var div = document.createElement('div');
    div.className = 'row rule-row';
    div.style.marginBottom = '8px';
    div.innerHTML =
      '<div><label>Weekday</label><select name="rules[' + i + '][weekday]">' + opts + '</select></div>' +
      '<div><label>Start</label><input type="time" name="rules[' + i + '][start_time]" value="10:00"></div>' +
      '<div><label>End</label><input type="time" name="rules[' + i + '][end_time]" value="13:00"></div>' +
      '<div><label>Active</label><label style="display:flex;gap:8px;align-items:center;color:var(--text);margin-top:8px"><input type="checkbox" name="rules[' + i + '][is_active]" value="1" style="width:auto" checked></label></div>';
    wrap.appendChild(div);
  });
})();
</script>
@endsection
