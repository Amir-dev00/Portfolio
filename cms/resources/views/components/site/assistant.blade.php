<div class="az-assistant az-assistant--home" data-az-assistant-home hidden>
  <div class="az-assistant__panel">
    @include('components.site.assistant-composer', ['inputId' => 'az-assistant-input'])
  </div>

  {{-- Mobile homepage only: two text links under the collapsed composer. --}}
  <nav
    class="az-selected-work"
    data-az-selected-work
    hidden
    aria-label="Selected work"
    data-i18n-attr="aria-label:home.selectedWork"
  >
    <span class="az-selected-work__label" data-i18n="home.selectedWork">Selected work</span>
    <div class="az-selected-work__links" data-az-selected-work-links></div>
  </nav>
</div>
