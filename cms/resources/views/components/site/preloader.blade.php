{{-- AZ circular brand loader. Always English — never translate this block. --}}
<div class="preloader az-preloader az-preloader--ring" id="js-preloader" lang="en" dir="ltr" data-arts-theme-text="light" aria-label="Loading Amirhossein's portfolio">
  <div class="preloader__content az-preloader__layout">
    <div class="preloader__header az-preloader__center">
      <div class="az-preloader__disc" aria-hidden="true">
        <div class="az-preloader__image-shell">
          <img class="az-preloader__image" src="{{ asset('assets/img/amir/az-logo.webp') }}" width="1254" height="1254" alt="" decoding="async" fetchpriority="high"/>
        </div>
        <svg class="az-preloader__ring" viewBox="0 0 360 360" focusable="false" aria-hidden="true">
          <defs>
            <linearGradient id="az-ring-gold" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#FAE6BD"/>
              <stop offset="45%" stop-color="#CB9251"/>
              <stop offset="100%" stop-color="#FFF0CD"/>
            </linearGradient>
          </defs>
          <circle class="az-preloader__ring-track" cx="180" cy="180" r="162"/>
          <circle class="az-preloader__ring-fill" cx="180" cy="180" r="162" pathLength="100"/>
        </svg>
        <span class="az-preloader__ring-head" aria-hidden="true"></span>
      </div>

      <span class="az-preloader__load-label">LOADING EXPERIENCE</span>
      <div class="preloader__counter az-preloader__percentage" aria-label="Loading progress">
        <span class="preloader__counter-number preloader__counter-current">0</span>
        <span class="preloader__counter-divider az-preloader__counter-sep" aria-hidden="true">/</span>
        <span class="preloader__counter-number preloader__counter-total">100</span>
      </div>
      <div class="az-preloader__identity">
        <div class="preloader__heading az-preloader__name">AMIRHOSSEIN ZAMANZADE</div>
        <div class="preloader__subline az-preloader__role">SOFTWARE ENGINEER</div>
      </div>
    </div>
  </div>
  <div class="preloader__circle" aria-hidden="true"></div>
</div>
