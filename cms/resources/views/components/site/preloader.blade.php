{{-- AZ circular brand loader. Always English — never translate this block. --}}
<div class="preloader az-preloader az-preloader--ring" id="js-preloader" lang="en" dir="ltr" data-arts-theme-text="light" aria-label="Loading Amirhossein's portfolio">
  <div class="preloader__content az-preloader__layout">
    <div class="preloader__header az-preloader__center">
      <div class="az-preloader__disc" aria-hidden="true">
        <div class="az-preloader__image-shell">
          <img class="az-preloader__image" src="{{ $azLogoSrc ?? asset('assets/img/amir/az-monogram-blue.webp') }}" width="720" height="720" alt="" decoding="async" fetchpriority="high"/>
        </div>
        <svg class="az-preloader__ring" viewBox="0 0 360 360" focusable="false" aria-hidden="true">
          <defs>
            <linearGradient id="az-ring-ice" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="var(--az-text)"/>
              <stop offset="45%" stop-color="var(--az-accent)"/>
              <stop offset="100%" stop-color="var(--az-text)"/>
            </linearGradient>
          </defs>
          <circle class="az-preloader__ring-track" cx="180" cy="180" r="162"/>
          <circle class="az-preloader__ring-fill" cx="180" cy="180" r="162" pathLength="100"/>
        </svg>
        <span class="az-preloader__ring-head" aria-hidden="true"></span>
      </div>

      <span class="az-preloader__load-label">LOADING EXPERIENCE</span>
      <div class="az-preloader__percentage" data-az-preloader-meter role="progressbar"
           aria-label="Loading progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
        <span data-az-preloader-value>0</span>
        <span class="az-preloader__counter-sep" aria-hidden="true">/</span>
        <span class="az-preloader__counter-total" aria-hidden="true">100</span>
      </div>
      {{-- Rhye still receives its original counter hooks. Its writes stay offscreen. --}}
      <div class="preloader__counter az-preloader__native-counter" aria-hidden="true" hidden>
        <span class="preloader__counter-number preloader__counter-current">0</span>
        <span class="preloader__counter-divider">/</span>
        <span class="preloader__counter-number preloader__counter-total">100</span>
      </div>
      <div class="az-preloader__identity">
        <div class="preloader__heading az-preloader__name">AMIRHOSSEIN ZAMANZADE</div>
        <div class="preloader__subline az-preloader__role">SOFTWARE ENGINEER</div>
      </div>
      <div class="az-preloader__retry" data-az-preloader-retry hidden>
        <p class="az-preloader__retry-msg" data-az-preloader-retry-msg>Still loading. Check your connection and try again.</p>
        <button type="button" class="az-preloader__retry-btn" data-az-preloader-retry-btn>Retry</button>
      </div>
    </div>
  </div>
  <div class="preloader__circle" aria-hidden="true"></div>
</div>
