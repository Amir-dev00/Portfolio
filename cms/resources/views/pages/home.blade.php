          <!-- HOME VIEW -->
          <section class="az-view {{ ($viewName ?? 'home') === 'home' ? 'is-active is-visible' : '' }}" id="view-home" data-view="home" aria-label="{{ $strings['nav.home'] ?? 'Home' }}" data-i18n-attr="aria-label:nav.home" @if(($viewName ?? 'home') !== 'home') hidden aria-hidden="true" @endif>
          <!-- section PROJECTS SLIDER FULLSCREEN -->
          <section id="selected-work" class="section section-fullheight section-projects section-projects-slider bg-dark-1 overflow" data-arts-theme-text="light" data-arts-os-animation>
            <div class="section-fullheight__inner section-fullheight__inner_mobile text-center">
              @include('components.site.profile')
              @include('components.site.assistant')
              <div class="slider slider-fullscreen-projects js-slider-fullscreen-projects js-slider">
                <!-- Empty content slides keep Rhye Swiper controllers in sync; no titles/captions. -->
                <div class="slider-fullscreen-projects__content swiper-container pointer-events-none js-slider-fullscreen-projects__content az-home-slider-text" aria-hidden="true">
                  <div class="swiper-wrapper">
                    <div class="swiper-slide"></div>
                    <div class="swiper-slide"></div>
                    <div class="swiper-slide"></div>
                    <div class="swiper-slide"></div>
                  </div>
                </div>
                <!-- slider IMAGES: four authored home backgrounds (WebP preferred). First loads promptly; others defer. -->
                <div class="slider-fullscreen-projects__images swiper-container js-slider-fullscreen-projects__images" data-mousewheel-enabled="data-mousewheel-enabled" data-direction="horizontal" data-transition-effect="distortion" data-aspect-ratio="1.5" data-transition-displacement-img="assets/img/general/bg-displacement-7.jpg" data-speed="1200" data-autoplay-enabled="true" data-autoplay-delay="6000" data-counter-style="roman">
                  <div class="swiper-wrapper">
                    <div class="swiper-slide overflow">
                      <div class="slider__images-slide-inner js-transition-img overflow" data-swiper-parallax="90%">
                        <div class="slider__bg lazy-bg js-transition-img__transformed-el az-home-bg az-home-bg--01" data-texture-src="{{ asset('assets/img/amir/home/home-bg-01.webp') }}" style="--az-home-bg-pos: 50% 42%;"></div>
                      </div>
                    </div>
                    <div class="swiper-slide overflow">
                      <div class="slider__images-slide-inner js-transition-img overflow" data-swiper-parallax="90%">
                        <div class="slider__bg lazy-bg js-transition-img__transformed-el az-home-bg az-home-bg--02" data-texture-src="{{ asset('assets/img/amir/home/home-bg-02.webp') }}" data-az-defer-texture="1" style="--az-home-bg-pos: 50% 40%;"></div>
                      </div>
                    </div>
                    <div class="swiper-slide overflow">
                      <div class="slider__images-slide-inner js-transition-img overflow" data-swiper-parallax="90%">
                        <div class="slider__bg lazy-bg js-transition-img__transformed-el az-home-bg az-home-bg--03" data-texture-src="{{ asset('assets/img/amir/home/home-bg-03.webp') }}" data-az-defer-texture="1" style="--az-home-bg-pos: 50% 38%;"></div>
                      </div>
                    </div>
                    <div class="swiper-slide overflow">
                      <div class="slider__images-slide-inner js-transition-img overflow" data-swiper-parallax="90%">
                        <div class="slider__bg lazy-bg js-transition-img__transformed-el az-home-bg az-home-bg--04" data-texture-src="{{ asset('assets/img/amir/home/home-bg-04.webp') }}" data-az-defer-texture="1" style="--az-home-bg-pos: 50% 44%;"></div>
                      </div>
                    </div>
                  </div>
                  <!-- overlay -->
                  <div class="slider__overlay overlay overlay_circle-dark overlay_deither"></div>
                  <div class="slider__circle"></div>
                  <!-- - overlay -->
                </div>
                <!-- - slider IMAGES -->
                <!-- Empty footer slides for Rhye sync -->
                <div class="slider-fullscreen-projects__footer swiper-container js-slider-fullscreen-projects__footer az-home-slider-text" aria-hidden="true">
                  <div class="swiper-wrapper">
                    <div class="swiper-slide"></div>
                    <div class="swiper-slide"></div>
                    <div class="swiper-slide"></div>
                    <div class="swiper-slide"></div>
                  </div>
                </div>
                <!-- Counter kept for Rhye sync; fully hidden (no numbers on screen). -->
                <div class="slider__wrapper-counter slider-fullscreen-projects__counter az-home-slider-text" aria-hidden="true">
                  <div class="slider__counter slider__counter_current">
                    <div class="js-slider-fullscreen-projects__counter-current swiper-container">
                      <div class="swiper-wrapper"></div>
                    </div>
                  </div>
                  <div class="slider__counter-divider slider-fullscreen__counter-divider"></div>
                  <div class="slider__counter slider__counter_total js-slider-fullscreen-projects__counter-total"></div>
                </div>
                <!-- slider ARROWS -->
                <div class="az-home-arrows" role="group" aria-label="Background slides" data-i18n-attr="aria-label:home.arrows">
                  <div class="slider__arrow slider__arrow_left slider__arrow_absolute js-slider__arrow-prev" aria-label="Previous slide" data-i18n-attr="aria-label:home.prev">
                    <div class="arrow arrow-left js-arrow" data-arts-cursor="data-arts-cursor" data-arts-cursor-hide-native="true" data-arts-cursor-scale="0" data-arts-cursor-magnetic="data-arts-cursor-magnetic">
                      <svg class="svg-circle" viewBox="0 0 60 60" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true">
                        <circle class="circle" cx="30" cy="30" r="29" fill="none"></circle>
                      </svg>
                      <div class="arrow__pointer arrow-left__pointer" aria-hidden="true"></div>
                      <div class="arrow__triangle" aria-hidden="true"></div>
                      <span class="az-home-arrows__tab" aria-hidden="true">
                        <span class="az-home-arrows__glint"></span>
                        <svg class="az-home-arrows__chevron" viewBox="0 0 16 16" width="14" height="14" fill="none" focusable="false">
                          <path d="M10.25 3.5 5.75 8l4.5 4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </span>
                    </div>
                  </div>
                  <div class="slider__arrow slider__arrow_right slider__arrow_absolute js-slider__arrow-next" aria-label="Next slide" data-i18n-attr="aria-label:home.next">
                    <div class="arrow arrow-right js-arrow" data-arts-cursor="data-arts-cursor" data-arts-cursor-hide-native="true" data-arts-cursor-scale="0" data-arts-cursor-magnetic="data-arts-cursor-magnetic">
                      <svg class="svg-circle" viewBox="0 0 60 60" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true">
                        <circle class="circle" cx="30" cy="30" r="29" fill="none"></circle>
                      </svg>
                      <div class="arrow__pointer arrow-right__pointer" aria-hidden="true"></div>
                      <div class="arrow__triangle" aria-hidden="true"></div>
                      <span class="az-home-arrows__tab" aria-hidden="true">
                        <span class="az-home-arrows__glint"></span>
                        <svg class="az-home-arrows__chevron" viewBox="0 0 16 16" width="14" height="14" fill="none" focusable="false">
                          <path d="M5.75 3.5 10.25 8l-4.5 4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </span>
                    </div>
                  </div>
                </div>
                <!-- - slider ARROWS -->
                <!-- slider CANVAS -->
                <div class="slider__wrapper-canvas">
                  <div class="slider__wrapper-canvas-inner">
                    <canvas class="slider__canvas"></canvas>
                  </div>
                </div>
                <!-- - slider CANVAS -->
              </div>
            </div>
          </section>
          <!-- - section PROJECTS SLIDER FULLSCREEN -->
          </section>
          <!-- - HOME VIEW -->
