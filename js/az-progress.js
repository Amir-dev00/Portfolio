/* Segmented homepage progress — synced to Rhye/Swiper autoplay. */
(function () {
  "use strict";

  var root = document.getElementById("az-progress");
  var sliderRoot = document.querySelector(".js-slider-fullscreen-projects");
  var imagesEl = document.querySelector(".js-slider-fullscreen-projects__images");
  if (!root || !imagesEl) return;

  var REDUCE = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var delayMs = parseInt(imagesEl.getAttribute("data-autoplay-delay") || "6000", 10) || 6000;
  var bound = false;
  var swiper = null;
  var slideCount = 0;

  root.style.setProperty("--az-progress-ms", delayMs + "ms");

  function getSwiper() {
    return imagesEl.swiper || null;
  }

  function countSlides() {
    var slides = imagesEl.querySelectorAll(".swiper-slide:not(.swiper-slide-duplicate)");
    return slides.length || imagesEl.querySelectorAll(".swiper-slide").length || 0;
  }

  function build() {
    slideCount = countSlides();
    if (!slideCount) return;
    var html = "";
    for (var i = 0; i < slideCount; i += 1) {
      html +=
        '<button type="button" class="az-progress__seg" role="tab" data-index="' +
        i +
        '" aria-label="Go to slide ' +
        (i + 1) +
        ' of ' +
        slideCount +
        '" aria-selected="false">' +
        '<span class="az-progress__track" aria-hidden="true"><span class="az-progress__fill"></span></span>' +
        "</button>";
    }
    root.innerHTML = html;
    root.hidden = false;
  }

  function restartFill(el) {
    if (!el) return;
    var fill = el.querySelector(".az-progress__fill");
    if (!fill) return;
    fill.style.animation = "none";
    // Force reflow so the animation restarts cleanly.
    void fill.offsetWidth;
    fill.style.animation = "";
  }

  function setActive(index, paused) {
    var segs = root.querySelectorAll(".az-progress__seg");
    segs.forEach(function (seg, i) {
      var active = i === index;
      seg.classList.toggle("is-active", active);
      seg.classList.toggle("is-done", i < index);
      seg.classList.toggle("is-paused", active && !!paused);
      seg.setAttribute("aria-selected", active ? "true" : "false");
      if (active && !REDUCE) restartFill(seg);
      if (!active) {
        var fill = seg.querySelector(".az-progress__fill");
        if (fill) fill.style.animation = "";
      }
    });
  }

  function isAutoplayRunning() {
    if (!swiper || !swiper.autoplay) return false;
    if (typeof swiper.autoplay.running === "boolean") return swiper.autoplay.running;
    return !swiper.autoplay.paused;
  }

  function syncFromSwiper() {
    if (!swiper) return;
    var index = typeof swiper.realIndex === "number" ? swiper.realIndex : swiper.activeIndex || 0;
    setActive(index, !isAutoplayRunning());
  }

  function setPaused(paused) {
    var active = root.querySelector(".az-progress__seg.is-active");
    if (!active) return;
    active.classList.toggle("is-paused", !!paused);
  }

  function onSegClick(event) {
    var btn = event.target.closest(".az-progress__seg");
    if (!btn || !swiper) return;
    var index = parseInt(btn.getAttribute("data-index"), 10);
    if (!Number.isFinite(index)) return;
    if (typeof swiper.slideToLoop === "function" && swiper.params && swiper.params.loop) {
      swiper.slideToLoop(index);
    } else if (typeof swiper.slideTo === "function") {
      swiper.slideTo(index);
    }
  }

  function bindSwiper(instance) {
    if (!instance || bound) return;
    swiper = instance;
    bound = true;
    if (!root.children.length) build();
    syncFromSwiper();

    swiper.on("slideChange", function () {
      syncFromSwiper();
    });
    swiper.on("slideChangeTransitionStart", function () {
      syncFromSwiper();
    });
    swiper.on("autoplayStart", function () {
      setPaused(false);
      // Fresh cycle when autoplay resumes after stop.
      var active = root.querySelector(".az-progress__seg.is-active");
      if (active && !REDUCE) restartFill(active);
    });
    swiper.on("autoplayStop", function () {
      setPaused(true);
    });

    root.addEventListener("click", onSegClick);
  }

  function tryBind() {
    var instance = getSwiper();
    if (instance) {
      bindSwiper(instance);
      return true;
    }
    return false;
  }

  // Rhye initializes after components.js; poll briefly until Swiper exists.
  var tries = 0;
  var poll = window.setInterval(function () {
    tries += 1;
    if (tryBind() || tries > 80) window.clearInterval(poll);
  }, 100);

  window.addEventListener("arts/preloader/end", function () {
    window.setTimeout(tryBind, 200);
  });

  // Pause/resume with SPA route changes without rebinding.
  var routeWatch = new MutationObserver(function () {
    if (!bound) return;
    var home = document.documentElement.dataset.azRoute === "home" || !document.documentElement.dataset.azRoute;
    if (!home) {
      setPaused(true);
    } else {
      syncFromSwiper();
    }
  });
  routeWatch.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["data-az-route"],
  });

  // Build immediately from DOM slide count so layout is ready before Swiper.
  build();
})();
