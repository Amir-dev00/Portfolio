/* AZ circular preloader — visual ring adapter + reliable first paint.
   Rhye PagePreloader remains the source of truth for percent (and the on-screen digits).
   Ring + head share one rAF controller: native updates are targets; we interpolate. */
(function () {
  "use strict";

  var MIN_VISIBLE_MS = 1100;
  var STALL_MS = 140;
  var INDET_AMP = 1.35;
  var INDET_HZ = 0.48;
  var FINISH_EPS = 0.15;
  var MIN_DUR_MS = 180;
  var MAX_DUR_MS = 420;
  var FINISH_MIN_MS = 240;
  var FINISH_MAX_MS = 380;

  var loader = document.getElementById("js-preloader");
  if (!loader || !loader.classList.contains("az-preloader--ring")) return;

  var startedAt = performance.now();
  var endReceived = false;
  var closed = false;
  var closeScheduled = false;
  var finishing = false;
  var rafId = 0;

  var reducedMotion = false;
  try {
    reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  } catch (e) {}

  var traceEnabled = false;
  try {
    traceEnabled = /(?:\?|&)az-preloader-trace(?:=|&|$)/.test(String(location.search || ""));
  } catch (e2) {}
  var nativeSamples = [];
  var frameSamples = [];
  var lastTraceFrameAt = 0;

  document.documentElement.classList.add("az-preloader-open", "az-loading");
  loader.hidden = false;
  loader.style.removeProperty("display");
  loader.style.setProperty("opacity", "1");
  loader.style.setProperty("visibility", "visible");

  var counter = loader.querySelector(".preloader__counter-current");
  var ring = loader.querySelector(".az-preloader__ring-fill");
  var head = loader.querySelector(".az-preloader__ring-head");
  var image = loader.querySelector(".az-preloader__image");
  if (!counter || !ring) return;

  var nativeTarget = 0;
  var rendered = 0;
  var lastNativeChangeAt = performance.now();
  var lastFrameAt = performance.now();
  var stallStartedAt = 0;

  // Retargetable segment: always ease from the live rendered value toward the latest target.
  var animFrom = 0;
  var animTo = 0;
  var animStart = performance.now();
  var animDur = MIN_DUR_MS;
  var animActive = false;

  function clamp(n, lo, hi) {
    return Math.max(lo, Math.min(hi, n));
  }

  function easeOutCubic(t) {
    var u = 1 - t;
    return 1 - u * u * u;
  }

  function durationForDelta(delta, isFinish) {
    var abs = Math.abs(delta);
    if (isFinish) {
      return clamp(abs * 7.5 + 160, FINISH_MIN_MS, FINISH_MAX_MS);
    }
    return clamp(abs * 11 + 120, MIN_DUR_MS, MAX_DUR_MS);
  }

  function startSegment(to, now) {
    animFrom = rendered;
    animTo = clamp(to, 0, 100);
    animStart = now;
    animDur = durationForDelta(animTo - animFrom, finishing || animTo >= 100);
    animActive = Math.abs(animTo - animFrom) > 0.001;
    stallStartedAt = 0;
  }

  function parseNativePercent() {
    var text = counter.textContent || "0";
    var parsed = parseFloat(String(text).replace(/[^0-9.]/g, ""));
    if (!Number.isFinite(parsed)) return 0;
    return clamp(parsed, 0, 100);
  }

  function applyVisual(percent) {
    var p = clamp(percent, 0, 100);
    ring.style.strokeDashoffset = String(100 - p);
    if (head) {
      head.style.transform = "rotate(" + p * 3.6 + "deg)";
      var complete = finishing && p >= 100 - FINISH_EPS;
      var active = p > 0.35 && !complete;
      head.classList.toggle("is-active", active);
      head.classList.toggle("is-complete", complete);
    }
    loader.classList.toggle("az-preloader--complete", finishing && p >= 100 - FINISH_EPS);
  }

  function publishTrace() {
    if (!traceEnabled) return;
    try {
      var gaps = nativeSamples.slice(1).map(function (s, i) {
        return Math.round(s.t - nativeSamples[i].t);
      });
      window.__AZ_PRELOADER_TRACE__ = {
        natives: nativeSamples.slice(),
        frames: frameSamples.slice(),
        nativeGapsMs: gaps,
      };
    } catch (err) {}
  }

  function maybeFinishClose() {
    if (!finishing || closeScheduled || closed) return;
    if (rendered < 100 - FINISH_EPS) return;
    rendered = 100;
    applyVisual(100);
    closeScheduled = true;
    publishTrace();
    scheduleClose();
  }

  function setNativeTarget(percent, fromObserver) {
    var next = clamp(percent, 0, 100);
    var now = performance.now();
    var same = Math.abs(next - nativeTarget) <= 0.001;

    if (next < nativeTarget && !finishing) {
      // Ignore brief Rhye regressions; never roll the ring backward mid-load.
      return;
    }

    if (same) {
      // Same digit rewrite — do not refresh stall timing or restart a segment
      // (restarting at 100 was starving the final sweep with ease-out restarts).
      return;
    }

    nativeTarget = next;
    lastNativeChangeAt = now;

    if (traceEnabled && fromObserver) {
      nativeSamples.push({
        t: now,
        native: nativeTarget,
        rendered: Math.round(rendered * 100) / 100,
      });
    }

    if (nativeTarget >= 100) {
      finishing = true;
      nativeTarget = 100;
    }

    if (reducedMotion) {
      rendered = nativeTarget;
      animActive = false;
      applyVisual(rendered);
      maybeFinishClose();
      return;
    }

    startSegment(nativeTarget, now);
  }

  function tick(now) {
    rafId = 0;
    if (closed) return;

    var dt = clamp(now - lastFrameAt, 0, 48);
    lastFrameAt = now;
    var visual = rendered;
    var stalled = false;

    if (!reducedMotion) {
      if (animActive) {
        var u = animDur <= 0 ? 1 : (now - animStart) / animDur;
        if (u >= 1) {
          rendered = animTo;
          animActive = false;
          visual = rendered;
        } else {
          rendered = animFrom + (animTo - animFrom) * easeOutCubic(clamp(u, 0, 1));
          visual = rendered;
        }
      } else {
        rendered = animTo;
        visual = rendered;
      }

      // After the segment settles, keep the ring alive with subtle indeterminate motion
      // while Rhye is quiet — without advancing the committed progress base.
      stalled =
        !finishing &&
        !animActive &&
        nativeTarget < 100 &&
        now - lastNativeChangeAt > STALL_MS;

      if (stalled) {
        if (!stallStartedAt) stallStartedAt = now;
        var elapsed = now - stallStartedAt;
        var wave = Math.sin(elapsed * 0.001 * INDET_HZ * Math.PI * 2) * INDET_AMP;
        var breath = (wave + INDET_AMP) * 0.5;
        visual = Math.min(99.15, rendered + breath);
      } else if (!animActive) {
        stallStartedAt = 0;
      }

      applyVisual(visual);
    }

    if (traceEnabled && frameSamples.length < 360) {
      var drop = dt > 22;
      if (drop || now - lastTraceFrameAt > 48) {
        lastTraceFrameAt = now;
        frameSamples.push({
          t: Math.round(now),
          dt: Math.round(dt * 10) / 10,
          rendered: Math.round(rendered * 100) / 100,
          visual: Math.round(visual * 100) / 100,
          target: nativeTarget,
          anim: animActive ? 1 : 0,
          stall: stalled ? 1 : 0,
        });
      }
    }

    maybeFinishClose();

    if (!closed) {
      rafId = window.requestAnimationFrame(tick);
    }
  }

  function ensureRaf() {
    if (reducedMotion || rafId || closed) return;
    lastFrameAt = performance.now();
    rafId = window.requestAnimationFrame(tick);
  }

  // Seed from whatever Rhye already wrote.
  setNativeTarget(parseNativePercent(), false);
  applyVisual(rendered);
  ensureRaf();

  var observer = new MutationObserver(function () {
    setNativeTarget(parseNativePercent(), true);
    ensureRaf();
  });
  observer.observe(counter, { childList: true, characterData: true, subtree: true });

  function closePreloaderShell() {
    if (closed) return;
    closed = true;
    if (rafId) {
      window.cancelAnimationFrame(rafId);
      rafId = 0;
    }
    // Drop the force-visible override so Rhye's dismissal can apply.
    document.documentElement.classList.remove("az-preloader-open");
    // If Rhye already queued display:none, keep it; otherwise hide after the shell has painted.
    window.requestAnimationFrame(function () {
      var inline = (loader.getAttribute("style") || "").replace(/\s/g, "").toLowerCase();
      if (inline.indexOf("display:none") === -1) {
        loader.style.setProperty("display", "none");
      }
    });
    window.setTimeout(function () {
      document.documentElement.classList.remove("az-loading");
      observer.disconnect();
      hideWatcher.disconnect();
    }, 2200);
  }

  function scheduleClose() {
    var elapsed = performance.now() - startedAt;
    // Keep the shell painted long enough to survive cached reloads (no fake %).
    var wait = Math.max(0, MIN_VISIBLE_MS - elapsed);
    window.setTimeout(closePreloaderShell, wait);
  }

  function onRhyeEnd() {
    if (endReceived) return;
    endReceived = true;
    finishing = true;
    nativeTarget = 100;
    lastNativeChangeAt = performance.now();
    stallStartedAt = 0;
    if (reducedMotion) {
      rendered = 100;
      animActive = false;
      applyVisual(100);
      maybeFinishClose();
      return;
    }
    // Only retarget if we are not already sweeping to 100.
    if (!(animActive && animTo >= 100)) {
      startSegment(100, performance.now());
    }
    ensureRaf();
  }

  window.addEventListener("arts/preloader/start", function () {
    startedAt = performance.now();
    endReceived = false;
    finishing = false;
    closeScheduled = false;
    closed = false;
    nativeTarget = 0;
    rendered = 0;
    animFrom = 0;
    animTo = 0;
    animActive = false;
    stallStartedAt = 0;
    lastNativeChangeAt = performance.now();
    nativeSamples.length = 0;
    frameSamples.length = 0;
    document.documentElement.classList.add("az-preloader-open", "az-loading");
    loader.hidden = false;
    loader.style.removeProperty("display");
    applyVisual(0);
    ensureRaf();
  });

  window.addEventListener("arts/preloader/end", onRhyeEnd);

  // Fallback if the custom event name differs across builds: watch Rhye hiding the node.
  var hideWatcher = new MutationObserver(function () {
    if (closed || endReceived) return;
    var display = window.getComputedStyle(loader).display;
    var inline = (loader.getAttribute("style") || "").replace(/\s/g, "").toLowerCase();
    if (loader.hidden || display === "none" || inline.indexOf("display:none") !== -1) {
      onRhyeEnd();
    }
  });
  hideWatcher.observe(loader, { attributes: true, attributeFilter: ["style", "hidden", "class"] });

  if (image) {
    image.addEventListener("error", function () {
      image.style.opacity = "0";
    });
  }

  // Absolute safety net — never leave the UI locked behind the loader.
  window.setTimeout(function () {
    onRhyeEnd();
  }, 15000);

  // Test hook: ?az-preloader-sparse=1 coalesces native updates ~520ms apart to stress interpolation.
  try {
    if (/(?:\?|&)az-preloader-sparse(?:=|&|$)/.test(String(location.search || ""))) {
      var sparsePending = null;
      var sparseTimer = 0;
      observer.disconnect();
      var sparseObserver = new MutationObserver(function () {
        sparsePending = parseNativePercent();
        if (sparseTimer) return;
        sparseTimer = window.setTimeout(function () {
          sparseTimer = 0;
          if (sparsePending == null) return;
          setNativeTarget(sparsePending, true);
          sparsePending = null;
          ensureRaf();
        }, 520);
      });
      sparseObserver.observe(counter, { childList: true, characterData: true, subtree: true });
    }
  } catch (sparseErr) {}
})();
