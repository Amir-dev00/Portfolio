/* AZ circular preloader — owns progress independently of Rhye / vendor.js.
   One value drives the visible number, SVG arc, and progress dot. */
(function () {
  "use strict";

  var loader = document.getElementById("js-preloader");
  if (!loader || !loader.classList.contains("az-preloader--ring")) return;

  var REDUCE = window.matchMedia("(prefers-reduced-motion: reduce)");
  var FINISH_MS = 520;
  var STALL_MS = 1400;
  var SAFETY_MS = 15000;
  var EASE = 0.14;

  var visible = loader.querySelector("[data-az-preloader-count]");
  var ring = loader.querySelector(".az-preloader__ring-fill");
  var head = loader.querySelector(".az-preloader__ring-head");
  var image = loader.querySelector(".az-preloader__image");
  var fail = loader.querySelector("[data-az-preloader-fail]");
  var retry = loader.querySelector("[data-az-preloader-retry]");
  if (!visible || !ring) return;

  var startedAt = performance.now();
  var display = 0;
  var earned = 0;
  var raf = 0;
  var stallTimer = 0;
  var safetyTimer = 0;
  var finishing = false;
  var closed = false;
  var failed = false;
  var usable = false;
  var criticalFailed = false;
  var lastEarnAt = startedAt;
  var timeline = [];
  var finishReason = "";

  function note(event, extra) {
    timeline.push({
      ms: Math.round(performance.now() - startedAt),
      event: event,
      display: Math.round(display),
      earned: earned,
      extra: extra || null,
    });
  }

  document.documentElement.classList.add("az-preloader-open", "az-loading");
  loader.hidden = false;
  loader.style.removeProperty("display");
  loader.style.setProperty("opacity", "1");
  loader.style.setProperty("visibility", "visible");
  loader.classList.remove("az-preloader--fail", "az-preloader--stalling", "az-preloader--complete");
  if (fail) fail.hidden = true;

  function clamp(n, lo, hi) {
    return Math.max(lo, Math.min(hi, n));
  }

  function paint(value) {
    var shown = Math.round(value);
    visible.textContent = String(shown);
    ring.style.strokeDashoffset = String(100 - value);
    loader.style.setProperty("--az-progress", String(value));
    var complete = value >= 99.5;
    loader.classList.toggle("az-preloader--complete", complete);
    if (head) {
      head.classList.toggle("is-active", value > 0.4 && !complete && !failed);
      head.classList.toggle("is-complete", complete);
    }
  }

  function clearStallWatch() {
    if (stallTimer) {
      window.clearTimeout(stallTimer);
      stallTimer = 0;
    }
    loader.classList.remove("az-preloader--stalling");
  }

  function armStallWatch() {
    clearStallWatch();
    if (finishing || failed || closed || earned >= 99) return;
    stallTimer = window.setTimeout(function () {
      if (finishing || failed || closed || earned >= 99) return;
      loader.classList.add("az-preloader--stalling");
    }, STALL_MS);
  }

  function earn(next) {
    if (failed || finishing) return;
    var value = clamp(next, 0, 99);
    if (value <= earned) return;
    earned = value;
    lastEarnAt = performance.now();
    note("earn");
    clearStallWatch();
    armStallWatch();
    kickTween();
  }

  function kickTween() {
    if (raf) return;
    raf = window.requestAnimationFrame(step);
  }

  function step() {
    raf = 0;
    var target = finishing ? 100 : earned;
    var delta = target - display;
    if (REDUCE.matches || Math.abs(delta) < 0.08) {
      display = target;
    } else {
      display += delta * EASE;
    }
    paint(display);
    if (finishing && display >= 99.7) {
      display = 100;
      paint(100);
      closeSoon();
      return;
    }
    if (Math.abs((finishing ? 100 : earned) - display) > 0.08) {
      raf = window.requestAnimationFrame(step);
    } else if (!finishing) {
      armStallWatch();
    }
  }

  function markUsable() {
    usable = true;
  }

  function pageLooksUsable() {
    if (usable) return true;
    if (document.documentElement.classList.contains("az-app-ready")) return true;
    var app = document.getElementById("az-app");
    if (app && app.querySelector(".az-view.is-active, .az-view.is-visible")) return true;
    return false;
  }

  function finishClean(reason) {
    if (finishing || closed || failed) return;
    // A hard-failed critical script must not dismiss into a broken shell.
    if (criticalFailed) {
      showFail();
      return;
    }
    finishing = true;
    finishReason = reason || "usable";
    markUsable();
    clearStallWatch();
    earned = 100;
    note("finish", finishReason);
    loader.classList.remove("az-preloader--stalling");
    kickTween();
    if (REDUCE.matches) {
      display = 100;
      paint(100);
      closeSoon();
    }
  }

  function closeSoon() {
    if (closed) return;
    closed = true;
    note("close");
    clearStallWatch();
    if (safetyTimer) {
      window.clearTimeout(safetyTimer);
      safetyTimer = 0;
    }
    // Cached reloads: no long hold. Slow startups already spent time on screen.
    var elapsed = performance.now() - startedAt;
    var wait = elapsed < 320 ? Math.max(0, 280 - elapsed) : 0;
    window.setTimeout(function () {
      document.documentElement.classList.remove("az-preloader-open");
      window.requestAnimationFrame(function () {
        var inline = (loader.getAttribute("style") || "").replace(/\s/g, "").toLowerCase();
        if (inline.indexOf("display:none") === -1) {
          loader.style.setProperty("display", "none");
        }
      });
      window.setTimeout(function () {
        document.documentElement.classList.remove("az-loading");
      }, FINISH_MS);
    }, wait);
  }

  function showFail() {
    if (closed || finishing) return;
    failed = true;
    clearStallWatch();
    if (raf) {
      window.cancelAnimationFrame(raf);
      raf = 0;
    }
    loader.classList.add("az-preloader--fail");
    loader.classList.remove("az-preloader--stalling", "az-preloader--complete");
    if (head) {
      head.classList.remove("is-active");
      head.classList.add("is-complete");
    }
    if (fail) fail.hidden = false;
  }

  function onSafety() {
    if (closed || finishing || failed) return;
    if (pageLooksUsable()) {
      finishClean("safety-usable");
      return;
    }
    // Timeout alone must never fake 100. Only fail when a critical asset errored
    // or the wait has clearly exhausted without a usable route.
    if (criticalFailed) {
      showFail();
      return;
    }
    var waited = performance.now() - startedAt;
    if (waited >= 45000) {
      showFail();
      return;
    }
    armStallWatch();
    safetyTimer = window.setTimeout(onSafety, 8000);
  }

  function watchScript(part, amount, critical) {
    var bound = false;
    function bind(node) {
      if (!node || node.getAttribute("data-az-preload-watch") === "1") return false;
      node.setAttribute("data-az-preload-watch", "1");
      bound = true;
      if (node.getAttribute("data-az-preload-done") === "1") {
        earn(amount);
        return true;
      }
      node.addEventListener(
        "load",
        function () {
          node.setAttribute("data-az-preload-done", "1");
          earn(amount);
        },
        { once: true }
      );
      node.addEventListener(
        "error",
        function () {
          if (critical) {
            criticalFailed = true;
            note("critical-error", part);
            armStallWatch();
            // Don't wait the full safety window when a required script hard-fails.
            window.setTimeout(function () {
              if (!pageLooksUsable() && !finishing && !closed) showFail();
            }, 1600);
          } else {
            armStallWatch();
          }
        },
        { once: true }
      );
      return true;
    }

    function scan() {
      var nodes = document.getElementsByTagName("script");
      for (var i = 0; i < nodes.length; i++) {
        var src = nodes[i].getAttribute("src") || "";
        if (src.indexOf(part) !== -1 && bind(nodes[i])) return true;
      }
      return false;
    }

    if (scan()) return;
    var mo = new MutationObserver(function () {
      if (scan()) mo.disconnect();
    });
    mo.observe(document.documentElement, { childList: true, subtree: true });
  }

  function watchReadyClass() {
    if (document.documentElement.classList.contains("az-app-ready")) {
      markUsable();
      earn(92);
      finishClean("app-ready");
      return;
    }
    var mo = new MutationObserver(function () {
      if (!document.documentElement.classList.contains("az-app-ready")) return;
      mo.disconnect();
      markUsable();
      earn(92);
      finishClean("app-ready");
    });
    mo.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
  }

  // Milestone: controller alive (before vendor.js).
  note("init");
  earn(8);
  paint(0);
  kickTween();

  if (image) {
    if (image.complete && image.naturalWidth) {
      earn(18);
    } else {
      image.addEventListener(
        "load",
        function () {
          earn(18);
        },
        { once: true }
      );
      image.addEventListener(
        "error",
        function () {
          image.style.opacity = "0";
          earn(14);
        },
        { once: true }
      );
    }
  } else {
    earn(14);
  }

  if (document.readyState === "loading") {
    document.addEventListener(
      "DOMContentLoaded",
      function () {
        earn(32);
      },
      { once: true }
    );
  } else {
    earn(32);
  }

  watchScript("vendor.js", 58, true);
  watchScript("components.js", 74, true);
  watchScript("az-app.js", 88, false);
  watchReadyClass();

  window.addEventListener("arts/preloader/start", function () {
    // Rhye may boot after AZ already finished — never reopen or reset progress.
    if (closed || finishing || failed) return;
    note("rhye-start");
    earn(Math.max(earned, 40));
    document.documentElement.classList.add("az-preloader-open", "az-loading");
    loader.hidden = false;
    loader.style.removeProperty("display");
  });

  window.addEventListener("arts/preloader/end", function () {
    if (closed || failed) return;
    markUsable();
    earn(96);
    finishClean("rhye-end");
  });

  window.addEventListener(
    "load",
    function () {
      earn(Math.max(earned, 90));
      if (pageLooksUsable()) finishClean("window-load");
    },
    { once: true }
  );

  if (retry) {
    retry.addEventListener("click", function () {
      window.location.reload();
    });
  }

  safetyTimer = window.setTimeout(onSafety, SAFETY_MS);

  // Expose a tiny hook for diagnostics / future milestones.
  window.AZ_preloader = {
    earn: earn,
    finish: finishClean,
    get progress() {
      return display;
    },
    get earned() {
      return earned;
    },
    get timeline() {
      return timeline.slice();
    },
    get finishReason() {
      return finishReason;
    },
  };
})();
