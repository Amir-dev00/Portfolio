/* AZ circular preloader — milestone progress + timeout recovery.
   Owns one smoothed progress value for the counter digits, ring, and head dot.
   Rhye PagePreloader may still rewrite the counter node; we treat that as a soft
   milestone floor and keep writing our own displayed value. 100% only on real readiness. */
(function () {
  "use strict";

  var MIN_VISIBLE_MS = 1100;
  var TIMEOUT_MS = 15000;
  try {
    var timeoutOverride = /(?:\?|&)az-preloader-timeout=(\d+)/.exec(String(location.search || ""));
    if (timeoutOverride) TIMEOUT_MS = Math.max(500, parseInt(timeoutOverride[1], 10) || TIMEOUT_MS);
  } catch (timeoutParseErr) {}
  var STALL_MS = 220;
  var INDET_AMP = 1.25;
  var INDET_HZ = 0.42;
  var FINISH_EPS = 0.15;
  var MIN_DUR_MS = 160;
  var MAX_DUR_MS = 420;
  var FINISH_MIN_MS = 240;
  var FINISH_MAX_MS = 380;

  var loader = document.getElementById("js-preloader");
  if (!loader || !loader.classList.contains("az-preloader--ring")) return;

  var startedAt = performance.now();
  var closed = false;
  var closeScheduled = false;
  var finishing = false;
  var recovered = false;
  var retryMode = false;
  var viewReady = false;
  var rhyeEnded = false;
  var criticalFailed = false;
  var failReason = "";
  var rafId = 0;
  var timeoutId = 0;
  var writingCounter = false;

  var reducedMotion = false;
  try {
    reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  } catch (e) {}

  var traceEnabled = false;
  try {
    traceEnabled = /(?:\?|&)az-preloader-trace(?:=|&|$)/.test(String(location.search || ""));
  } catch (e2) {}

  document.documentElement.classList.add("az-preloader-open", "az-loading", "az-preloader-js");
  loader.hidden = false;
  loader.style.removeProperty("display");
  loader.style.setProperty("opacity", "1");
  loader.style.setProperty("visibility", "visible");
  // Stop Blade-first CSS wait animation; JS owns ring geometry now.
  loader.querySelector(".az-preloader__ring-fill") &&
    (loader.querySelector(".az-preloader__ring-fill").style.animation = "none");
  loader.querySelector(".az-preloader__ring-head") &&
    (loader.querySelector(".az-preloader__ring-head").style.animation = "none");

  var counter = loader.querySelector(".preloader__counter-current");
  var ring = loader.querySelector(".az-preloader__ring-fill");
  var head = loader.querySelector(".az-preloader__ring-head");
  var image = loader.querySelector(".az-preloader__image");
  var retryRoot = loader.querySelector("[data-az-preloader-retry]");
  var retryBtn = loader.querySelector("[data-az-preloader-retry-btn]");
  var retryMsg = loader.querySelector("[data-az-preloader-retry-msg]");
  if (!counter || !ring) return;

  // Confirmed progress floor from milestones; smoothed value drives UI.
  var confirmed = 0;
  var smoothed = 0;
  var displayedInt = -1;
  var lastConfirmAt = performance.now();
  var lastFrameAt = performance.now();
  var stallStartedAt = 0;

  var animFrom = 0;
  var animTo = 0;
  var animStart = performance.now();
  var animDur = MIN_DUR_MS;
  var animActive = false;

  var marks = Object.create(null);

  function clamp(n, lo, hi) {
    return Math.max(lo, Math.min(hi, n));
  }

  function easeOutCubic(t) {
    var u = 1 - t;
    return 1 - u * u * u;
  }

  function durationForDelta(delta, isFinish) {
    var abs = Math.abs(delta);
    if (isFinish) return clamp(abs * 7.5 + 160, FINISH_MIN_MS, FINISH_MAX_MS);
    return clamp(abs * 11 + 120, MIN_DUR_MS, MAX_DUR_MS);
  }

  function startSegment(to, now) {
    animFrom = smoothed;
    animTo = clamp(to, 0, finishing ? 100 : 99.2);
    animStart = now;
    animDur = durationForDelta(animTo - animFrom, finishing || animTo >= 100);
    animActive = Math.abs(animTo - animFrom) > 0.001;
    stallStartedAt = 0;
  }

  function writeCounter(n) {
    var next = String(Math.round(clamp(n, 0, 100)));
    if (counter.textContent === next) {
      displayedInt = Number(next);
      return;
    }
    writingCounter = true;
    counter.textContent = next;
    displayedInt = Number(next);
    // Release on next microtask so our MutationObserver ignores the write.
    Promise.resolve().then(function () {
      writingCounter = false;
    });
  }

  function applyVisual(percent, holdDigits) {
    var p = clamp(percent, 0, 100);
    ring.style.strokeDashoffset = String(100 - p);
    if (head) {
      head.style.transform = "rotate(" + p * 3.6 + "deg)";
      var complete = finishing && p >= 100 - FINISH_EPS;
      var active = p > 0.35 && !complete && !retryMode;
      head.classList.toggle("is-active", active);
      head.classList.toggle("is-complete", complete || retryMode || recovered);
    }
    loader.classList.toggle("az-preloader--complete", finishing && p >= 100 - FINISH_EPS);
    if (!holdDigits) {
      // While finishing, digits track the sweep; otherwise hold the confirmed integer.
      writeCounter(finishing ? p : confirmed);
    } else {
      writeCounter(confirmed);
    }
  }

  function raiseConfirmed(pct, source) {
    var next = clamp(pct, 0, finishing ? 100 : 99);
    if (next <= confirmed + 0.001) return;
    confirmed = next;
    lastConfirmAt = performance.now();
    if (traceEnabled) {
      try {
        (window.__AZ_PRELOADER_TRACE__ = window.__AZ_PRELOADER_TRACE__ || { marks: [] }).marks.push({
          t: lastConfirmAt,
          pct: confirmed,
          source: source || "",
        });
      } catch (err) {}
    }
    if (reducedMotion) {
      smoothed = confirmed;
      animActive = false;
      applyVisual(smoothed, false);
      maybeFinishClose();
      return;
    }
    startSegment(confirmed, lastConfirmAt);
    ensureRaf();
  }

  function mark(id, pct) {
    if (!id || marks[id]) return;
    marks[id] = true;
    raiseConfirmed(pct, id);
  }

  function hasUsableServerView() {
    var app = document.getElementById("az-app");
    if (!app) return false;
    var active = app.querySelector(".az-view.is-active, .az-view.is-visible");
    if (!active) active = app.querySelector(".az-view:not([hidden])");
    if (!active) return false;
    // Server-rendered homepage / work / about / contact content is present.
    return active.children.length > 0 || (active.textContent || "").trim().length > 40;
  }

  function isInitialViewReady() {
    if (viewReady) return true;
    if (document.documentElement.classList.contains("az-app-ready") && hasUsableServerView()) {
      return true;
    }
    // Rhye finished and the SPA marked ready.
    if (rhyeEnded && document.documentElement.classList.contains("az-app-ready")) {
      return true;
    }
    return false;
  }

  function beginFinish() {
    if (finishing || recovered || retryMode || closed) return;
    // Test hook: keep the shell open past readiness to exercise timeout recovery.
    try {
      if (/(?:\?|&)az-preloader-block-ready(?:=|&|$)/.test(String(location.search || ""))) {
        return;
      }
    } catch (blockErr) {}
    if (!isInitialViewReady()) return;
    finishing = true;
    confirmed = 100;
    lastConfirmAt = performance.now();
    if (reducedMotion) {
      smoothed = 100;
      animActive = false;
      applyVisual(100, false);
      maybeFinishClose();
      return;
    }
    startSegment(100, lastConfirmAt);
    ensureRaf();
  }

  function maybeFinishClose() {
    if (!finishing || closeScheduled || closed || recovered || retryMode) return;
    if (smoothed < 100 - FINISH_EPS) return;
    smoothed = 100;
    confirmed = 100;
    writeCounter(100);
    applyVisual(100, false);
    if (head) {
      head.classList.add("is-complete");
      head.classList.remove("is-active");
    }
    closeScheduled = true;
    scheduleClose();
  }

  function closePreloaderShell() {
    if (closed) return;
    closed = true;
    if (rafId) {
      window.cancelAnimationFrame(rafId);
      rafId = 0;
    }
    if (timeoutId) {
      window.clearTimeout(timeoutId);
      timeoutId = 0;
    }
    document.documentElement.classList.remove("az-preloader-open");
    window.requestAnimationFrame(function () {
      var inline = (loader.getAttribute("style") || "").replace(/\s/g, "").toLowerCase();
      if (inline.indexOf("display:none") === -1) {
        loader.style.setProperty("display", "none");
      }
    });
    window.setTimeout(function () {
      document.documentElement.classList.remove("az-loading");
      try {
        observer.disconnect();
        hideWatcher.disconnect();
        readyWatcher.disconnect();
      } catch (err) {}
    }, 2200);
  }

  function scheduleClose() {
    var elapsed = performance.now() - startedAt;
    var wait = Math.max(0, MIN_VISIBLE_MS - elapsed);
    window.setTimeout(closePreloaderShell, wait);
  }

  /** Reveal usable content without claiming 100% / false completion. */
  function recoverReveal(reason) {
    if (closed || recovered || finishing) return;
    recovered = true;
    finishing = false;
    retryMode = false;
    loader.classList.add("az-preloader--recovered");
    loader.classList.remove("az-preloader--retry");
    if (head) {
      head.classList.add("is-complete");
      head.classList.remove("is-active");
    }
    // Hold the last confirmed percentage — never paint a fake 100.
    applyVisual(Math.min(smoothed, confirmed), true);
    writeCounter(confirmed);
    if (traceEnabled) {
      try {
        (window.__AZ_PRELOADER_TRACE__ = window.__AZ_PRELOADER_TRACE__ || {}).recovered = {
          t: performance.now(),
          confirmed: confirmed,
          reason: reason || "timeout",
        };
      } catch (err) {}
    }
    scheduleClose();
  }

  function showRetry(reason) {
    if (closed || recovered || finishing || retryMode) return;
    retryMode = true;
    criticalFailed = true;
    failReason = reason || "A required asset failed to load.";
    loader.classList.add("az-preloader--retry");
    if (retryMsg) retryMsg.textContent = failReason;
    if (retryRoot) retryRoot.hidden = false;
    if (head) {
      head.classList.add("is-complete");
      head.classList.remove("is-active");
    }
    applyVisual(smoothed, true);
    if (rafId) {
      window.cancelAnimationFrame(rafId);
      rafId = 0;
    }
  }

  function onTimeout() {
    if (closed || finishing || recovered || retryMode) return;
    // Prefer a real completion sweep when the first view is ready.
    if (isInitialViewReady()) {
      beginFinish();
      if (finishing || closeScheduled) return;
      // Ready but finish did not engage — still never paint a fake 100.
    }
    if (criticalFailed) {
      showRetry(failReason || "Something essential failed while loading.");
      return;
    }
    if (hasUsableServerView()) {
      recoverReveal("timeout-usable");
      return;
    }
    showRetry("Still loading after 15 seconds. Check your connection and try again.");
  }

  function tick(now) {
    rafId = 0;
    if (closed || retryMode) return;

    var dt = clamp(now - lastFrameAt, 0, 48);
    lastFrameAt = now;
    var visual = smoothed;
    var holdDigits = false;
    var stalled = false;

    if (!reducedMotion) {
      if (animActive) {
        var u = animDur <= 0 ? 1 : (now - animStart) / animDur;
        if (u >= 1) {
          smoothed = animTo;
          animActive = false;
          visual = smoothed;
        } else {
          smoothed = animFrom + (animTo - animFrom) * easeOutCubic(clamp(u, 0, 1));
          visual = smoothed;
        }
      } else {
        smoothed = animTo;
        visual = smoothed;
      }

      stalled =
        !finishing &&
        !animActive &&
        confirmed < 99 &&
        now - lastConfirmAt > STALL_MS;

      if (stalled) {
        holdDigits = true;
        if (!stallStartedAt) stallStartedAt = now;
        var elapsed = now - stallStartedAt;
        var wave = Math.sin(elapsed * 0.001 * INDET_HZ * Math.PI * 2) * INDET_AMP;
        var breath = (wave + INDET_AMP) * 0.5;
        visual = Math.min(99.15, confirmed + breath);
      } else {
        stallStartedAt = 0;
      }

      applyVisual(visual, holdDigits);
    } else {
      applyVisual(smoothed, false);
    }

    void dt;
    maybeFinishClose();
    if (!closed && !retryMode) rafId = window.requestAnimationFrame(tick);
  }

  function ensureRaf() {
    if (reducedMotion || rafId || closed || retryMode) return;
    lastFrameAt = performance.now();
    rafId = window.requestAnimationFrame(tick);
  }

  // --- Milestone wiring ---------------------------------------------------

  mark("boot", 6);

  if (image) {
    if (image.complete && image.naturalWidth > 0) {
      mark("logo", 16);
    } else {
      image.addEventListener(
        "load",
        function () {
          mark("logo", 16);
        },
        { once: true }
      );
      image.addEventListener(
        "error",
        function () {
          // Logo is brand polish — not a critical dependency for content.
          image.style.opacity = "0";
          mark("logo", 12);
        },
        { once: true }
      );
    }
  } else {
    mark("logo", 12);
  }

  function onDomReady() {
    mark("dom", 28);
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", onDomReady, { once: true });
  } else {
    onDomReady();
  }

  window.addEventListener(
    "load",
    function () {
      mark("window-load", 62);
      if (isInitialViewReady()) beginFinish();
    },
    { once: true }
  );

  // Soft floor from Rhye digit updates (never forces completion alone).
  var observer = new MutationObserver(function () {
    if (writingCounter) return;
    // After recovery / retry, Rhye may still stamp 100 — keep the honest digit locked.
    if (recovered || retryMode) {
      writeCounter(confirmed);
      return;
    }
    if (finishing) return;
    var text = counter.textContent || "0";
    var parsed = parseFloat(String(text).replace(/[^0-9.]/g, ""));
    if (!Number.isFinite(parsed)) return;
    // Map Rhye's percent into our mid-band so milestones stay authoritative.
    var soft = clamp(parsed * 0.72, 0, 88);
    if (soft > confirmed) raiseConfirmed(soft, "rhye-digit");
  });
  observer.observe(counter, { childList: true, characterData: true, subtree: true });

  window.addEventListener("arts/preloader/start", function () {
    startedAt = performance.now();
    closed = false;
    closeScheduled = false;
    finishing = false;
    recovered = false;
    retryMode = false;
    viewReady = false;
    rhyeEnded = false;
    confirmed = 6;
    smoothed = 6;
    animActive = false;
    marks = Object.create(null);
    marks.boot = true;
    loader.classList.remove("az-preloader--recovered", "az-preloader--retry", "az-preloader--complete");
    if (retryRoot) retryRoot.hidden = true;
    document.documentElement.classList.add("az-preloader-open", "az-loading", "az-preloader-js");
    loader.hidden = false;
    loader.style.removeProperty("display");
    applyVisual(6, false);
    ensureRaf();
  });

  window.addEventListener("arts/preloader/end", function () {
    rhyeEnded = true;
    mark("rhye-end", 86);
    if (isInitialViewReady()) beginFinish();
  });

  // Watch for SPA readiness without requiring a public API from az-app.
  var readyWatcher = new MutationObserver(function () {
    if (document.documentElement.classList.contains("az-app-ready")) {
      mark("app-ready", 78);
      viewReady = hasUsableServerView();
      if (isInitialViewReady()) beginFinish();
    }
  });
  readyWatcher.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
  if (document.documentElement.classList.contains("az-app-ready")) {
    mark("app-ready", 78);
    viewReady = hasUsableServerView();
  }

  // Fallback: Rhye hiding the node means it thinks load finished.
  var hideWatcher = new MutationObserver(function () {
    if (closed || recovered || retryMode || finishing) return;
    var display = window.getComputedStyle(loader).display;
    var inline = (loader.getAttribute("style") || "").replace(/\s/g, "").toLowerCase();
    if (loader.hidden || display === "none" || inline.indexOf("display:none") !== -1) {
      rhyeEnded = true;
      mark("rhye-hide", 86);
      if (isInitialViewReady()) {
        // Force-visible override may still be on — finish properly instead of vanishing at 60%.
        loader.hidden = false;
        loader.style.removeProperty("display");
        beginFinish();
      }
    }
  });
  hideWatcher.observe(loader, { attributes: true, attributeFilter: ["style", "hidden", "class"] });

  if (retryBtn) {
    retryBtn.addEventListener("click", function () {
      window.location.reload();
    });
  }

  // Public milestone / failure API used by scripts.blade.php
  window.AZ_preloader = {
    mark: mark,
    ready: function () {
      viewReady = true;
      mark("view-ready", 90);
      beginFinish();
    },
    fail: function (reason) {
      criticalFailed = true;
      failReason = reason || "A required asset failed to load.";
      // Don't immediately retry UI unless we're past timeout or nothing usable is on screen.
      if (!hasUsableServerView()) {
        showRetry(failReason);
      }
    },
    getProgress: function () {
      return { confirmed: confirmed, smoothed: smoothed, finishing: finishing, recovered: recovered };
    },
  };

  timeoutId = window.setTimeout(onTimeout, TIMEOUT_MS);

  // Seed UI and start the shared rAF controller.
  applyVisual(confirmed, false);
  ensureRaf();

  // Sparse-progress stress hook.
  try {
    if (/(?:\?|&)az-preloader-sparse(?:=|&|$)/.test(String(location.search || ""))) {
      var sparsePending = null;
      var sparseTimer = 0;
      observer.disconnect();
      var sparseObserver = new MutationObserver(function () {
        if (writingCounter) return;
        var text = counter.textContent || "0";
        var parsed = parseFloat(String(text).replace(/[^0-9.]/g, ""));
        if (!Number.isFinite(parsed)) return;
        sparsePending = clamp(parsed * 0.72, 0, 88);
        if (sparseTimer) return;
        sparseTimer = window.setTimeout(function () {
          sparseTimer = 0;
          if (sparsePending == null) return;
          raiseConfirmed(sparsePending, "sparse");
          sparsePending = null;
        }, 520);
      });
      sparseObserver.observe(counter, { childList: true, characterData: true, subtree: true });
    }
  } catch (sparseErr) {}
})();
