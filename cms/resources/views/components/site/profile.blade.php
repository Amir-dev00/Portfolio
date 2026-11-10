              <aside class="az-profile" id="az-profile" aria-label="Amirhossein Zamanzade, Software Engineer" data-i18n-attr="aria-label:home.profile.aria">
                <div class="az-profile__orb">
                  <span class="az-profile__glow" aria-hidden="true"></span>
                  <span class="az-profile__ring" aria-hidden="true"></span>
                  <div class="az-profile__glass">
                    <img
                      class="az-profile__photo"
                      src="{{ $profilePhoto }}"
                      width="640"
                      height="640"
                      alt="Portrait of Amirhossein Zamanzade"
                      decoding="async"
                      fetchpriority="low"
                      data-i18n-attr="alt:about.portrait.alt"
                    />
                    <span class="az-profile__shine" aria-hidden="true"></span>
                  </div>
                </div>
                <div class="az-profile__meta">
                  <p class="az-profile__name" data-i18n="home.profile.name">{{ $strings['home.profile.name'] ?? 'Amirhossein Zamanzade' }}</p>
                  <p class="az-profile__role" data-i18n="home.profile.role">{{ $strings['home.profile.role'] ?? 'Software Engineer' }}</p>
                </div>
              </aside>
