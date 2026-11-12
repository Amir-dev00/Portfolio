/* Rhye's counter is an input only. The visible digits, arc, and dot have one owner. */
(function () {
  "use strict";

  var html = document.documentElement;
  var loader = document.getElementById("js-preloader");
  if (!loader || !loader.classList.contains("az-preloader--ring")) return;

  var counter = loader.querySelector("[data-az-preloader-value]");
  var meter = loader.querySelector("[data-az-preloader-meter]");
  var nativeCounter = loader.querySelector(".preloader__counter-current");
  var ring = loader.querySelector(".az-preloader__ring-fill");
  var head = loader.querySelector(".az-preloader__ring-head");
  var image = loader.querySelector(".az-preloader__image");
  var retry = loader.querySelector("[data-az-preloader-retry]");
  var retryButton = loader.querySelector("[data-az-preloader-retry-btn]");
  var retryMessage = loader.querySelector("[data-az-preloader-retry-msg]");
  if (!counter || !meter || !ring) return;

  var query = new URLSearchParams(window.location.search);
  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var trace = query.has("az-preloader-trace") ? { marks: [] } : null;
  if (trace) window.__AZ_PRELOADER_TRACE__ = trace;

  var state = "loading";
  var target = 0;
  var rendered = 0;
  var segment;
  var frame = 0;
  var sparseTimer = 0;
  var deadline;
  var failure = "";
  var displayed = 0;
  var marks = Object.create(null);

  html.classList.add("az-preloader-js");

  function paint(progress) {
    var value = progress === 100 ? 100 : Math.floor(progress);
    ring.style.strokeDashoffset = String(100 - progress);
    if (head) {
      head.style.transform = "rotate(" + progress * 3.6 + "deg)";
      head.classList.toggle("is-active", progress > 0 && progress < 100 && state !== "retry");
      head.classList.toggle("is-complete", progress === 100);
    }
    if (value !== displayed) {
      counter.textContent = String(value);
      meter.setAttribute("aria-valuenow", String(value));
      displayed = value;
    }
  }

  function stopWaiting() {
    loader.classList.remove("az-preloader--waiting");
  }

  function close() {
    state = "closed";
    window.clearTimeout(deadline);
    window.clearTimeout(sparseTimer);
    if (frame) window.cancelAnimationFrame(frame);
    if (nativeObserver) nativeObserver.disconnect();
    loader.hidden = true;
    html.classList.remove("az-preloader-open", "az-loading");
  }

  function settled() {
    if (state === "finishing") {
      loader.classList.add("az-preloader--complete");
      state = "complete";
      if (reducedMotion) close();
      else window.setTimeout(close, 120);
    } else {
      loader.classList.add("az-preloader--waiting");
    }
  }

  function tick(now) {
    frame = 0;
    var elapsed = Math.min(1, Math.max(0, (now - segment.start) / segment.duration));
    var eased = 1 - Math.pow(1 - elapsed, 2);
    rendered = elapsed === 1 ? target : segment.from + (target - segment.from) * eased;
    paint(rendered);
    if (elapsed < 1) frame = window.requestAnimationFrame(tick);
    else settled();
  }

  function advance(value, source) {
    if (value <= target) return;
    var now = performance.now();
    target = value;
    if (trace) trace.marks.push({ t: now, pct: target, source: source });
    stopWaiting();
    if (reducedMotion) {
      rendered = target;
      paint(rendered);
      settled();
      return;
    }
    segment = {
      from: rendered,
      start: now,
      duration: state === "finishing" ? 380 : Math.min(620, 200 + (target - rendered) * 9),
    };
    if (!frame) frame = window.requestAnimationFrame(tick);
  }

  function mark(id, value) {
    if (state !== "loading" || marks[id]) return;
    marks[id] = true;
    advance(Math.min(90, value), id);
  }

  function ready() {
    if (state !== "loading" && state !== "retry") return;
    if (!html.classList.contains("az-app-ready")) return;
    if (query.has("az-preloader-block-ready")) return;
    state = "finishing";
    if (retry) retry.hidden = true;
    loader.classList.remove("az-preloader--retry");
    window.clearTimeout(deadline);
    advance(100, "view-ready");
  }

  function onTimeout() {
    if (state !== "loading") return;
    var app = document.getElementById("az-app");
    var view = app && app.querySelector(".az-view:not([hidden])");
    if (!failure && view && (view.children.length || view.textContent.trim())) {
      close();
      return;
    }
    state = "retry";
    if (frame) {
      window.cancelAnimationFrame(frame);
      frame = 0;
    }
    stopWaiting();
    paint(rendered);
    loader.classList.add("az-preloader--retry");
    if (retryMessage) {
      retryMessage.textContent = failure || "Still loading. Check your connection and try again.";
    }
    if (retry) retry.hidden = false;
  }

  function readNative() {
    if (state !== "loading" || !nativeCounter) return;
    var value = Number.parseFloat(nativeCounter.textContent);
    if (!Number.isFinite(value)) return;
    advance(Math.min(72, value * 0.72), "rhye-digit");
  }

  var nativeObserver = nativeCounter ? new MutationObserver(function () {
    if (!query.has("az-preloader-sparse")) {
      readNative();
    } else if (!sparseTimer) {
      sparseTimer = window.setTimeout(function () {
        sparseTimer = 0;
        readNative();
      }, 520);
    }
  }) : null;
  if (nativeObserver) nativeObserver.observe(nativeCounter, { childList: true, characterData: true, subtree: true });

  if (retryButton) retryButton.addEventListener("click", function () { window.location.reload(); });
  window.addEventListener("arts/preloader/end", function () { mark("rhye-end", 86); });
  if (image) {
    if (image.complete && image.naturalWidth) mark("logo", 16);
    else image.addEventListener("load", function () { mark("logo", 16); }, { once: true });
  }

  window.AZ_preloader = {
    mark: mark,
    ready: ready,
    fail: function (reason) {
      if (state === "loading") failure = reason;
    },
    getProgress: function () {
      return { confirmed: target, smoothed: rendered, finishing: state === "finishing", recovered: state === "retry" };
    },
  };

  mark("boot", 6);
  readNative();
  var requestedTimeout = Number(query.get("az-preloader-timeout"));
  deadline = window.setTimeout(onTimeout, requestedTimeout > 0 ? Math.max(500, requestedTimeout) : 15000);
  ready();
})();
