          <!-- ABOUT VIEW -->
          <section class="az-view {{ ($viewName ?? '') === 'about' ? 'is-active is-visible' : '' }}" id="view-about" data-view="about" aria-label="{{ $strings['about.aria'] ?? 'About' }}" data-i18n-attr="aria-label:about.aria" @if(($viewName ?? '') !== 'about') hidden aria-hidden="true" @endif>
            <div class="az-panel az-panel_about">
              <div class="az-about">
                <header class="az-about__intro">
                  <p class="az-about__eyebrow" data-i18n="about.eyebrow">{{ $strings['about.eyebrow'] ?? ($locale === 'fa' ? 'درباره' : 'About') }}</p>
                  <h1 class="az-about__title" data-i18n="home.profile.name">{{ $strings['home.profile.name'] ?? ($locale === 'fa' ? 'امیرحسین زمان‌زاده' : 'Amirhossein Zamanzade') }}</h1>
                  <p class="az-about__role" data-i18n="about.title">{{ $strings['about.title'] ?? ($locale === 'fa' ? 'مهندس نرم‌افزار' : 'Software engineer.') }}</p>
                </header>

                <figure class="az-about__portrait">
                  <img src="{{ $profilePhoto ?? asset('assets/img/amir/profile.webp') }}" width="596" height="596" alt="{{ $strings['about.portrait.alt'] ?? 'Portrait of Amirhossein Zamanzade' }}" decoding="async" data-i18n-attr="alt:about.portrait.alt"/>
                </figure>

                <div class="az-about__copy">
                  <p class="az-panel__lead az-about__lead" data-i18n="about.p1">@if(isset($strings['about.p1'])){{ $strings['about.p1'] }}@else
                    I’m Amirhossein Zamanzade, a Computer Science student and Full-Stack Developer focused on fast, scalable, and user-centric products. I care about clean architecture, modern tooling, and interfaces that feel refined.
                  @endif</p>
                  <p class="az-panel__lead az-about__lead az-about__lead_last" data-i18n="about.p2">@if(isset($strings['about.p2'])){{ $strings['about.p2'] }}@else
                    This portfolio reflects my learning journey across web, desktop, APIs, and automation — and a place to connect for collaboration.
                  @endif</p>
                  <div class="az-about__actions">
                    <a class="az-about__action az-about__action_primary" href="{{ $navPaths['work'] }}" data-nav="work">
                      <span data-i18n="about.workLink">{{ $locale === 'fa' ? 'دیدن کارها' : 'View my work' }}</span>
                      <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12 12 4M5 4h7v7"/></svg>
                    </a>
                    <a class="az-about__action" href="{{ $navPaths['contact'] }}" data-nav="contact" data-i18n="nav.contact">{{ $strings['nav.contact'] ?? ($locale === 'fa' ? 'ارتباط' : 'Contact') }}</a>
                  </div>
                </div>
              </div>

              <ul class="az-about__facts">
                <li><strong data-i18n="about.meta.location.label">Location:</strong> <span data-i18n="about.meta.location.value">Tehran, Iran</span></li>
                <li><strong data-i18n="about.meta.education.label">Education:</strong> <span data-i18n="about.meta.education.value">B.Sc. Computer Science, Amirkabir University of Technology</span></li>
                <li><strong data-i18n="about.meta.status.label">Status:</strong> <span data-i18n="about.meta.status.value">Available for freelance</span></li>
              </ul>

              <section class="az-about-skills" aria-labelledby="about-skills-heading">
                <h2 class="az-about-skills__title" id="about-skills-heading" data-i18n="about.skills">{{ $strings['about.skills'] ?? 'Skills' }}</h2>
                <div class="az-about-skills__grid">
                  <article class="az-about-skills__card">
                    <h3 class="az-about-skills__heading" data-i18n="about.skills.frontend">Front-end</h3>
                    <ul class="az-about-skills__list">
                      <li dir="ltr">HTML</li>
                      <li dir="ltr">CSS</li>
                      <li dir="ltr">JavaScript</li>
                      <li dir="ltr">React</li>
                      <li dir="ltr">Bootstrap</li>
                      <li dir="ltr">Tailwind</li>
                      <li dir="ltr">Sass</li>
                    </ul>
                  </article>
                  <article class="az-about-skills__card">
                    <h3 class="az-about-skills__heading" data-i18n="about.skills.languages">Programming Languages</h3>
                    <ul class="az-about-skills__list">
                      <li dir="ltr">Python</li>
                      <li dir="ltr">Java</li>
                      <li dir="ltr">PHP</li>
                      <li dir="ltr">C</li>
                      <li dir="ltr">C#</li>
                    </ul>
                  </article>
                  <article class="az-about-skills__card">
                    <h3 class="az-about-skills__heading" data-i18n="about.skills.backend">Back-end &amp; Database</h3>
                    <ul class="az-about-skills__list">
                      <li dir="ltr">Node.js</li>
                      <li dir="ltr">Laravel</li>
                      <li dir="ltr">.NET</li>
                      <li dir="ltr">MySQL</li>
                      <li dir="ltr">PostgreSQL</li>
                      <li dir="ltr">MongoDB</li>
                    </ul>
                  </article>
                  <article class="az-about-skills__card">
                    <h3 class="az-about-skills__heading" data-i18n="about.skills.other">Other Skills</h3>
                    <ul class="az-about-skills__list">
                      <li dir="ltr">JavaFX</li>
                      <li dir="ltr">Scrum</li>
                    </ul>
                  </article>
                </div>
              </section>
            </div>
          </section>
