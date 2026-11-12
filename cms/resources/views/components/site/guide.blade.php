{{-- Homepage guided tour — WebP sprite companion (no GLB / WebGL). --}}
{{-- Floating sprite layer: parks on the launcher, walks to tour targets. --}}
<div
  class="az-guide-companion"
  data-az-guide-companion
  aria-hidden="true"
>
  <span class="az-guide-companion__sprite is-idle" data-az-guide-sprite data-pose="0"></span>
</div>

<div class="az-guide" id="az-guide" data-az-guide hidden>
  <div class="az-guide__scrim" data-az-guide-scrim aria-hidden="true"></div>
  <div class="az-guide__spotlight" data-az-guide-spotlight aria-hidden="true"></div>

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
