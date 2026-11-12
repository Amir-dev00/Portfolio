/* AZ circular preloader — visual ring adapter + reliable first paint.
   Rhye PagePreloader remains the source of truth for percent.
   Progress is never simulated. */
(function () {
  "use strict";

  var MIN_VISIBLE_MS = 1100;
  var loader = document.getElementById("js-preloader");
  if (!loader || !loader.classList.contains("az-preloader--ring")) return;

  var startedAt = performance.now();
  var endReceived = false;
  var closed = false;

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

  function updateFromNativeCounter() {
    var text = counter.textContent || "0";
    var parsed = parseFloat(String(text).replace(/[^0-9.]/g, ""));
    var percent = Number.isFinite(parsed) ? Math.max(0, Math.min(100, parsed)) : 0;
    var complete = percent >= 100;
    ring.style.strokeDashoffset = String(100 - percent);
    loader.style.setProperty("--az-progress", String(percent));
    loader.classList.toggle("az-preloader--complete", complete);
    if (head) {
      // Visible while 1–99; fade out at exactly 100 before exit.
      head.classList.toggle("is-active", percent > 0 && !complete);
      head.classList.toggle("is-complete", complete);
      head.style.removeProperty("opacity");
    }
  }

  updateFromNativeCounter();

  var observer = new MutationObserver(updateFromNativeCounter);
  observer.observe(counter, { childList: true, characterData: true, subtree: true });

  function closePreloaderShell() {
    if (closed) return;
    closed = true;
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
    scheduleClose();
  }

  window.addEventListener("arts/preloader/start", function () {
    startedAt = performance.now();
    document.documentElement.classList.add("az-preloader-open", "az-loading");
    loader.hidden = false;
    loader.style.removeProperty("display");
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
})();
