/* The number and the ring share a single, time-based animation. */
(function () {
  "use strict";

  var loader = document.getElementById("az-preloader") || document.getElementById("js-preloader");
  if (!loader || !loader.classList.contains("az-preloader--ring")) return;

  var html = document.documentElement;
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)");
  var counter = loader.querySelector("[data-az-preloader-count]");
  var ring = loader.querySelector(".az-preloader__ring-fill");
  var head = loader.querySelector(".az-preloader__ring-head");
  if (!counter || !ring || !head) return;
  var failure = loader.querySelector("[data-az-preloader-fail]");
  var retry = loader.querySelector("[data-az-preloader-retry]");
  var start = performance.now();
  var previous = start;
  var progress = 0;
  var readyAt = null;
  var readyProgress = 0;
  var finishDuration = 0;
  var FAST_READY_MS = 650;
  var FAST_DISPLAY_MS = 1150;
  var frame = 0;
  var timeout;
  var state = "loading";

  function paint() {
    var shown = progress === 100 ? 100 : Math.floor(progress);
    if (counter.textContent !== String(shown)) counter.textContent = String(shown);
    ring.style.strokeDashoffset = String(100 - progress);
    loader.style.setProperty("--az-progress", String(progress));
    head.classList.toggle("is-active", progress > 0 && progress < 100);
    loader.classList.toggle("az-preloader--complete", progress === 100);
  }

  function dismiss() {
    state = "done";
    html.classList.remove("az-preloader-open");
    window.setTimeout(function () {
      loader.hidden = true;
      html.classList.remove("az-loading");
      window.dispatchEvent(new Event("az:preloader-end"));
    }, reduce.matches ? 0 : 240);
  }

  function step(now) {
    frame = 0;
    if (readyAt !== null) {
      var elapsed = Math.min(1, (now - readyAt) / finishDuration);
      // Limit a large timestamp gap after a blocked frame or background tab.
      var target = readyProgress + (100 - readyProgress) * elapsed;
      progress = reduce.matches ? 100 : Math.min(target, progress + 3.5);
    } else {
      // A bounded indication of waiting, independent of real network byte counts.
      var age = Math.max(0, now - start);
      var estimate = 89 * (1 - Math.exp(-age / 5700));
      var rate = Math.min(50, Math.max(0, now - previous)) * 0.025;
      progress = Math.min(estimate, progress + rate);
    }
    previous = now;
    paint();

    if (progress >= 100) dismiss();
    else if (readyAt !== null || !reduce.matches) frame = window.requestAnimationFrame(step);
  }

  function finish() {
    if (state !== "loading") return;
    state = "finishing";
    window.clearTimeout(timeout);
    readyAt = performance.now();
    readyProgress = progress;
    var startupMs = readyAt - start;
    finishDuration = startupMs < 350 ? 500 : Math.min(850, Math.max(300, (100 - readyProgress) * 10));

    // Include the time before this script loaded when deciding whether startup was fast.
    var connection = window.navigator && window.navigator.connection;
    var constrained = connection && (connection.saveData || /^(slow-2g|2g|3g)$/.test(connection.effectiveType));
    if (!reduce.matches && !constrained && readyAt < FAST_READY_MS) {
      finishDuration = Math.max(finishDuration, FAST_DISPLAY_MS - startupMs);
    }

    if (!frame) frame = window.requestAnimationFrame(step);
  }

  function fail() {
    if (state !== "loading") return;
    state = "failed";
    window.clearTimeout(timeout);
    window.cancelAnimationFrame(frame);
    loader.classList.add("az-preloader--fail");
    head.classList.remove("is-active");
    if (failure) failure.hidden = false;
  }

  if (retry) retry.addEventListener("click", function () { window.location.reload(); });
  window.addEventListener("az:startup-error", fail, { once: true });
  window.addEventListener("az:app-ready", finish, { once: true });
  timeout = window.setTimeout(fail, 45000);
  if (!reduce.matches) frame = window.requestAnimationFrame(step);
  if (html.classList.contains("az-app-ready")) finish();
  else if (window.AZ_STARTUP_FAILED) fail();
})();
