/**
 * Mobile home slider edge-tab feedback.
 * Does not drive the slider — Rhye/Swiper keep owning prev/next clicks.
 */
(function () {
  var REDUCE = window.matchMedia("(prefers-reduced-motion: reduce)");
  var FIRE_MS = 160;
  var GLINT_MS = 400;

  function bind(root) {
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

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      bind(document);
    });
  } else {
    bind(document);
  }

  window.AZ_HOME_ARROWS = { bind: bind };
})();
