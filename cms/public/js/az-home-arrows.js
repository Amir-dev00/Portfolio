/**
 * Home background helpers:
 * - Desktop arrow edge-tab feedback (Rhye/Swiper still owns prev/next).
 * - Mobile photographic crossfade (desktop WebGL path stays untouched).
 */
(function () {
  var REDUCE = window.matchMedia("(prefers-reduced-motion: reduce)");
  var MOBILE = window.matchMedia("(max-width: 767px)");
  var FIRE_MS = 160;
  var GLINT_MS = 400;
  var MOBILE_INTERVAL_MS = 6000;
  var mobileTimer = 0;

  function bindArrows(root) {
    var scope = root || document;
    scope.querySelectorAll(".az-home-arrows .slider__arrow").forEach(function (el) {
      if (el.dataset.azHomeArrowBound === "1") return;
      el.dataset.azHomeArrowBound = "1";

      el.addEventListener(
        "pointerdown",
        function () {
          if (REDUCE.matches) return;
          el.classList.add("is-firing");
        },
        { passive: true }
      );

      function settle() {
        window.setTimeout(function () {
          el.classList.remove("is-firing");
        }, FIRE_MS);
      }

      el.addEventListener("pointerup", settle, { passive: true });
      el.addEventListener("pointercancel", settle, { passive: true });
      el.addEventListener("pointerleave", settle, { passive: true });

      el.addEventListener(
        "click",
        function () {
          if (REDUCE.matches) return;
          el.classList.remove("is-glinting");
          // Restart glint if the user taps quickly.
          void el.offsetWidth;
          el.classList.add("is-glinting");
          window.setTimeout(function () {
            el.classList.remove("is-glinting");
          }, GLINT_MS);
        },
        { passive: true }
      );
    });
  }

  function stopMobileBg() {
    if (mobileTimer) {
      window.clearInterval(mobileTimer);
      mobileTimer = 0;
    }
  }

  function advanceMobileBg(root) {
    var slides = root.querySelectorAll(".az-home-mobile-bg__slide");
    if (slides.length < 2) return;
    var active = root.querySelector(".az-home-mobile-bg__slide.is-active");
    var index = active ? Array.prototype.indexOf.call(slides, active) : 0;
    var next = slides[(index + 1) % slides.length];
    if (active) active.classList.remove("is-active");
    if (next) next.classList.add("is-active");
  }

  function bindMobileBg(root) {
    var scope = root || document;
    var plane = scope.querySelector("[data-az-home-mobile-bg]");
    if (!plane || plane.dataset.azHomeMobileBound === "1") return;
    plane.dataset.azHomeMobileBound = "1";

    function sync() {
      stopMobileBg();
      if (!MOBILE.matches || REDUCE.matches) return;
      if (document.hidden) return;
      mobileTimer = window.setInterval(function () {
        advanceMobileBg(plane);
      }, MOBILE_INTERVAL_MS);
    }

    sync();
    if (typeof MOBILE.addEventListener === "function") {
      MOBILE.addEventListener("change", sync);
    } else if (typeof MOBILE.addListener === "function") {
      MOBILE.addListener(sync);
    }
    document.addEventListener("visibilitychange", sync);
  }

  function bind(root) {
    bindArrows(root);
    bindMobileBg(root);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      bind(document);
    });
  } else {
    bind(document);
  }

  window.AZ_HOME_ARROWS = { bind: bind };
})();
