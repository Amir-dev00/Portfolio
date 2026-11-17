          <!-- WORK VIEW -->
          <section class="az-view {{ ($viewName ?? '') === 'work' ? 'is-active is-visible' : '' }}" id="view-work" data-view="work" aria-label="{{ $strings['work.aria'] ?? 'Work' }}" data-i18n-attr="aria-label:work.aria" @if(($viewName ?? '') !== 'work') hidden aria-hidden="true" @endif>
            <div class="az-panel az-panel_work">
              @if (!empty($experience))
                <nav class="az-work-sections" aria-label="{{ $locale === 'fa' ? 'بخش‌های کار' : 'Work sections' }}" data-i18n-attr="aria-label:work.sections">
                  <a href="#work-experience" data-i18n="work.experience.title">{{ $locale === 'fa' ? 'سوابق کاری' : 'Work experience' }}</a>
                  <a href="#work-projects" data-i18n="work.projects.label">{{ $locale === 'fa' ? 'پروژه‌ها' : 'Projects' }}</a>
                </nav>
                @include('pages.work-experience')
              @endif
              <section id="work-projects" aria-labelledby="work-projects-title" tabindex="-1">
                <p class="az-panel__eyebrow" data-i18n="work.eyebrow">{{ $strings['work.eyebrow'] ?? 'Selected work' }}</p>
                <h1 id="work-projects-title" class="az-panel__title" data-i18n="work.title">{{ $strings['work.title'] ?? 'Selected projects' }}</h1>
                <p class="az-panel__lead" data-i18n="work.lead">{{ $strings['work.lead'] ?? 'A selection of websites, applications, APIs, and automation tools.' }}</p>
                @if ($workProjects)
                  <div class="az-work-mobile" lang="{{ $locale }}" dir="{{ $locale === 'fa' ? 'rtl' : 'ltr' }}">
                    <ul id="az-work-mobile-track" class="az-work-mobile__track" role="list" tabindex="0" aria-labelledby="work-projects-title">
                      @foreach ($workProjects as $workProject)
                        <li data-mobile-project="{{ $workProject['slug'] }}">
                          <a class="az-work-mobile__card" href="{{ $projectUrl($workProject['slug']) }}">
                            @if ($workProject['image'])
                              <div class="az-work-mobile__cover">
                                <img src="{{ $workProject['image'] }}" alt="" width="960" height="540" loading="lazy" decoding="async">
                              </div>
                            @endif
                            <div class="az-work-mobile__copy">
                              @if ($workProject['category'])
                                <p class="az-work-mobile__category" dir="auto">{{ $workProject['category'] }}</p>
                              @endif
                              <h2 dir="auto">{{ $workProject['title'] }}</h2>
                              @if ($workProject['shortDescription'])
                                <p class="az-work-mobile__description" dir="auto">{{ $workProject['shortDescription'] }}</p>
                              @endif
                              <span class="az-work-mobile__arrow" aria-hidden="true">↗</span>
                            </div>
                          </a>
                        </li>
                      @endforeach
                    </ul>
                    @if (count($workProjects) > 1)
                      <div class="az-work-mobile__controls" hidden>
                        <button type="button" data-mobile-step="-1" aria-controls="az-work-mobile-track" aria-label="{{ $locale === 'fa' ? 'پروژه قبلی' : 'Previous project' }}" aria-disabled="true">
                          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="m14 6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <span class="az-work-mobile__count" dir="ltr" aria-live="polite" aria-atomic="true"></span>
                        <button type="button" data-mobile-step="1" aria-controls="az-work-mobile-track" aria-label="{{ $locale === 'fa' ? 'پروژه بعدی' : 'Next project' }}">
                          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="m10 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                      </div>
                    @endif
                  </div>
                @endif
                <div class="az-work-browser" id="az-work-browser" @if ($workProjects) data-az-desktop-work @endif>
                  @if ($workProjects)
                    <ul>
                      @foreach ($workProjects as $workProject)
                        <li><a href="{{ $projectUrl($workProject['slug']) }}">{{ $workProject['title'] }}</a></li>
                      @endforeach
                    </ul>
                  @endif
                </div>
              </section>
            </div>
          </section>
