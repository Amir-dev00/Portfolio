/* Hash SPA router — Home / Work / About / Contact. Dock stays mounted. */
(function () {
  "use strict";

  var REDUCE = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var TITLE_BASE = "Amirhossein Zamanzade | Developer";
  var app = document.getElementById("az-app");
  var nav = document.getElementById("glass-nav");
  if (!app) return;

  var views = {
    home: document.getElementById("view-home"),
    work: document.getElementById("view-work"),
    about: document.getElementById("view-about"),
    contact: document.getElementById("view-contact"),
    project: document.getElementById("view-project"),
  };

  var workBrowser = document.getElementById("az-work-browser");
  var projectRoot = document.getElementById("az-project-root");
  var indicator = nav && nav.querySelector(".glass-nav__indicator");
  var currentKey = null;
  var animating = false;
  var pendingRoute = null;
  var transitionTimer = 0;
  var homeSliderWanted = true;
  var nativeScrollWanted = false;
  var homeSliderPoll = 0;
  var scrollModePoll = 0;
  var workIndex = 0;
  var workLastSlug = "";
  var workBound = false;
  var workFadeTimer = 0;
  var WORK_ROW_H = 48;
  var WORK_VISIBLE = 5;

  function normalizeHash(raw) {
    var hash = String(raw || "").replace(/^#/, "").replace(/^\/+/, "");
    if (!hash || hash === "selected-work" || hash === "/") return { name: "home" };
    if (hash === "home") return { name: "home" };
    if (hash === "work") return { name: "work" };
    if (hash === "about") return { name: "about" };
    if (hash === "contact") return { name: "contact" };
    if (hash.indexOf("work/") === 0) {
      return { name: "project", slug: decodeURIComponent(hash.slice(5)) };
    }
    return { name: "home" };
  }

  function routeKey(route) {
    return route.name === "project" ? "project:" + route.slug : route.name;
  }

  function viewFor(route) {
    return views[route.name] || views.home;
  }

  function setDocumentTitle(route) {
    if (route.name === "work") document.title = "Work — " + TITLE_BASE;
    else if (route.name === "about") document.title = "About — " + TITLE_BASE;
    else if (route.name === "contact") document.title = "Contact — " + TITLE_BASE;
    else if (route.name === "project") {
      var project = window.AZ_getProjectBySlug && window.AZ_getProjectBySlug(route.slug);
      document.title = (project ? project.title + " — " : "") + TITLE_BASE;
    } else document.title = TITLE_BASE;
  }

  function getHomeSwiper() {
    var el = document.querySelector(".js-slider-fullscreen-projects__images");
    return el && el.swiper ? el.swiper : null;
  }

  function getPageScrollbar() {
    var pw = document.getElementById("page-wrapper");
    if (!pw || !window.Scrollbar || typeof window.Scrollbar.get !== "function") return null;
    try {
      return window.Scrollbar.get(pw) || null;
    } catch (err) {
      return null;
    }
  }

  function applyHomeSliderLive() {
    var swiper = getHomeSwiper();
    if (!swiper) return false;
    try {
      if (homeSliderWanted) {
        if (typeof swiper.update === "function") swiper.update();
        if (swiper.autoplay && typeof swiper.autoplay.start === "function") swiper.autoplay.start();
        if ("allowTouchMove" in swiper) swiper.allowTouchMove = true;
        if (swiper.mousewheel && typeof swiper.mousewheel.enable === "function") {
          swiper.mousewheel.enable();
        }
        window.dispatchEvent(new Event("resize"));
      } else {
        if (swiper.autoplay && typeof swiper.autoplay.stop === "function") swiper.autoplay.stop();
        if ("allowTouchMove" in swiper) swiper.allowTouchMove = false;
        if (swiper.mousewheel && typeof swiper.mousewheel.disable === "function") {
          swiper.mousewheel.disable();
        }
      }
    } catch (err) {
      /* Rhye swiper internals may differ across builds. */
    }
    return true;
  }

  function setHomeSliderLive(live) {
    homeSliderWanted = !!live;
    if (applyHomeSliderLive()) {
      if (homeSliderPoll) {
        window.clearInterval(homeSliderPoll);
        homeSliderPoll = 0;
      }
      return;
    }
    // Swiper can boot after the first route paint (esp. direct #work).
    if (homeSliderPoll) window.clearInterval(homeSliderPoll);
    var tries = 0;
    homeSliderPoll = window.setInterval(function () {
      tries += 1;
      if (applyHomeSliderLive() || tries > 50) {
        window.clearInterval(homeSliderPoll);
        homeSliderPoll = 0;
      }
    }, 100);
  }

  function initPageScrollbar() {
    var pw = document.getElementById("page-wrapper");
    if (!pw || !window.Scrollbar || typeof window.Scrollbar.init !== "function") return null;
    if (getPageScrollbar()) return getPageScrollbar();
    var opts = (window.theme && window.theme.smoothScroll) || {};
    try {
      return window.Scrollbar.init(pw, {
        damping: opts.damping != null ? opts.damping : 0.12,
        renderByPixels: opts.renderByPixels !== false,
        continuousScrolling: !!opts.continuousScrolling,
        plugins: opts.plugins || {
          edgeEasing: true,
          lockscroll: { lock: false },
          softscroll: {},
        },
      });
    } catch (err) {
      return null;
    }
  }

  function applyPageScrollMode() {
    var pw = document.getElementById("page-wrapper");
    if (!pw) return false;
    var sb = getPageScrollbar();

    if (nativeScrollWanted) {
      document.documentElement.classList.add("az-native-scroll");
      if (sb) {
        try {
          if (typeof sb.setPosition === "function") sb.setPosition(0, 0);
        } catch (err) {}
        try {
          sb.destroy();
        } catch (err) {}
        sb = null;
      }
      // Drop leftover smooth-scrollbar inline lock so CSS can open native overflow.
      if (pw.style.overflow === "hidden") pw.style.overflow = "";
      if (pw.style.height && /px$/.test(pw.style.height)) pw.style.height = "";
      return !getPageScrollbar();
    }

    document.documentElement.classList.remove("az-native-scroll");
    if (!sb) sb = initPageScrollbar();
    if (sb && typeof sb.update === "function") {
      try {
        sb.update();
      } catch (err) {}
    }
    return !!sb;
  }

  function setNativePageScroll(native) {
    nativeScrollWanted = !!native;
    applyPageScrollMode();
    if (scrollModePoll) window.clearInterval(scrollModePoll);
    var tries = 0;
    scrollModePoll = window.setInterval(function () {
      tries += 1;
      var done = applyPageScrollMode();
      // Poll briefly: Rhye may init Scrollbar after the first route paint.
      if (tries > 40 || (done && tries >= 4)) {
        window.clearInterval(scrollModePoll);
        scrollModePoll = 0;
      }
    }, 100);
  }

  function moveIndicator(navKey) {
    if (!nav || !indicator) return;
    var target =
      nav.querySelector('[data-nav="' + navKey + '"]') ||
      (navKey === "work" ? nav.querySelector('[data-nav="portfolio"]') : null);
    if (!target || navKey === "cv") {
      indicator.classList.remove("is-on", "is-home");
      return;
    }

    var bar = nav.querySelector(".glass-nav__bar");
    if (!bar) return;

    var isHome = navKey === "home";
    var icon =
      (isHome && target.querySelector(".glass-nav__home-glass")) ||
      target.querySelector(".glass-nav__icon, .glass-nav__action-icon, .glass-nav__home-icon") ||
      target;
    var label = target.querySelector(".glass-nav__name");

    // Neutralize hover/tap transforms so the capsule stays centered on stable layout.
    var prevMagX = target.style.getPropertyValue("--mag-x");
    var prevMagY = target.style.getPropertyValue("--mag-y");
    var prevTransform = target.style.transform;
    target.style.setProperty("--mag-x", "0px");
    target.style.setProperty("--mag-y", "0px");
    target.style.transform = "none";

    var barBox = bar.getBoundingClientRect();
    var iconBox = icon.getBoundingClientRect();
    var labelBox = label ? label.getBoundingClientRect() : null;

    target.style.setProperty("--mag-x", prevMagX || "0px");
    target.style.setProperty("--mag-y", prevMagY || "0px");
    target.style.transform = prevTransform;

    var ovalW;
    var ovalH;
    var x;
    var y;

    if (isHome) {
      ovalW = Math.round(iconBox.width);
      ovalH = Math.round(iconBox.height);
      x = iconBox.left + iconBox.width / 2 - barBox.left - ovalW / 2;
      y = iconBox.top + iconBox.height / 2 - barBox.top - ovalH / 2;
    } else {
      var left = labelBox ? Math.min(iconBox.left, labelBox.left) : iconBox.left;
      var right = labelBox ? Math.max(iconBox.right, labelBox.right) : iconBox.right;
      var top = labelBox ? Math.min(iconBox.top, labelBox.top) : iconBox.top;
      var bottom = labelBox ? Math.max(iconBox.bottom, labelBox.bottom) : iconBox.bottom;
      var contentW = right - left;
      var contentH = bottom - top;
      var padX = Math.max(8, Math.round(contentW * 0.18));
      var padY = Math.max(7, Math.round(contentH * 0.14));
      ovalW = Math.round(contentW + padX * 2);
      ovalH = Math.round(contentH + padY * 2);
      x = (left + right) / 2 - barBox.left - ovalW / 2;
      y = (top + bottom) / 2 - barBox.top - ovalH / 2;
    }

    indicator.classList.toggle("is-home", isHome);
    indicator.style.width = ovalW + "px";
    indicator.style.height = ovalH + "px";
    indicator.style.transform = "translate3d(" + x.toFixed(2) + "px," + y.toFixed(2) + "px,0)";
    indicator.classList.add("is-on");
  }

  function setDockActive(route) {
    if (!nav) return;
    var navKey = route.name === "project" ? "work" : route.name;
    var map = { home: "home", work: "work", about: "about", contact: "contact" };
    var activeKey = map[navKey] || "home";
    nav.querySelectorAll("[data-nav]").forEach(function (item) {
      var key = item.getAttribute("data-nav");
      var normalized = key === "portfolio" ? "work" : key;
      var active = normalized === activeKey && key !== "cv";
      item.classList.toggle("is-active", active);
      if (active) item.setAttribute("aria-current", "page");
      else item.removeAttribute("aria-current");
    });
    moveIndicator(activeKey);
    window.requestAnimationFrame(function () {
      moveIndicator(activeKey);
    });
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function getProjects() {
    return window.AZ_PROJECTS || [];
  }

  function indexForSlug(slug) {
    var projects = getProjects();
    for (var i = 0; i < projects.length; i += 1) {
      if (projects[i].slug === slug) return i;
    }
    return 0;
  }

  function techTagsHtml(project) {
    return (project.technologies || [])
      .slice(0, 5)
      .map(function (tech) {
        return '<span class="az-work-tag">' + escapeHtml(tech) + "</span>";
      })
      .join("");
  }

  function spotlightHtml(project) {
    var href = "#work/" + encodeURIComponent(project.slug);
    return (
      '<a class="az-work-spotlight__cover" href="' +
      href +
      '" aria-label="Open ' +
      escapeHtml(project.title) +
      '">' +
      '<img src="' +
      escapeHtml(project.image) +
      '" alt="" width="960" height="600" decoding="async"/>' +
      "</a>" +
      '<div class="az-work-spotlight__copy">' +
      '<p class="az-work-spotlight__eyebrow">Selected work</p>' +
      "<h2>" +
      escapeHtml(project.title) +
      "</h2>" +
      "<p>" +
      escapeHtml(project.shortDescription) +
      "</p>" +
      '<div class="az-work-tags">' +
      techTagsHtml(project) +
      "</div>" +
      '<a class="az-work-explore" href="' +
      href +
      '">Explore project <span aria-hidden="true">↗</span></a>' +
      "</div>"
    );
  }

  function buildWorkBrowser() {
    if (!workBrowser) return;
    var projects = getProjects();
    var rows = projects
      .map(function (project, index) {
        return (
          '<button type="button" class="az-work-row" role="option" data-index="' +
          index +
          '" data-slug="' +
          escapeHtml(project.slug) +
          '" aria-selected="false">' +
          '<span class="az-work-row__name">' +
          escapeHtml(project.title) +
          "</span>" +
          "<small>" +
          escapeHtml(project.category) +
          "</small>" +
          '<span class="az-work-row__chev" aria-hidden="true">↗</span>' +
          "</button>"
        );
      })
      .join("");

    workBrowser.innerHTML =
      '<div class="az-work-spotlight" role="region" aria-live="polite" aria-atomic="true"></div>' +
      '<div class="az-work-wheel-heading">' +
      '<span class="az-work-count">' +
      projects.length +
      " projects</span>" +
      '<div class="az-work-wheel-controls">' +
      '<button type="button" class="az-work-browse" data-dir="-1" aria-label="Previous project">' +
      '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 14.5L12 8.5l6 6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
      "</button>" +
      '<button type="button" class="az-work-browse" data-dir="1" aria-label="Next project">' +
      '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 9.5L12 15.5l6-6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
      "</button>" +
      "</div>" +
      "</div>" +
      '<div class="az-work-wheel" role="listbox" tabindex="0" aria-label="Select a project; use up and down arrow keys">' +
      '<div class="az-work-wheel__track">' +
      rows +
      "</div>" +
      "</div>";
  }

  function updateWorkWheel() {
    if (!workBrowser) return;
    var track = workBrowser.querySelector(".az-work-wheel__track");
    var rows = workBrowser.querySelectorAll(".az-work-row");
    if (!track || !rows.length) return;

    var centerOffset = (WORK_VISIBLE / 2 - 0.5) * WORK_ROW_H;
    var y = centerOffset - workIndex * WORK_ROW_H;
    track.style.transform = "translate3d(0," + y + "px,0)";

    rows.forEach(function (row, i) {
      var distance = Math.abs(i - workIndex);
      var selected = i === workIndex;
      row.classList.toggle("is-selected", selected);
      row.setAttribute("aria-selected", selected ? "true" : "false");
      row.tabIndex = selected ? 0 : -1;
      var opacity = selected ? 1 : Math.max(0.14, 1 - distance * 0.28);
      row.style.opacity = String(opacity);
      row.style.setProperty("--az-row-distance", String(distance));
    });
  }

  function updateWorkSpotlight(animate) {
    if (!workBrowser) return;
    var projects = getProjects();
    var project = projects[workIndex];
    if (!project) return;
    var spotlight = workBrowser.querySelector(".az-work-spotlight");
    if (!spotlight) return;

    workLastSlug = project.slug;
    spotlight.setAttribute("aria-label", project.title);

    if (!animate || REDUCE) {
      spotlight.innerHTML = spotlightHtml(project);
      spotlight.classList.remove("is-fading");
      return;
    }

    spotlight.classList.add("is-fading");
    if (workFadeTimer) window.clearTimeout(workFadeTimer);
    workFadeTimer = window.setTimeout(function () {
      spotlight.innerHTML = spotlightHtml(project);
      window.requestAnimationFrame(function () {
        spotlight.classList.remove("is-fading");
      });
    }, 140);
  }

  function setWorkIndex(nextIndex, animate) {
    var projects = getProjects();
    if (!projects.length) return;
    var clamped = Math.max(0, Math.min(projects.length - 1, nextIndex));
    var changed = clamped !== workIndex;
    workIndex = clamped;
    updateWorkWheel();
    if (changed || animate === "force") updateWorkSpotlight(animate !== false);
  }

  function bindWorkBrowser() {
    if (!workBrowser || workBound) return;
    workBound = true;
    var touchStartY = 0;
    var touchActive = false;

    workBrowser.addEventListener("click", function (event) {
      var browse = event.target.closest(".az-work-browse");
      if (browse) {
        event.preventDefault();
        setWorkIndex(workIndex + (parseInt(browse.getAttribute("data-dir"), 10) || 0), true);
        return;
      }
      var row = event.target.closest(".az-work-row");
      if (row) {
        event.preventDefault();
        setWorkIndex(parseInt(row.getAttribute("data-index"), 10) || 0, true);
      }
    });

    workBrowser.addEventListener("keydown", function (event) {
      var target = event.target;
      if (!workBrowser.contains(target)) return;
      if (event.key !== "ArrowUp" && event.key !== "ArrowDown" && event.key !== "Home" && event.key !== "End") {
        return;
      }
      // Scope keys to the selector / browse controls, not the whole page.
      if (!target.closest(".az-work-wheel, .az-work-browse, .az-work-row")) return;
      event.preventDefault();
      if (event.key === "ArrowUp") setWorkIndex(workIndex - 1, true);
      else if (event.key === "ArrowDown") setWorkIndex(workIndex + 1, true);
      else if (event.key === "Home") setWorkIndex(0, true);
      else if (event.key === "End") setWorkIndex(getProjects().length - 1, true);
    });

    workBrowser.addEventListener(
      "touchstart",
      function (event) {
        if (!event.target.closest(".az-work-wheel")) return;
        if (!event.touches || !event.touches.length) return;
        touchActive = true;
        touchStartY = event.touches[0].clientY;
      },
      { passive: true }
    );

    workBrowser.addEventListener(
      "touchend",
      function (event) {
        if (!touchActive) return;
        touchActive = false;
        if (!event.changedTouches || !event.changedTouches.length) return;
        if (!event.target.closest(".az-work-wheel")) return;
        var dy = event.changedTouches[0].clientY - touchStartY;
        if (Math.abs(dy) < 28) return;
        setWorkIndex(workIndex + (dy < 0 ? 1 : -1), true);
      },
      { passive: true }
    );
  }

  function renderWorkGrid() {
    if (!workBrowser) return;
    if (workBrowser.dataset.ready !== "1") {
      buildWorkBrowser();
      bindWorkBrowser();
      workBrowser.dataset.ready = "1";
    }
    var restoreSlug = workLastSlug;
    if (restoreSlug) workIndex = indexForSlug(restoreSlug);
    updateWorkWheel();
    updateWorkSpotlight(false);
  }

  function renderProject(slug) {
    if (!projectRoot) return false;
    var project = window.AZ_getProjectBySlug && window.AZ_getProjectBySlug(slug);
    if (!project) {
      projectRoot.innerHTML =
        '<div class="az-panel">' +
        '<a class="az-project__back" href="#work">← Back to Work</a>' +
        '<h1 class="az-panel__title">Project not found</h1>' +
        '<p class="az-panel__lead">That project is unavailable. Return to the work grid to continue.</p>' +
        "</div>";
      return false;
    }
    var actions =
      '<div class="az-project__actions">' +
      (project.githubUrl
        ? '<a class="az-btn az-btn_primary" href="' +
          escapeHtml(project.githubUrl) +
          '" target="_blank" rel="noopener">GitHub</a>'
        : "") +
      (project.liveUrl
        ? '<a class="az-btn" href="' +
          escapeHtml(project.liveUrl) +
          '" target="_blank" rel="noopener">Live</a>'
        : "") +
      "</div>";
    var tech = (project.technologies || [])
      .map(function (item) {
        return "<li>" + escapeHtml(item) + "</li>";
      })
      .join("");
    projectRoot.innerHTML =
      '<article class="az-panel az-project">' +
      '<a class="az-project__back" href="#work">← Back to Work</a>' +
      '<p class="az-panel__eyebrow">' +
      escapeHtml(project.category) +
      " · " +
      escapeHtml(project.date) +
      "</p>" +
      '<h1 class="az-panel__title">' +
      escapeHtml(project.title) +
      "</h1>" +
      '<p class="az-panel__lead">' +
      escapeHtml(project.fullDescription) +
      "</p>" +
      '<div class="az-project__hero"><img src="' +
      escapeHtml(project.image) +
      '" alt="' +
      escapeHtml(project.title) +
      '" decoding="async"/></div>' +
      '<ul class="az-about__meta">' +
      "<li><strong>Role:</strong> " +
      escapeHtml(project.role) +
      "</li>" +
      "</ul>" +
      '<ul class="az-skills">' +
      tech +
      "</ul>" +
      actions +
      "</article>";
    return true;
  }

  function afterShow(route) {
    var isHome = route.name === "home";
    document.documentElement.dataset.azRoute = route.name === "project" ? "work" : route.name;
    document.documentElement.classList.toggle("az-route-home", isHome);
    // Work/project/about/contact: release Rhye smooth-scrollbar so the document scrolls natively.
    // Home: restore smooth-scrollbar + Swiper mousewheel/touch.
    setNativePageScroll(!isHome);
    setHomeSliderLive(isHome);
    window.scrollTo(0, 0);
    window.requestAnimationFrame(function () {
      applyPageScrollMode();
      if (!isHome) window.scrollTo(0, 0);
    });
  }

  function showView(route, instant) {
    var next = viewFor(route);
    var key = routeKey(route);
    if (!next) return;
    if (key === currentKey && !animating) {
      setDockActive(route);
      return;
    }

    // Rapid taps: jump to the latest route instead of queuing stale frames.
    if (animating) {
      pendingRoute = route;
      if (transitionTimer) {
        window.clearTimeout(transitionTimer);
        transitionTimer = 0;
      }
      animating = false;
      instant = true;
    }

    if (route.name === "work") renderWorkGrid();
    if (route.name === "project") {
      if (route.slug) workLastSlug = route.slug;
      renderProject(route.slug);
    }

    var prev = document.querySelector(".az-view.is-active");
    setDocumentTitle(route);
    setDockActive(route);

    function activate() {
      Object.keys(views).forEach(function (name) {
        var view = views[name];
        if (!view) return;
        var on = view === next;
        view.classList.toggle("is-active", on);
        view.classList.toggle("is-visible", on);
        view.classList.remove("is-leaving");
        view.hidden = !on;
        view.setAttribute("aria-hidden", on ? "false" : "true");
      });
      currentKey = key;
      afterShow(route);
      animating = false;
      transitionTimer = 0;
      var queued = pendingRoute;
      pendingRoute = null;
      if (queued && routeKey(queued) !== key) {
        showView(queued, true);
      }
    }

    if (instant || REDUCE || !prev || prev === next) {
      if (prev && prev !== next) {
        prev.classList.remove("is-leaving", "is-visible", "is-active");
        prev.hidden = true;
        prev.setAttribute("aria-hidden", "true");
      }
      activate();
      return;
    }

    animating = true;
    prev.classList.add("is-leaving");
    prev.classList.remove("is-visible");
    transitionTimer = window.setTimeout(function () {
      activate();
      if (!REDUCE) {
        next.classList.remove("is-visible");
        void next.offsetWidth;
        next.classList.add("is-visible");
      }
    }, 170);
  }

  function applyFromLocation(instant) {
    showView(normalizeHash(window.location.hash), instant);
  }

  function onNavClick(event) {
    var link = event.target.closest("a[data-nav], a[href^='#']");
    if (!link || link.hasAttribute("download")) return;
    var href = link.getAttribute("href") || "";
    if (href.indexOf("mailto:") === 0 || href.indexOf("http") === 0) return;
    if (href.charAt(0) !== "#") return;
    event.preventDefault();
    var route = normalizeHash(href);
    var nextHash = "#" + (route.name === "project" ? "work/" + route.slug : route.name === "home" ? "home" : route.name);
    if (window.location.hash === nextHash) {
      showView(route, false);
      return;
    }
    window.location.hash = nextHash;
  }

  document.addEventListener("click", onNavClick);
  window.addEventListener("hashchange", function () {
    applyFromLocation(false);
  });
  window.addEventListener("resize", function () {
    var route = normalizeHash(window.location.hash);
    setDockActive(route);
  });

  // Ensure an initial hash exists for shareable Home without forcing reload loops.
  if (!window.location.hash || window.location.hash === "#") {
    history.replaceState(null, "", "#home");
  }

  document.documentElement.classList.add("az-app-ready");
  applyFromLocation(true);
})();
