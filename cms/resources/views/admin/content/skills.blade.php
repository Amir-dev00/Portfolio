@extends('admin.layouts.app')
@section('title', __('Skills'))
@section('heading', __('Skills'))
@section('content')
<p class="admin-hint">{{ __('Edit skill titles and items here. Save the draft, then publish it to update your site.') }}</p>

<form method="post" action="{{ route('admin.content.update') }}" data-skill-editor>
  @csrf
  <section class="card" aria-labelledby="skills-section-title">
    <h3 id="skills-section-title">{{ __('Skills section title') }}</h3>
    @include('admin.content.skill-heading-fields', ['index' => 'section', 'heading' => $headings['section']])
  </section>
  <div class="row">
    @foreach($skills as $category => $items)
      <section class="card" aria-labelledby="skills-{{ $category }}" data-skill-group>
        <h3 id="skills-{{ $category }}">{{ __(ucfirst($category)) }}</h3>
        @include('admin.content.skill-heading-fields', ['index' => $category, 'heading' => $headings[$category]])
        {{-- Keep empty categories in the submitted draft so deleted skills stay deleted. --}}
        <input type="hidden" name="about_skill_items[{{ $category }}][]" value="">
        <div data-skill-list>
          @foreach(array_filter((array) old('about_skill_items.'.$category, $items), 'is_string') as $item)
            <div class="admin-pills" data-skill-row>
              <input name="about_skill_items[{{ $category }}][]" aria-label="{{ __('Skill name') }}" dir="auto" maxlength="100" value="{{ $item }}" style="width:auto;flex:1;min-width:0">
              <button type="button" class="btn-secondary" data-skill-remove>{{ __('Remove') }}</button>
            </div>
          @endforeach
        </div>
        <div class="admin-pills">
          <button type="button" class="btn-secondary" data-skill-add>{{ __('Add skill') }}</button>
        </div>
        <template>
          <div class="admin-pills" data-skill-row>
            <input name="about_skill_items[{{ $category }}][]" aria-label="{{ __('Skill name') }}" dir="auto" maxlength="100" style="width:auto;flex:1;min-width:0">
            <button type="button" class="btn-secondary" data-skill-remove>{{ __('Remove') }}</button>
          </div>
        </template>
      </section>
    @endforeach
  </div>
  <div class="admin-save"><button type="submit">{{ __('Save draft') }}</button></div>
</form>

<script>
  const skillEditor = document.querySelector('[data-skill-editor]');
  skillEditor.addEventListener('click', function (event) {
    const button = event.target.closest('[data-skill-add], [data-skill-remove]');
    if (!button) return;

    const group = button.closest('[data-skill-group]');
    const list = group.querySelector('[data-skill-list]');
    if (button.hasAttribute('data-skill-add')) {
      const row = group.querySelector('template').content.firstElementChild.cloneNode(true);
      list.appendChild(row);
      row.querySelector('input').focus();
      return;
    }

    const row = button.closest('[data-skill-row]');
    const next = row.nextElementSibling || row.previousElementSibling;
    row.remove();
    (next ? next.querySelector('input') : group.querySelector('[data-skill-add]')).focus();
  });
</script>
@endsection
