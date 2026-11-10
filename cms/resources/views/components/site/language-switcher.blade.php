{{-- English stays physically left / فارسی right in both languages (CSS direction:ltr). --}}
<div class="az-lang-switch" role="group" aria-label="{{ $strings['lang.switcher'] ?? 'Language' }}" data-i18n-attr="aria-label:lang.switcher">
  <button type="button" class="az-lang-switch__btn {{ ($locale ?? 'en') === 'en' ? 'is-active' : '' }}" data-lang="en" aria-pressed="{{ ($locale ?? 'en') === 'en' ? 'true' : 'false' }}" aria-label="Read this site in English" data-i18n-attr="aria-label:lang.aria.en">English</button>
  <button type="button" class="az-lang-switch__btn {{ ($locale ?? 'en') === 'fa' ? 'is-active' : '' }}" data-lang="fa" aria-pressed="{{ ($locale ?? 'en') === 'fa' ? 'true' : 'false' }}" aria-label="نمایش سایت به فارسی" data-i18n-attr="aria-label:lang.aria.fa">فارسی</button>
</div>
