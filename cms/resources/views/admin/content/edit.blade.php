@extends('admin.layouts.app')
@section('title','Site content')
@section('heading','Site content')
@section('content')
<nav class="admin-pills" aria-label="Content sections">
  <a class="btn btn-secondary" href="{{ route('admin.content.edit') }}" @if(!$group) aria-current="page" @endif>Site settings</a>
  @foreach($groups as $name => $rows)
    <a class="btn btn-secondary" href="{{ route('admin.content.edit', ['group' => $name]) }}" @if($group === $name) aria-current="page" @endif>{{ $name }} <span class="badge">{{ count($rows) }}</span></a>
  @endforeach
</nav>

@if($group)
<form method="post" action="{{ route('admin.content.update') }}">
  @csrf
  <div class="card">
    <h3>{{ $group }} · English and Persian</h3>
    <label for="content-search">Find a field by its key or current text</label>
    <input id="content-search" type="search" data-admin-filter placeholder="e.g. title, booking, تماس">
    <p class="admin-hint">Clear a field to use its default text. Keep placeholders like <code>{name}</code> in translations.</p>
  </div>
  @foreach($groups[$group] as $i => $entry)
    <div class="card" data-admin-filter-row>
      <h3>{{ $entry['key'] }}</h3>
      <input type="hidden" name="texts[{{ $i }}][key]" value="{{ $entry['key'] }}">
      <div class="row">
        <label>English<textarea name="texts[{{ $i }}][en]" placeholder="{{ $entry['default_en'] }}">{{ old("texts.$i.en", $entry['en']) }}</textarea></label>
        <label>فارسی<textarea name="texts[{{ $i }}][fa]" placeholder="{{ $entry['default_fa'] }}" dir="rtl">{{ old("texts.$i.fa", $entry['fa']) }}</textarea></label>
      </div>
    </div>
  @endforeach
  <div class="admin-save"><button type="submit">Save draft</button></div>
</form>
<script>
  document.querySelector('[data-admin-filter]').addEventListener('input', function (event) {
    const query = event.target.value.trim().toLocaleLowerCase();
    document.querySelectorAll('[data-admin-filter-row]').forEach(function (row) {
      const fields = Array.from(row.querySelectorAll('textarea'), field => field.value);
      row.hidden = ![row.querySelector('h3').textContent, ...fields].some(value => value.toLocaleLowerCase().includes(query));
    });
  });
</script>
@else
<form method="post" action="{{ route('admin.content.update') }}">
  @csrf
  <datalist id="published-images">
    @foreach($imagePaths as $image)
      <option value="{{ $image->public_path }}">{{ $image->original_name }}</option>
    @endforeach
  </datalist>
  <section class="card">
    <h3>Profile, team &amp; contact</h3>
    <p class="admin-hint">Edit your name and bio in Home and About. Choose a published image or enter its local path. Upload and publish new images in <a href="{{ route('admin.media.index') }}">Media</a>.</p>
    <div class="row">
      <label>Profile photo path<input list="published-images" name="profile[photo]" value="{{ old('profile.photo', $settings['profile']['photo'] ?? '') }}" placeholder="assets/img/amir/profile.webp"></label>
      <label>Site logo path<input list="published-images" name="profile[logo]" value="{{ old('profile.logo', $settings['profile']['logo'] ?? '') }}" placeholder="assets/img/amir/az-monogram-display.webp"></label>
      <label>Team website<input name="team[url]" type="url" value="{{ old('team.url', $settings['team']['url'] ?? config('novin.url')) }}"></label>
      @foreach(['email' => 'Email', 'linkedin' => 'LinkedIn', 'github' => 'GitHub', 'telegram' => 'Telegram', 'telegram_handle' => 'Telegram handle'] as $key => $label)
        <label>{{ $label }}<input name="social[{{ $key }}]" value="{{ old('social.'.$key, $settings['social'][$key] ?? '') }}"></label>
      @endforeach
    </div>
  </section>

  <section class="card">
    <h3>Homepage backgrounds</h3>
    <p class="admin-hint">Set a desktop and mobile image for each slide. Empty fields use the bundled images.</p>
    <div class="row">
      @for($slide = 1; $slide <= 4; $slide++)
        @foreach(['desktop' => 'Desktop', 'mobile' => 'Mobile'] as $device => $label)
          <label>Slide {{ $slide }} · {{ $label }}<input list="published-images" name="home_backgrounds[{{ $slide }}][{{ $device }}]" value="{{ old("home_backgrounds.$slide.$device", $settings['home_backgrounds'][$slide][$device] ?? '') }}" placeholder="{{ sprintf('assets/img/amir/home/home-%sbg-%02d.webp', $device === 'mobile' ? 'mobile-' : '', $slide) }}"></label>
        @endforeach
      @endfor
    </div>
  </section>

  <section class="card">
    <h3>About skills</h3>
    <div class="row">
      @foreach(config('portfolio.about_skills') as $key => $default)
        <label>{{ ucfirst($key) }} <span class="admin-hint">· comma-separated</span><input name="about_skills[{{ $key }}]" value="{{ old('about_skills.'.$key, implode(', ', $settings['about_skills'][$key] ?? $default)) }}"></label>
      @endforeach
    </div>
  </section>

  <section class="card">
    <h3>Miro’s knowledge</h3>
    <p class="admin-hint">Add public facts about yourself that Miro can use in answers. These fields become part of the published content.</p>
    <div class="row">
      <label>English<textarea name="assistant_knowledge[en]" maxlength="450">{{ old('assistant_knowledge.en', $settings['assistant_knowledge']['en'] ?? '') }}</textarea></label>
      <label>فارسی<textarea name="assistant_knowledge[fa]" maxlength="450" dir="rtl">{{ old('assistant_knowledge.fa', $settings['assistant_knowledge']['fa'] ?? '') }}</textarea></label>
      <label>Projects Miro should mention first
        <small id="featured-help" class="admin-hint">Select up to 30 projects. On desktop, hold Ctrl or Command to select more than one.</small>
        <input type="hidden" name="featured_slugs_present" value="1">
        <select name="featured_slugs[]" multiple size="6" aria-describedby="featured-help">
          @foreach($projects as $project)
            <option value="{{ $project['slug'] }}" @selected(in_array($project['slug'], old('featured_slugs', $settings['featured_slugs'] ?? []), true))>{{ $project['title'] }}</option>
          @endforeach
        </select>
      </label>
    </div>
  </section>
  <section class="card">
    <h3>Booking settings</h3>
    <p class="admin-hint"><a href="{{ route('admin.bookings.settings') }}">Manage availability, booking visibility and SMS notifications</a>. <a href="{{ route('admin.content.edit', ['group' => 'Booking']) }}">Edit booking copy</a>.</p>
  </section>
  <div class="admin-save"><button type="submit">Save draft</button></div>
</form>
@endif
@endsection
