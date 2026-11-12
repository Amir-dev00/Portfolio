{{-- Homepage bilingual guided tour shell. Model loads only after the visitor opens the guide. --}}
<div
  class="az-guide"
  id="az-guide"
  data-az-guide
  data-model-url="{{ asset('assets/models/az-astronaut-guide.glb') }}{{ is_file(public_path('assets/models/az-astronaut-guide.glb')) ? '?v='.filemtime(public_path('assets/models/az-astronaut-guide.glb')) : '' }}"
  data-viewer-url="{{ asset('js/az-guide-viewer.mjs') }}{{ is_file(public_path('js/az-guide-viewer.mjs')) ? '?v='.filemtime(public_path('js/az-guide-viewer.mjs')) : '' }}"
  hidden
>
  <div class="az-guide__scrim" data-az-guide-scrim aria-hidden="true"></div>
  <div class="az-guide__spotlight" data-az-guide-spotlight aria-hidden="true"></div>

  <canvas class="az-guide__canvas" data-az-guide-canvas width="160" height="160" aria-hidden="true" hidden></canvas>
  <div class="az-guide__fallback" data-az-guide-fallback aria-hidden="true" hidden>
    <span class="az-guide__fallback-glyph" aria-hidden="true">🚀</span>
  </div>

  <div
    class="az-guide__invite"
    data-az-guide-invite
    role="dialog"
    aria-modal="true"
    aria-labelledby="az-guide-invite-title"
    hidden
  >
    <p class="az-guide__invite-title" id="az-guide-invite-title" data-i18n="guide.invite.title">Want a quick tour?</p>
    <p class="az-guide__invite-body" data-i18n="guide.invite.body">I’ll point out language, chat, and navigation — about 30 seconds.</p>
    <div class="az-guide__invite-actions">
      <button type="button" class="az-guide__btn az-guide__btn--ghost" data-az-guide-decline data-i18n="guide.notNow">Not now</button>
      <button type="button" class="az-guide__btn az-guide__btn--primary" data-az-guide-start data-i18n="guide.start">Start tour</button>
    </div>
  </div>

  <div
    class="az-guide__callout"
    data-az-guide-callout
    role="dialog"
    aria-modal="true"
    aria-labelledby="az-guide-callout-title"
    hidden
  >
    <p class="az-guide__step" data-az-guide-step>1 / 3</p>
    <p class="az-guide__callout-title" id="az-guide-callout-title" data-az-guide-title></p>
    <p class="az-guide__callout-body" data-az-guide-body></p>
    <div class="az-guide__callout-actions">
      <button type="button" class="az-guide__btn az-guide__btn--ghost" data-az-guide-skip data-i18n="guide.skip">Skip</button>
      <button type="button" class="az-guide__btn az-guide__btn--primary" data-az-guide-next data-i18n="guide.next">Next</button>
    </div>
  </div>
</div>
