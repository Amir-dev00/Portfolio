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
      window.clearTimeout(mobileTimer);
      mobileTimer = 0;
    }
  }

  var plane;
  var imageLoads = new Map();
  var requestId = 0;

  function homeIsVisible() {
    var home = plane && plane.closest(".az-view");
    return !!(home && !home.hidden && !document.hidden &&
      !document.documentElement.classList.contains("az-loading"));
  }

  function usesPhotos() {
    return MOBILE.matches || !document.documentElement.classList.contains("az-rhye-ready");
  }

  function loadPhoto(url) {
    if (imageLoads.has(url)) return imageLoads.get(url);
    var pending = new Promise(function (resolve, reject) {
      var image = new Image();
      image.onload = function () {
        if (image.decode) image.decode().then(resolve, reject);
        else resolve();
      };
      image.onerror = reject;
      image.fetchPriority = "low";
      image.src = url;
    }).catch(function (error) {
      imageLoads.delete(url);
      throw error;
    });
    imageLoads.set(url, pending);
    return pending;
  }

  function advancePhoto(direction) {
    if (!homeIsVisible() || !usesPhotos()) return;
    var slides = Array.from(plane.querySelectorAll(".az-home-mobile-bg__slide"));
    var active = plane.querySelector(".az-home-mobile-bg__slide.is-active");
    var next = slides[(slides.indexOf(active) + direction + slides.length) % slides.length];
    if (!next) return;
    stopMobileBg();
    var token = ++requestId;
    var url = next.dataset[MOBILE.matches ? "mobile" : "desktop"];
    var loaded = url ? loadPhoto(url) : Promise.resolve();
    loaded.then(function () {
      if (token !== requestId || !homeIsVisible() || !usesPhotos()) return;
      if (url) next.style.backgroundImage = 'url("' + url + '")';
      if (active) active.classList.remove("is-active");
      next.classList.add("is-active");
    }).catch(function () {
      // Keep the current image if the next image fails to load.
    }).finally(function () {
      if (token === requestId) sync();
    });
  }

  function sync() {
    stopMobileBg();
    if (!plane || !homeIsVisible() || !usesPhotos() || REDUCE.matches) return;
    var connection = navigator.connection;
    if (!MOBILE.matches && connection &&
      (connection.saveData || /^(slow-2g|2g|3g)$/.test(connection.effectiveType))) return;
    // Wait for each image to finish before counting down to the next slide.
    mobileTimer = window.setTimeout(function () { advancePhoto(1); }, MOBILE_INTERVAL_MS);
  }

  function bind(root) {
    var scope = root || document;
    bindArrows(scope);
    var nextPlane = scope.querySelector("[data-az-home-mobile-bg]");
    if (!nextPlane || nextPlane === plane) return;
    plane = nextPlane;
    var home = plane.closest(".az-view");
    if (home && document.documentElement.classList.contains("az-laravel")) {
      home.querySelectorAll(".az-home-arrows .slider__arrow").forEach(function (arrow) {
        arrow.addEventListener("click", function () {
          advancePhoto(arrow.classList.contains("js-slider__arrow-prev") ? -1 : 1);
        });
      });
    }
    sync();
  }

  MOBILE.addEventListener("change", function () {
    requestId += 1;
    if (plane) {
      plane.querySelectorAll(".az-home-mobile-bg__slide").forEach(function (slide, index) {
        slide.classList.toggle("is-active", index === 0);
      });
    }
    sync();
  });
  REDUCE.addEventListener("change", sync);
  if (navigator.connection && navigator.connection.addEventListener) navigator.connection.addEventListener("change", sync);
  document.addEventListener("visibilitychange", sync);
  window.addEventListener("az:preloader-end", sync);

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      bind(document);
    });
  } else {
    bind(document);
  }

  window.AZ_HOME_ARROWS = { bind: bind, sync: sync };
})();
