          <!-- WORK VIEW -->
          <section class="az-view {{ ($viewName ?? '') === 'work' ? 'is-active is-visible' : '' }}" id="view-work" data-view="work" aria-label="{{ $strings['work.aria'] ?? 'Work' }}" data-i18n-attr="aria-label:work.aria" @if(($viewName ?? '') !== 'work') hidden aria-hidden="true" @endif>
            <div class="az-panel az-panel_work">
              <p class="az-panel__eyebrow" data-i18n="work.eyebrow">{{ $strings['work.eyebrow'] ?? 'Selected work' }}</p>
              <h1 class="az-panel__title" data-i18n="work.title">{{ $strings['work.title'] ?? 'Selected projects.' }}</h1>
              <p class="az-panel__lead" data-i18n="work.lead">{{ $strings['work.lead'] ?? 'A selection of websites, applications, APIs, and automation tools.' }}</p>
              <div class="az-work-browser" id="az-work-browser"></div>
            </div>
          </section>
