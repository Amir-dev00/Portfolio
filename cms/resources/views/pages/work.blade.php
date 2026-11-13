          <!-- WORK VIEW -->
          <section class="az-view {{ ($viewName ?? '') === 'work' ? 'is-active is-visible' : '' }}" id="view-work" data-view="work" aria-label="{{ $strings['work.aria'] ?? 'Work' }}" data-i18n-attr="aria-label:work.aria" @if(($viewName ?? '') !== 'work') hidden aria-hidden="true" @endif>
            <div class="az-panel az-panel_work">
              <nav class="az-work-sections" aria-label="{{ $locale === 'fa' ? 'بخش‌های کار' : 'Work sections' }}" data-i18n-attr="aria-label:work.sections">
                <a href="#work-projects" data-i18n="work.projects.label">{{ $locale === 'fa' ? 'پروژه‌ها' : 'Projects' }}</a>
                <a href="#work-experience" data-i18n="work.experience.title">{{ $locale === 'fa' ? 'سوابق کاری' : 'Work experience' }}</a>
              </nav>
              <section id="work-projects" aria-labelledby="work-projects-title" tabindex="-1">
                <p class="az-panel__eyebrow" data-i18n="work.eyebrow">{{ $strings['work.eyebrow'] ?? 'Selected work' }}</p>
                <h1 id="work-projects-title" class="az-panel__title" data-i18n="work.title">{{ $strings['work.title'] ?? 'Selected projects.' }}</h1>
                <p class="az-panel__lead" data-i18n="work.lead">{{ $strings['work.lead'] ?? 'A selection of websites, applications, APIs, and automation tools.' }}</p>
                <div class="az-work-browser" id="az-work-browser"></div>
              </section>
              @include('pages.work-experience')
            </div>
          </section>
