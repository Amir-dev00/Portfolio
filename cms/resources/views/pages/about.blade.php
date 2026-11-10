          <!-- ABOUT VIEW -->
          <section class="az-view {{ ($viewName ?? '') === 'about' ? 'is-active is-visible' : '' }}" id="view-about" data-view="about" aria-label="{{ $strings['about.aria'] ?? 'About' }}" data-i18n-attr="aria-label:about.aria" @if(($viewName ?? '') !== 'about') hidden aria-hidden="true" @endif>
            <div class="az-panel az-panel_about">
              <div class="az-about">
                <aside class="az-about__aside">
                  <div class="az-about__portrait">
                    <img src="{{ asset('assets/img/amir/profile.jpg') }}" width="640" height="640" alt="Portrait of Amirhossein Zamanzade" decoding="async" data-i18n-attr="alt:about.portrait.alt"/>
                  </div>
                  <ul class="az-about__meta">
                    <li><strong data-i18n="about.meta.location.label">Location:</strong> <span data-i18n="about.meta.location.value">Tehran, Iran</span></li>
                    <li><strong data-i18n="about.meta.education.label">Education:</strong> <span data-i18n="about.meta.education.value">B.Sc. Computer Science, Amirkabir University of Technology</span></li>
                    <li><strong data-i18n="about.meta.status.label">Status:</strong> <span data-i18n="about.meta.status.value">Available for freelance</span></li>
                  </ul>
                </aside>

                <div class="az-about__copy">
                  <p class="az-panel__eyebrow" data-i18n="about.eyebrow">{{ $strings['about.eyebrow'] ?? 'About' }}</p>
                  <h1 class="az-panel__title" data-i18n="about.title">{{ $strings['about.title'] ?? 'Software engineer.' }}</h1>
                  <p class="az-panel__lead az-about__lead" data-i18n="about.p1">
                    I’m Amirhossein Zamanzade, a Computer Science student and Full-Stack Developer focused on fast, scalable, and user-centric products. I care about clean architecture, modern tooling, and interfaces that feel refined.
                  </p>
                  <p class="az-panel__lead az-about__lead az-about__lead_last" data-i18n="about.p2">
                    This portfolio reflects my learning journey across web, desktop, APIs, and automation — and a place to connect for collaboration.
                  </p>
                </div>
              </div>

              <section class="az-about-skills" aria-labelledby="about-skills-heading">
                <h2 class="az-about-skills__title" id="about-skills-heading" data-i18n="about.skills">{{ $strings['about.skills'] ?? 'Skills' }}</h2>
                <div class="az-about-skills__grid">
                  <article class="az-about-skills__card">
                    <h3 class="az-about-skills__heading" data-i18n="about.skills.frontend">Front-end</h3>
                    <ul class="az-about-skills__list">
                      <li>HTML</li>
                      <li>CSS</li>
                      <li>JavaScript</li>
                      <li>React</li>
                      <li>Bootstrap</li>
                      <li>Tailwind</li>
                      <li>Sass</li>
                    </ul>
                  </article>
                  <article class="az-about-skills__card">
                    <h3 class="az-about-skills__heading" data-i18n="about.skills.languages">Programming Languages</h3>
                    <ul class="az-about-skills__list">
                      <li>Python</li>
                      <li>Java</li>
                      <li>PHP</li>
                      <li>C</li>
                      <li>C#</li>
                    </ul>
                  </article>
                  <article class="az-about-skills__card">
                    <h3 class="az-about-skills__heading" data-i18n="about.skills.backend">Back-end &amp; Database</h3>
                    <ul class="az-about-skills__list">
                      <li>Node.js</li>
                      <li>Laravel</li>
                      <li>.NET</li>
                      <li>MySQL</li>
                      <li>PostgreSQL</li>
                      <li>MongoDB</li>
                    </ul>
                  </article>
                  <article class="az-about-skills__card">
                    <h3 class="az-about-skills__heading" data-i18n="about.skills.other">Other Skills</h3>
                    <ul class="az-about-skills__list">
                      <li>JavaFX</li>
                      <li>Scrum</li>
                    </ul>
                  </article>
                </div>
              </section>
            </div>
          </section>
