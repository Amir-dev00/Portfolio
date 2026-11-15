<input type="hidden" name="texts[{{ $index }}][key]" value="{{ $heading['key'] }}">
<div class="row">
  <label>{{ __('English title') }}
    <input name="texts[{{ $index }}][en]" dir="ltr" lang="en" value="{{ old('texts.'.$index.'.en', $heading['en']) }}" placeholder="{{ $heading['default_en'] }}">
  </label>
  <label>{{ __('Persian title') }}
    <input name="texts[{{ $index }}][fa]" dir="rtl" lang="fa" value="{{ old('texts.'.$index.'.fa', $heading['fa']) }}" placeholder="{{ $heading['default_fa'] }}">
  </label>
</div>
