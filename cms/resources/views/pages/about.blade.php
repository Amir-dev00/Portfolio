          <!-- ABOUT VIEW -->
          <section class="az-view {{ ($viewName ?? '') === 'about' ? 'is-active is-visible' : '' }}" id="view-about" data-view="about" aria-label="{{ $strings['about.aria'] ?? 'About' }}" data-i18n-attr="aria-label:about.aria" @if(($viewName ?? '') !== 'about') hidden aria-hidden="true" @endif>
            <div class="az-panel">
              <p class="az-panel__eyebrow" data-i18n="about.eyebrow">{{ $strings['about.eyebrow'] ?? 'About' }}</p>
              <h1 class="az-panel__title" data-i18n="about.title">{{ $strings['about.title'] ?? 'Software engineer.' }}</h1>
              <div class="az-about">
                <div>
                  <div class="az-about__portrait">
                    <img src="{{ asset('assets/img/amir/profile.jpg') }}" width="640" height="640" alt="Portrait of Amirhossein Zamanzade" decoding="async" data-i18n-attr="alt:about.portrait.alt"/>
                  </div>
                  <ul class="az-about__meta">
                    <li><strong data-i18n="about.meta.location.label">Location:</strong> <span data-i18n="about.meta.location.value">Tehran, Iran</span></li>
                    <li><strong data-i18n="about.meta.education.label">Education:</strong> <span data-i18n="about.meta.education.value">B.Sc. Computer Science, Amirkabir University of Technology</span></li>
                    <li><strong data-i18n="about.meta.status.label">Status:</strong> <span data-i18n="about.meta.status.value">Available for freelance</span></li>
                  </ul>
                </div>
                <div>
                  <p class="az-panel__lead" style="margin-bottom:16px" data-i18n="about.p1">
                    I’m Amirhossein Zamanzade, a Computer Science student and Full-Stack Developer focused on fast, scalable, and user-centric products. I care about clean architecture, modern tooling, and interfaces that feel refined.
                  </p>
                  <p class="az-panel__lead" style="margin-bottom:0" data-i18n="about.p2">
                    This portfolio reflects my learning journey across web, desktop, APIs, and automation — and a place to connect for collaboration.
                  </p>
                  <ul class="az-skills" aria-label="Skills" data-i18n-attr="aria-label:about.skills">
                    <li>HTML</li><li>CSS</li><li>JavaScript</li><li>React</li><li>Python</li><li>Java</li>
                    <li>Node.js</li><li>PHP</li><li>Laravel</li><li>.NET</li><li>MySQL</li><li>PostgreSQL</li>
                    <li>MongoDB</li><li>Bootstrap</li><li>Tailwind</li><li>JavaFX</li>
                  </ul>
                </div>
              </div>
            </div>
          </section>
