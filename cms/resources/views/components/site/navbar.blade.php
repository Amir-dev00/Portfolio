      <nav class="glass-nav" id="glass-nav" aria-label="Primary" data-i18n-attr="aria-label:nav.primary">
        <div class="glass-nav__bar" role="list">
          <span class="glass-nav__sheen" aria-hidden="true"></span>
          <span class="glass-nav__indicator" aria-hidden="true"></span>

          @php
            $navHome = ($activeView ?? 'home') === 'home';
            $navWork = in_array($activeView ?? '', ['work', 'project'], true);
            $navAbout = ($activeView ?? '') === 'about';
            $navAsk = ($activeView ?? '') === 'ask';
            $navContact = ($activeView ?? '') === 'contact';
          @endphp
          <a class="glass-nav__tab {{ $navWork ? 'is-active' : '' }}" role="listitem" href="{{ $navPaths['work'] ?? '/work' }}" data-nav="work" data-tooltip="Work" data-i18n-attr="data-tooltip:nav.tooltip.work" @if($navWork) aria-current="page" @endif>
            <span class="glass-nav__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12h.01"/><path d="M16 6V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><path d="M22 13a18.15 18.15 0 0 1-20 0"/><rect width="20" height="14" x="2" y="6" rx="2"/></svg>
            </span>
            <span class="glass-nav__name" dir="auto" data-i18n="nav.work">{{ $strings['nav.work'] ?? 'Work' }}</span>
          </a>

          <a class="glass-nav__tab {{ $navAbout ? 'is-active' : '' }}" role="listitem" href="{{ $navPaths['about'] ?? '/about' }}" data-nav="about" data-tooltip="About" data-i18n-attr="data-tooltip:nav.tooltip.about" @if($navAbout) aria-current="page" @endif>
            <span class="glass-nav__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
            </span>
            <span class="glass-nav__name" dir="auto" data-i18n="nav.about">{{ $strings['nav.about'] ?? 'About' }}</span>
          </a>

          <a class="glass-nav__home {{ $navHome ? 'is-active' : '' }}" role="listitem" href="{{ $navPaths['home'] ?? '/' }}" aria-label="{{ $strings['nav.home'] ?? 'Home' }}" @if($navHome) aria-current="page" @endif data-nav="home" data-tooltip="Home" data-i18n-attr="aria-label:nav.home;data-tooltip:nav.tooltip.home">
            <span class="glass-nav__home-glass" aria-hidden="true"></span>
            <span class="glass-nav__home-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            </span>
          </a>

          <a class="glass-nav__action glass-nav__cv" role="listitem" href="{{ $cvHref }}" download="Amirhossein-Zamanzade-CV.pdf" aria-label="Download CV" data-tooltip="Download CV" data-nav="cv" data-i18n-attr="aria-label:nav.aria.cv;data-tooltip:nav.tooltip.cv">
            <span class="glass-nav__action-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>
            </span>
            <span class="glass-nav__name" dir="auto" data-i18n="nav.cv">{{ $strings['nav.cv'] ?? 'CV' }}</span>
          </a>

          <a class="glass-nav__tab {{ $navAsk ? 'is-active' : '' }}" role="listitem" href="{{ $navPaths['ask'] ?? '/ask' }}" data-nav="ask" data-tooltip="Ask" data-i18n-attr="data-tooltip:nav.ask" @if($navAsk) aria-current="page" @endif>
            <span class="glass-nav__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5H4l1.4-4.2A8.5 8.5 0 1 1 21 11.5Z"/><path d="M9 12h.01m3.5 0h.01m3.5 0h.01"/></svg>
            </span>
            <span class="glass-nav__name" dir="auto" data-i18n="nav.ask">{{ $strings['nav.ask'] ?? ($locale === 'fa' ? 'گفتگو' : 'Ask') }}</span>
          </a>

          <a class="glass-nav__action glass-nav__contact {{ $navContact ? 'is-active' : '' }}" role="listitem" href="{{ $navPaths['contact'] ?? '/contact' }}" aria-label="Contact" data-tooltip="Contact" data-nav="contact" data-i18n-attr="aria-label:nav.contact;data-tooltip:nav.tooltip.contact" @if($navContact) aria-current="page" @endif>
            <span class="glass-nav__action-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
            </span>
            <span class="glass-nav__name" dir="auto" data-i18n="nav.contact">{{ $strings['nav.contact'] ?? 'Contact' }}</span>
          </a>
        </div>
      </nav>
