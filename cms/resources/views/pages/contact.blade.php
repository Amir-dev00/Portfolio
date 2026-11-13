          <!-- CONTACT VIEW -->
          <section class="az-view {{ ($viewName ?? '') === 'contact' ? 'is-active is-visible' : '' }}" id="view-contact" data-view="contact" aria-label="{{ $strings['nav.contact'] ?? $strings['contact.aria'] ?? ((($locale ?? 'en') === 'fa') ? 'ارتباط' : 'Contact') }}" data-i18n-attr="aria-label:nav.contact" @if(($viewName ?? '') !== 'contact') hidden aria-hidden="true" @endif>
            <div class="az-panel az-panel_contact">
              <p class="az-contact-eyebrow" data-i18n="contact.eyebrow">{{ $strings['contact.eyebrow'] ?? 'Let’s connect' }}</p>
              <h1 class="az-panel__title" data-i18n="contact.title">{{ $strings['contact.title'] ?? ((($locale ?? 'en') === 'fa') ? 'بیایید همکاری کنیم' : 'Let’s work together') }}</h1>
              <p class="az-panel__lead az-contact-intro" data-i18n="contact.intro">{{ $strings['contact.intro'] ?? ((($locale ?? 'en') === 'fa') ? 'پروژه یا فرصت همکاری در نظر دارید؟ یک تماس رزرو کنید یا ایمیل بزنید.' : 'Have a project or role in mind? Book a call or send me an email.') }}</p>

              <div class="az-contact-actions">
                @if ($booking['enabled'] ?? true)
                  <button type="button" class="az-contact-pill az-contact-pill_primary az-contact-pill_book"
                    data-az-booking-open
                    data-booking-api="{{ $booking['api'] ?? url('/api/booking') }}"
                    data-booking-duration="{{ $booking['duration'] ?? 30 }}"
                    data-booking-timezone="{{ $booking['timezone'] ?? 'Asia/Tehran' }}"
                    data-booking-email="{{ $social['email'] }}"
                    data-booking-name="{{ $profile['name'] ?? 'Amirhossein Zamanzade' }}"
                    data-booking-name-fa="{{ $profile['name_fa'] ?? 'امیرحسین زمان زاده' }}"
                    data-booking-photo="{{ $profilePhoto ?? asset('assets/img/amir/profile.webp') }}"
                    @if ($bookingAdmin ?? false) data-booking-admin="1" @endif
                    aria-haspopup="dialog" aria-expanded="false">
                    <svg class="az-contact-pill__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                    <span data-i18n="contact.bookCall">{{ $strings['contact.bookCall'] ?? ((($locale ?? 'en') === 'fa') ? 'رزرو تماس' : 'Book a call') }}</span>
                    <span class="az-contact-pill__arrow" aria-hidden="true">↗</span>
                  </button>
                @else
                  <a class="az-contact-pill az-contact-pill_primary" href="mailto:{{ $social['email'] }}">
                    <span data-i18n="contact.emailMe">{{ $strings['contact.emailMe'] ?? 'Email me' }}</span>
                    <span class="az-contact-pill__arrow" aria-hidden="true">↗</span>
                  </a>
                @endif
                <a class="az-contact-pill" href="{{ $cvHref }}" download="Amirhossein-Zamanzade-CV.pdf" data-i18n="contact.downloadCv">
                  {{ $strings['contact.downloadCv'] ?? 'Download CV' }}
                </a>
              </div>

              <div class="az-contact-links">
                <a class="az-contact-link az-contact-link_email" href="mailto:{{ $social['email'] }}">
                  <span class="az-contact-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                  </span>
                  <span class="az-contact-link__copy">
                    <strong class="az-ltr" dir="ltr">{{ $social['email'] }}</strong>
                    <small data-i18n="contact.emailMe">Email me</small>
                  </span>
                  <span class="az-contact-link__arrow" aria-hidden="true">↗</span>
                </a>
                <a class="az-contact-link" href="{{ $social['linkedin'] }}" target="_blank" rel="noopener">
                  <span class="az-contact-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 11v5M8 8v.01M12 16v-5c0-1.5 1.2-2 2.5-2s2.5.5 2.5 2v5"/></svg>
                  </span>
                  <span class="az-contact-link__copy">
                    <strong data-i18n="contact.linkedin">LinkedIn</strong>
                    <small data-i18n="contact.linkedin.sub">{{ $strings['contact.linkedin.sub'] ?? 'Professional profile' }}</small>
                  </span>
                  <span class="az-contact-link__arrow" aria-hidden="true">↗</span>
                </a>
                <a class="az-contact-link" href="{{ $social['github'] }}" target="_blank" rel="noopener">
                  <span class="az-contact-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-4.3 1.4-4.3-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.2 4.2 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12.3 12.3 0 0 0-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.2 4.2 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/></svg>
                  </span>
                  <span class="az-contact-link__copy">
                    <strong data-i18n="contact.github">GitHub</strong>
                    <small data-i18n="contact.github.sub">{{ $strings['contact.github.sub'] ?? 'Projects and code' }}</small>
                  </span>
                  <span class="az-contact-link__arrow" aria-hidden="true">↗</span>
                </a>
                <a class="az-contact-link" href="{{ $social['telegram'] }}" target="_blank" rel="noopener">
                  <span class="az-contact-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 4.5 2.8 11.8c-.7.3-.7 1.3.1 1.5l4.7 1.5 1.8 5.3c.2.7 1.1.9 1.6.3l2.7-2.9 4.8 3.5c.6.4 1.4.1 1.6-.6L22 5.4c.2-.8-.6-1.4-1.3-1.1Z"/><path d="m7.6 14.8 9.5-6.3"/></svg>
                  </span>
                  <span class="az-contact-link__copy">
                    <strong data-i18n="contact.telegram">Telegram</strong>
                    <small data-i18n="contact.telegram.sub">{{ $strings['contact.telegram.sub'] ?? 'Send a message' }}</small>
                  </span>
                  <span class="az-contact-link__arrow" aria-hidden="true">↗</span>
                </a>
              </div>

              <p class="az-contact-note">
                <span class="az-contact-note__dot" aria-hidden="true"></span>
                <span data-i18n="contact.note">Available for freelance collaborations.</span>
              </p>
            </div>
          </section>
