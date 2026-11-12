{{-- Top-right cluster: guide launcher + language switch (English left / فارسی right). --}}
<div class="az-header-end">
  {{-- English stays physically left / فارسی right in both languages (CSS direction:ltr). --}}
  <div class="az-lang-switch" role="group" aria-label="{{ $strings['lang.switcher'] ?? 'Language' }}" data-i18n-attr="aria-label:lang.switcher">
    <button type="button" class="az-lang-switch__btn {{ ($locale ?? 'en') === 'en' ? 'is-active' : '' }}" data-lang="en" aria-pressed="{{ ($locale ?? 'en') === 'en' ? 'true' : 'false' }}" aria-label="Read this site in English" data-i18n-attr="aria-label:lang.aria.en">English</button>
    <button type="button" class="az-lang-switch__btn {{ ($locale ?? 'en') === 'fa' ? 'is-active' : '' }}" data-lang="fa" aria-pressed="{{ ($locale ?? 'en') === 'fa' ? 'true' : 'false' }}" aria-label="نمایش سایت به فارسی" data-i18n-attr="aria-label:lang.aria.fa">فارسی</button>
  </div>
  <button
    type="button"
    class="az-guide-launch"
    data-az-guide-launch
    aria-label="{{ $strings['guide.launch'] ?? 'Open site guide' }}"
    data-i18n-attr="aria-label:guide.launch"
    title="{{ $strings['guide.launch'] ?? 'Open site guide' }}"
  >
    <span class="az-guide-launch__sprite" aria-hidden="true"></span>
  </button>
</div>
