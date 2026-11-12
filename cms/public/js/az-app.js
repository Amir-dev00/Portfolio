/* SPA router — Home / Work / About / Contact. Dock stays mounted.
   Laravel mode: clean path URLs (/, /work, /fa/work/{slug}, …) with no route fragments.
   Static mode: hash URLs (#home, #work, …) for hosts without path rewrites. */
(function () {
  "use strict";

  var REDUCE = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var app = document.getElementById("az-app");
  var nav = document.getElementById("glass-nav");
  if (!app) return;

  var boot = window.AZ_BOOTSTRAP || null;
  var serverRouted = !!(boot && boot.canonical);

  function tt(key, vars) {
    if (window.AZ_i18n && typeof window.AZ_i18n.t === "function") {
      return window.AZ_i18n.t(key, vars);
    }
    return key;
  }

  function localizeProject(project) {
    if (!project) return project;
    if (window.AZ_i18n && typeof window.AZ_i18n.localizeProject === "function") {
      return window.AZ_i18n.localizeProject(project);
    }
    return project;
  }

  function currentLang() {
    if (window.AZ_i18n && typeof window.AZ_i18n.getLang === "function") {
      return window.AZ_i18n.getLang();
    }
    return (boot && boot.lang) || "en";
  }

  function localePrefix(lang) {
    return (lang || currentLang()) === "fa" ? "/fa" : "";
  }

  function pathForRoute(route, lang) {
    var prefix = localePrefix(lang);
    if (!route || !route.name || route.name === "home") return prefix || "/";
    if (route.name === "project") {
      return prefix + "/work/" + encodeURIComponent(route.slug || "");
    }
    return prefix + "/" + route.name;
  }

  function hashForRoute(route) {
    if (route.name === "project") return "#work/" + encodeURIComponent(route.slug || "");
    if (route.name === "home") return "#home";
    return "#" + route.name;
  }

  /** Parse a portfolio SPA path, or null when the URL is not one of our public pages. */
  function parsePortfolioPath(pathname) {
    var raw = String(pathname || "/");
    if (raw.length > 1) raw = raw.replace(/\/+$/, "");
    if (raw === "/fa") return { name: "home" };
    if (raw.indexOf("/fa/") === 0) {
      raw = raw.slice(3) || "/";
      if (raw.length > 1) raw = raw.replace(/\/+$/, "");
    }
    if (raw === "/" || raw === "") return { name: "home" };
    if (raw === "/work") return { name: "work" };
    if (raw === "/about") return { name: "about" };
    if (raw === "/contact") return { name: "contact" };
    var match = raw.match(/^\/work\/([^/]+)$/);
    if (match) {
      return { name: "project", slug: decodeURIComponent(match[1]) };
    }
    return null;
  }

  function normalizePathname(pathname) {
    return parsePortfolioPath(pathname) || { name: "home" };
  }

  function isPortfolioHash(raw) {
    var hash = String(raw || "").replace(/^#/, "").replace(/^\/+/, "");
    if (!hash || hash === "selected-work" || hash === "/") return true;
    if (hash === "home" || hash === "work" || hash === "about" || hash === "contact") return true;
    return hash.indexOf("work/") === 0;
  }

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
    if (route.name === "work") document.title = tt("title.work");
    else if (route.name === "about") document.title = tt("title.about");
    else if (route.name === "contact") document.title = tt("title.contact");
    else if (route.name === "project") {
      var project = window.AZ_getProjectBySlug && window.AZ_getProjectBySlug(route.slug);
      document.title = project
        ? tt("title.project", { name: project.title })
        : tt("title.base");
    } else document.title = tt("title.base");
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
      /* Fully wrap icon + label with a centered translucent blue pill. */
      var padX = Math.max(10, Math.round(contentW * 0.28));
      var padY = Math.max(8, Math.round(contentH * 0.22));
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
    return (window.AZ_PROJECTS || []).map(localizeProject);
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
    var href = serverRouted
      ? pathForRoute({ name: "project", slug: project.slug })
      : "#work/" + encodeURIComponent(project.slug);
    return (
      '<a class="az-work-spotlight__cover" href="' +
      href +
      '" aria-label="' +
      escapeHtml(tt("work.open", { name: project.title })) +
      '">' +
      '<img src="' +
      escapeHtml(project.image) +
      '" alt="" width="960" height="600" decoding="async"/>' +
      "</a>" +
      '<div class="az-work-spotlight__copy">' +
      '<p class="az-work-spotlight__eyebrow">' +
      escapeHtml(tt("work.spotlight.eyebrow")) +
      "</p>" +
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
      '">' +
      escapeHtml(tt("work.explore")) +
      ' <span aria-hidden="true">↗</span></a>' +
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
      escapeHtml(tt("work.count", { n: projects.length })) +
      "</span>" +
      '<div class="az-work-wheel-controls">' +
      '<button type="button" class="az-work-browse" data-dir="-1" aria-label="' +
      escapeHtml(tt("work.prev")) +
      '">' +
      '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 14.5L12 8.5l6 6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
      "</button>" +
      '<button type="button" class="az-work-browse" data-dir="1" aria-label="' +
      escapeHtml(tt("work.next")) +
      '">' +
      '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 9.5L12 15.5l6-6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
      "</button>" +
      "</div>" +
      "</div>" +
      '<div class="az-work-wheel" role="listbox" tabindex="0" aria-label="' +
      escapeHtml(tt("work.listbox")) +
      '">' +
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
    var wheelLockUntil = 0;
    var wheelAccum = 0;
    var WHEEL_STEP = 48;
    var WHEEL_COOLDOWN_MS = 320;

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
      "wheel",
      function (event) {
        // Only hijack wheel while the pointer is over the project list itself.
        if (!event.target.closest(".az-work-wheel")) return;

        var projects = getProjects();
        if (!projects.length) return;

        var dy = event.deltaY;
        if (!dy) return;
        // Prefer vertical intent; ignore mostly-horizontal trackpad pans.
        if (Math.abs(event.deltaX) > Math.abs(dy)) return;

        var goingNext = dy > 0;
        var atStart = workIndex <= 0;
        var atEnd = workIndex >= projects.length - 1;

        // Normalize line/page modes to pixel-ish units.
        var delta = dy;
        if (event.deltaMode === 1) delta *= 16;
        else if (event.deltaMode === 2) delta *= WHEEL_STEP;

        // First/last stop: hand scrolling back to the page. Stop leftover Rhye
        // smooth-scroll listeners from eating the gesture without moving.
        if ((goingNext && atEnd) || (!goingNext && atStart)) {
          wheelAccum = 0;
          event.preventDefault();
          event.stopPropagation();
          window.scrollBy(0, delta);
          return;
        }

        event.preventDefault();
        event.stopPropagation();

        var now = performance.now();
        if (now < wheelLockUntil) {
          wheelAccum = 0;
          return;
        }

        // Reverse direction mid-gesture: start a fresh accumulation.
        if (wheelAccum && (wheelAccum > 0) !== (delta > 0)) wheelAccum = 0;
        wheelAccum += delta;

        if (Math.abs(wheelAccum) < WHEEL_STEP) return;

        wheelAccum = 0;
        wheelLockUntil = now + WHEEL_COOLDOWN_MS;
        setWorkIndex(workIndex + (goingNext ? 1 : -1), true);
      },
      { passive: false }
    );

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
    var raw = window.AZ_getProjectBySlug && window.AZ_getProjectBySlug(slug);
    var project = localizeProject(raw);
    if (!project) {
      projectRoot.innerHTML =
        '<div class="az-panel">' +
        '<a class="az-project__back" href="' +
        (serverRouted ? pathForRoute({ name: "work" }) : "#work") +
        '">' +
        escapeHtml(tt("project.back")) +
        "</a>" +
        '<h1 class="az-panel__title">' +
        escapeHtml(tt("project.missing.title")) +
        "</h1>" +
        '<p class="az-panel__lead">' +
        escapeHtml(tt("project.missing.lead")) +
        "</p>" +
        "</div>";
      return false;
    }
    var actions =
      '<div class="az-project__actions">' +
      (project.githubUrl
        ? '<a class="az-btn az-btn_primary" href="' +
          escapeHtml(project.githubUrl) +
          '" target="_blank" rel="noopener">' +
          escapeHtml(tt("project.github")) +
          "</a>"
        : "") +
      (project.liveUrl
        ? '<a class="az-btn" href="' +
          escapeHtml(project.liveUrl) +
          '" target="_blank" rel="noopener">' +
          escapeHtml(tt("project.live")) +
          "</a>"
        : "") +
      "</div>";
    var tech = (project.technologies || [])
      .map(function (item) {
        return "<li>" + escapeHtml(item) + "</li>";
      })
      .join("");
    projectRoot.innerHTML =
      '<article class="az-panel az-project">' +
      '<a class="az-project__back" href="' +
      (serverRouted ? pathForRoute({ name: "work" }) : "#work") +
      '">' +
      escapeHtml(tt("project.back")) +
      "</a>" +
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
      "<li><strong>" +
      escapeHtml(tt("project.role")) +
      "</strong> " +
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

  function refreshLocalizedUi() {
    var route = resolveRouteFromLocation();
    setDocumentTitle(route);
    if (workBrowser && workBrowser.dataset.ready === "1") {
      var slug = workLastSlug;
      buildWorkBrowser();
      if (slug) workIndex = indexForSlug(slug);
      updateWorkWheel();
      updateWorkSpotlight(false);
    }
    if (route.name === "project") renderProject(route.slug);
    window.requestAnimationFrame(function () {
      setDockActive(route);
    });
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

  function resolveRouteFromLocation() {
    if (serverRouted) {
      // Pathname is authoritative; portfolio hashes are normalized away on boot.
      var pathRoute = parsePortfolioPath(window.location.pathname);
      if (pathRoute) return pathRoute;
      if (window.location.hash && isPortfolioHash(window.location.hash)) {
        return normalizeHash(window.location.hash);
      }
      return { name: "home" };
    }
    if (window.location.hash && window.location.hash !== "#") {
      return normalizeHash(window.location.hash);
    }
    return { name: "home" };
  }

  function applyFromLocation(instant) {
    showView(resolveRouteFromLocation(), instant);
  }

  /** Rewrite dock / brand hrefs after language switches so middle-click stays correct. */
  function syncNavHrefs(lang) {
    var prefix = localePrefix(lang);
    var paths = {
      home: prefix || "/",
      work: prefix + "/work",
      about: prefix + "/about",
      contact: prefix + "/contact",
    };
    document.querySelectorAll("a[data-nav]").forEach(function (link) {
      var key = link.getAttribute("data-nav");
      if (paths[key]) link.setAttribute("href", paths[key]);
    });
    document.querySelectorAll("a.az-brand-logo").forEach(function (link) {
      link.setAttribute("href", paths.home);
    });
  }

  /**
   * Convert legacy /#contact, /work#work, /fa#about into clean paths.
   * Keeps query params; drops portfolio route fragments only.
   */
  function canonicalizeLegacyUrl() {
    if (!serverRouted) return resolveRouteFromLocation();
    var query = window.location.search || "";
    var hash = window.location.hash || "";
    var route = null;
    if (hash && hash !== "#" && isPortfolioHash(hash)) {
      route = normalizeHash(hash);
    } else {
      route = parsePortfolioPath(window.location.pathname) || { name: "home" };
    }
    var clean = pathForRoute(route) + query;
    var currentClean = window.location.pathname + (window.location.search || "");
    if (currentClean !== clean || (hash && hash !== "#")) {
      history.replaceState({ az: true }, "", clean);
    }
    return route;
  }

  function navigateClient(route, replace) {
    if (serverRouted) {
      var nextUrl = pathForRoute(route);
      var current = window.location.pathname.replace(/\/+$/, "") || "/";
      var nextNorm = nextUrl.replace(/\/+$/, "") || "/";
      if (replace || current !== nextNorm || window.location.hash) {
        if (replace) history.replaceState({ az: true }, "", nextUrl);
        else if (current !== nextNorm || window.location.hash) {
          history.pushState({ az: true }, "", nextUrl);
        }
      }
      showView(route, false);
      return;
    }
    var nextHash = hashForRoute(route);
    if (window.location.hash === nextHash) {
      showView(route, false);
      return;
    }
    window.location.hash = nextHash;
  }

  function routeFromHref(href) {
    var raw = String(href || "").trim();
    if (!raw || raw.indexOf("mailto:") === 0 || raw.indexOf("tel:") === 0) return null;
    if (raw.charAt(0) === "#") {
      return isPortfolioHash(raw) ? normalizeHash(raw) : null;
    }
    try {
      var url = new URL(raw, window.location.origin);
      if (url.origin !== window.location.origin) return null;
      if (url.hash && isPortfolioHash(url.hash)) {
        // Legacy path+hash: /#contact, /work#about, /fa#work/slug
        return normalizeHash(url.hash);
      }
      return parsePortfolioPath(url.pathname);
    } catch (err) {
      return null;
    }
  }

  function onNavClick(event) {
    if (event.defaultPrevented) return;
    if (event.button != null && event.button !== 0) return;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    var link = event.target.closest("a[href]");
    if (!link || link.hasAttribute("download")) return;
    if (link.target && link.target !== "" && link.target !== "_self") return;
    var href = link.getAttribute("href") || "";
    var route = routeFromHref(href);
    if (!route) return;
    event.preventDefault();
    navigateClient(route, false);
  }

  document.addEventListener("click", onNavClick);
  window.addEventListener("hashchange", function () {
    if (serverRouted) {
      canonicalizeLegacyUrl();
    }
    applyFromLocation(false);
  });
  window.addEventListener("popstate", function () {
    if (serverRouted) {
      canonicalizeLegacyUrl();
      applyFromLocation(false);
      return;
    }
    applyFromLocation(false);
  });
  window.addEventListener("resize", function () {
    setDockActive(resolveRouteFromLocation());
  });
  window.addEventListener("az:langchange", function () {
    refreshLocalizedUi();
    if (serverRouted) {
      var route = resolveRouteFromLocation();
      history.replaceState({ az: true }, "", pathForRoute(route));
      syncNavHrefs(currentLang());
    }
  });

  if (serverRouted) {
    canonicalizeLegacyUrl();
    syncNavHrefs(currentLang());
  } else if (!window.location.hash || window.location.hash === "#") {
    history.replaceState(null, "", hashForRoute(resolveRouteFromLocation()));
  }

  applyFromLocation(true);
  document.documentElement.classList.add("az-app-ready");
})();
