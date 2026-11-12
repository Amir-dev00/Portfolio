{{-- Top-right cluster: guide launcher + language switch (English left / فارسی right). --}}
<div class="az-header-end">
  <button
    type="button"
    class="az-guide-launch"
    data-az-guide-launch
    aria-label="{{ $strings['guide.launch'] ?? 'Open site guide' }}"
    data-i18n-attr="aria-label:guide.launch"
  >
    <span class="az-guide-launch__orb" aria-hidden="true">
      <svg class="az-guide-launch__icon" viewBox="0 0 32 32" width="22" height="22" focusable="false">
        <circle cx="16" cy="16" r="15" fill="currentColor" opacity="0.12"/>
        <path fill="currentColor" d="M16 6.2c2.4 0 4.3 1.9 4.3 4.2S18.4 14.6 16 14.6 11.7 12.7 11.7 10.4 13.6 6.2 16 6.2zm0 10.2c3.8 0 7.1 2.1 7.1 4.1v1.3H8.9V20.5c0-2 3.3-4.1 7.1-4.1z"/>
        <path fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" d="M22.2 9.2c1.4-.4 2.6.5 2.8 1.8M9.8 9.2C8.4 8.8 7.2 9.7 7 11"/>
      </svg>
    </span>
  </button>

  {{-- English stays physically left / فارسی right in both languages (CSS direction:ltr). --}}
  <div class="az-lang-switch" role="group" aria-label="{{ $strings['lang.switcher'] ?? 'Language' }}" data-i18n-attr="aria-label:lang.switcher">
    <button type="button" class="az-lang-switch__btn {{ ($locale ?? 'en') === 'en' ? 'is-active' : '' }}" data-lang="en" aria-pressed="{{ ($locale ?? 'en') === 'en' ? 'true' : 'false' }}" aria-label="Read this site in English" data-i18n-attr="aria-label:lang.aria.en">English</button>
    <button type="button" class="az-lang-switch__btn {{ ($locale ?? 'en') === 'fa' ? 'is-active' : '' }}" data-lang="fa" aria-pressed="{{ ($locale ?? 'en') === 'fa' ? 'true' : 'false' }}" aria-label="نمایش سایت به فارسی" data-i18n-attr="aria-label:lang.aria.fa">فارسی</button>
  </div>
</div>
