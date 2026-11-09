(function () {
  function disableCustomCursor() {
    document.documentElement.classList.add("cursor-disabled");
    var cursor = document.getElementById("js-cursor");
    if (cursor) {
      cursor.style.setProperty("display", "none", "important");
      cursor.style.setProperty("visibility", "hidden", "important");
      cursor.style.setProperty("opacity", "0", "important");
      cursor.style.setProperty("pointer-events", "none", "important");
      cursor.setAttribute("aria-hidden", "true");
      cursor.hidden = true;
    }
    document.documentElement.style.setProperty("cursor", "auto", "important");
    if (document.body) document.body.style.setProperty("cursor", "auto", "important");
  }

  function isPreloaderDone(preloader) {
    if (!preloader) return true;
    return (
      preloader.classList.contains("preloader_loaded") ||
      getComputedStyle(preloader).display === "none" ||
      preloader.hidden
    );
  }

  var nav = document.getElementById("glass-nav");
  var bar = nav && nav.querySelector(".glass-nav__bar");
  if (!nav || !bar) {
    // Still wait for preloader before killing cursor if nav markup is missing.
    var onlyPreloader = document.getElementById("js-preloader");
    if (isPreloaderDone(onlyPreloader)) disableCustomCursor();
    return;
  }

  var items = nav.querySelectorAll("[data-nav]");
  var magnetic = nav.querySelectorAll(".glass-nav__home, .glass-nav__tab, .glass-nav__action");
  var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function setActive(target) {
    items.forEach(function (item) {
      var active = item === target;
      item.classList.toggle("is-active", active);
      if (active) item.setAttribute("aria-current", "page");
      else item.removeAttribute("aria-current");
    });
  }

  items.forEach(function (item) {
    item.addEventListener("click", function () {
      var href = item.getAttribute("href") || "";
      var isExternal =
        href.indexOf("mailto:") === 0 ||
        item.hasAttribute("download") ||
        item.target === "_blank";
      if (isExternal) return;
      setActive(item);
    });
  });

  function onScroll() {
    nav.classList.toggle("is-scrolled", window.scrollY > 24);
  }
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  if (finePointer && !reduceMotion) {
    bar.addEventListener("pointermove", function (event) {
      var box = bar.getBoundingClientRect();
      var x = ((event.clientX - box.left) / box.width) * 100;
      var y = ((event.clientY - box.top) / box.height) * 100;
      bar.style.setProperty("--mx", x + "%");
      bar.style.setProperty("--my", y + "%");
    });

    magnetic.forEach(function (el) {
      el.addEventListener("pointermove", function (event) {
        var box = el.getBoundingClientRect();
        var dx = (event.clientX - (box.left + box.width / 2)) / box.width;
        var dy = (event.clientY - (box.top + box.height / 2)) / box.height;
        el.style.setProperty("--mag-x", Math.max(-3, Math.min(3, dx * 5)) + "px");
        el.style.setProperty("--mag-y", Math.max(-3, Math.min(3, dy * 5)) + "px");
      });
      el.addEventListener("pointerleave", function () {
        el.style.setProperty("--mag-x", "0px");
        el.style.setProperty("--mag-y", "0px");
      });
    });
  }

  function onPreloaderFinished() {
    nav.classList.add("is-ready");
    // Let the preloader exit timeline finish shrinking the SVG ring first.
    window.setTimeout(disableCustomCursor, 1600);
  }

  var preloader = document.getElementById("js-preloader");
  if (isPreloaderDone(preloader)) {
    onPreloaderFinished();
  } else {
    var started = false;
    var watch = new MutationObserver(function () {
      if (isPreloaderDone(preloader) && !started) {
        started = true;
        onPreloaderFinished();
        watch.disconnect();
      }
    });
    watch.observe(preloader, { attributes: true, attributeFilter: ["class", "style", "hidden"] });
    window.setTimeout(function () {
      if (!started) onPreloaderFinished();
    }, 8000);
  }
})();
