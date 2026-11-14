/* SPA router — Home / Work / About / Contact. Dock stays mounted.
   Laravel mode: clean path URLs (/, /work, /fa/work/{slug}, …) with no route fragments.
   Static mode: hash URLs (#home, #work, …) for hosts without path rewrites. */
(function () {
  "use strict";

  var REDUCE = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var app = document.getElementById("az-app");
  var nav = document.getElementById("glass-nav");
  if (!app) return;

  // The SPA owns navigation. Disable Rhye's Barba setup before window.load
  // so clean route URLs and browser history use the in-page view transition.
  if (window.theme && window.theme.ajax) window.theme.ajax.enabled = false;

  var boot = window.AZ_BOOTSTRAP || null;
  var serverRouted = !!(boot && boot.canonical);
  var assets = window.AZ_ASSETS || {};
  var featureLoads = new Map();
  var routeCache = new Map();
  var routeLoads = new Map();
  var requestedRoute = 0;
  var routeStatus = document.getElementById("az-route-status");
  var activeRouteData = null;
  var workProjects = [];
  var activeRoute = null;
  var rhyeRequested = false;

  var desktopHome = window.matchMedia("(width > 767px)");
  var connection = window.navigator.connection;
  var pendingHomeFeatures = false;

  function tt(key, vars) {
    if (window.AZ_i18n && typeof window.AZ_i18n.t === "function") {
      return window.AZ_i18n.t(key, vars);
    }
    return key;
  }

  function localizeProject(project) {
    if (!project || serverRouted) return project;
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
    if (raw === "/ask") return { name: "ask" };
    if (raw === "/contact") return { name: "contact" };
    var match = raw.match(/^\/work\/([^/]+)$/);
    if (match) {
      return { name: "project", slug: decodeURIComponent(match[1]) };
    }
    return null;
  }

  function workSectionHash() {
    var hash = window.location.hash;
    return hash === "#work-projects" || hash === "#work-experience" ? hash : "";
  }

  function isPortfolioHash(raw) {
    var hash = String(raw || "").replace(/^#/, "").replace(/^\/+/, "");
    if (!hash || hash === "selected-work" || hash === "/") return true;
    if (hash === "home" || hash === "work" || hash === "about" || hash === "contact" || hash === "ask" || hash === "chat") return true;
    return hash.indexOf("work/") === 0;
  }

  var views = {
    home: document.getElementById("view-home"),
    work: document.getElementById("view-work"),
    about: document.getElementById("view-about"),
    ask: document.getElementById("view-ask"),
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
    if (hash === "ask" || hash === "chat") return { name: "ask" };
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
    return views[route.name];
  }

  function setDocumentTitle(route) {
    if (serverRouted && activeRouteData) {
      var seo = activeRouteData.seo;
      var hreflang = activeRouteData.hreflang;
      var canonical = hreflang[currentLang()];
      document.title = seo.title;
      [
        ['link[rel="canonical"]', "href", canonical],
        ['link[hreflang="en"]', "href", hreflang.en],
        ['link[hreflang="fa"]', "href", hreflang.fa],
        ['link[hreflang="x-default"]', "href", hreflang.en],
        ['meta[name="description"]', "content", seo.description || ""],
        ['meta[name="robots"]', "content", seo.robots || "index,follow"],
        ['meta[property="og:title"]', "content", seo.title],
        ['meta[property="og:description"]', "content", seo.description || ""],
        ['meta[property="og:image"]', "content", activeRouteData.ogImage],
        ['meta[property="og:url"]', "content", canonical],
        ['meta[property="og:type"]', "content", route.name === "project" ? "article" : "website"],
        ['meta[property="og:locale"]', "content", currentLang() === "fa" ? "fa_IR" : "en_US"],
        ['meta[property="og:locale:alternate"]', "content", currentLang() === "fa" ? "en_US" : "fa_IR"],
      ].forEach(function (entry) {
        var node = document.querySelector(entry[0]);
        if (node) node.setAttribute(entry[1], entry[2]);
      });
      return;
    }
    if (route.name === "work") document.title = tt("title.work");
    else if (route.name === "about") document.title = tt("title.about");
    else if (route.name === "ask") document.title = tt("title.ask");
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
        if (!serverRouted && swiper.autoplay && typeof swiper.autoplay.start === "function") swiper.autoplay.start();
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
    if (serverRouted) {
      applyHomeSliderLive();
      return;
    }
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
    if (serverRouted || !pw || !window.Scrollbar || typeof window.Scrollbar.init !== "function") return null;
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
    if (serverRouted) return true;
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
    if (serverRouted) return;
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
    if (!target || navKey === "cv" || target.offsetParent === null) {
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

    // Neutralize magnet/press transforms and measure layout sizes (offset*),
    // so pressed icons or avatar landings cannot shift the active oval.
    var prevMagX = target.style.getPropertyValue("--mag-x");
    var prevMagY = target.style.getPropertyValue("--mag-y");
    var prevTransform = target.style.transform;
    target.style.setProperty("--mag-x", "0px");
    target.style.setProperty("--mag-y", "0px");
    target.style.transform = "none";

    var slotW = target.offsetWidth;
    var slotH = target.offsetHeight;
    var slotLeft = target.offsetLeft;
    var slotTop = target.offsetTop;
    var iconLeft = icon.offsetLeft;
    var iconTop = icon.offsetTop;
    var iconW = icon.offsetWidth;
    var iconH = icon.offsetHeight;
    var labelLeft = label ? label.offsetLeft : iconLeft;
    var labelTop = label ? label.offsetTop : iconTop;
    var labelW = label ? label.offsetWidth : iconW;
    var labelH = label ? label.offsetHeight : iconH;

    target.style.setProperty("--mag-x", prevMagX || "0px");
    target.style.setProperty("--mag-y", prevMagY || "0px");
    target.style.transform = prevTransform;

    var ovalW;
    var ovalH;
    var x;
    var y;

    if (isHome) {
      ovalW = Math.round(iconW);
      ovalH = Math.round(iconH);
      x = slotLeft + iconLeft + iconW / 2 - ovalW / 2;
      y = slotTop + iconTop + iconH / 2 - ovalH / 2;
    } else {
      var contentLeft = Math.min(iconLeft, labelLeft);
      var contentRight = Math.max(iconLeft + iconW, labelLeft + labelW);
      var contentTop = Math.min(iconTop, labelTop);
      var contentBottom = Math.max(iconTop + iconH, labelTop + labelH);
      var contentW = contentRight - contentLeft;
      var contentH = contentBottom - contentTop;
      var padX = 8;
      var padY = 6;
      var maxW = Math.max(24, slotW - 4);
      var maxH = Math.max(24, slotH - 2);
      ovalW = Math.min(maxW, Math.round(contentW + padX * 2));
      ovalH = Math.min(maxH, Math.round(contentH + padY * 2));
      // Center the oval on icon+label, then clamp inside the slot.
      x = slotLeft + contentLeft + contentW / 2 - ovalW / 2;
      y = slotTop + contentTop + contentH / 2 - ovalH / 2;
      x = Math.min(Math.max(slotLeft + 1, x), slotLeft + slotW - ovalW - 1);
      y = Math.min(Math.max(slotTop + 1, y), slotTop + slotH - ovalH - 1);
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
    var map = { home: "home", work: "work", about: "about", contact: "contact", ask: "ask" };
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
    return serverRouted ? workProjects : (window.AZ_PROJECTS || []).map(localizeProject);
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
    var raw = serverRouted ? activeRouteData.projects[0] : window.AZ_getProjectBySlug && window.AZ_getProjectBySlug(slug);
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

  var aboutSkillsObserver = null;

  function observeAboutSkills() {
    if (aboutSkillsObserver) {
      aboutSkillsObserver.disconnect();
      aboutSkillsObserver = null;
    }
    if (!activeRoute || activeRoute.name !== "about" ||
        document.documentElement.classList.contains("az-loading") ||
        typeof window.IntersectionObserver !== "function") return;

    var view = views.about;
    if (!view) return;
    var cards = view.querySelectorAll(".az-about-skills__card");
    if (!cards.length) return;

    aboutSkillsObserver = new window.IntersectionObserver(function (entries, observer) {
      if (observer !== aboutSkillsObserver) return;
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-revealed");
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.08 });
    cards.forEach(function (card) {
      card.classList.remove("is-revealed");
      aboutSkillsObserver.observe(card);
    });
  }

  window.addEventListener("az:preloader-end", function () {
    if (activeRoute && activeRoute.name === "about") observeAboutSkills();
  }, { once: true });

  function afterShow(route) {
    var isHome = route.name === "home";
    document.documentElement.dataset.azRoute = route.name === "project" ? "work" : route.name;
    document.documentElement.classList.toggle("az-route-home", isHome);
    // Work/project/about/contact: release Rhye smooth-scrollbar so the document scrolls natively.
    // Home: restore smooth-scrollbar + Swiper mousewheel/touch.
    setNativePageScroll(!isHome);
    setHomeSliderLive(isHome && (!serverRouted || desktopHome.matches));
    if (serverRouted && isHome) startHomeFeatures();
    if (window.AZ_HOME_ARROWS && window.AZ_HOME_ARROWS.sync) window.AZ_HOME_ARROWS.sync();
    if (!isHome && window.AZ_booking && route.name !== "contact") window.AZ_booking.close();
    window.scrollTo(0, 0);
    observeAboutSkills();
    if (route.name === "ask" && document.documentElement.classList.contains("az-app-ready")) {
      var title = document.getElementById("az-ask-title");
      if (title) title.focus({ preventScroll: true });
    }
    window.requestAnimationFrame(function () {
      applyPageScrollMode();
      if (!isHome) window.scrollTo(0, 0);
      var hash = route.name === "work" && workSectionHash();
      if (hash) {
        var section = document.getElementById(hash.slice(1));
        if (section) section.scrollIntoView();
      }
    });
  }

  function showView(route, instant) {
    var next = viewFor(route);
    var key = serverRouted ? pathForRoute(route) : routeKey(route);
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
      activeRoute = route;
      afterShow(route);
      animating = false;
      transitionTimer = 0;
      var queued = pendingRoute;
      pendingRoute = null;
      if (queued && routeKey(queued) !== key) {
        showView(queued, true);
      }
    }

    if (serverRouted || instant || REDUCE || !prev || prev === next) {
      if (prev && prev !== next) {
        prev.classList.remove("is-leaving", "is-visible", "is-active");
        prev.hidden = true;
        prev.setAttribute("aria-hidden", "true");
      }
      activate();
      if (serverRouted && !instant && !REDUCE && prev && prev !== next && next.animate) {
        next.animate([{ opacity: 0.65 }, { opacity: 1 }], { duration: 180, easing: "ease-out" });
      }
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

  function loadFeature(key, stylesheet) {
    if (featureLoads.has(key)) return featureLoads.get(key);
    var promise = new Promise(function (resolve, reject) {
      var element = document.createElement(stylesheet ? "link" : "script");
      element.onload = resolve;
      element.onerror = function () {
        element.remove();
        reject(new Error(key));
      };
      if (key === "assistant" && document.documentElement.dataset.azRoute === "home") {
        element.fetchPriority = "low";
      }
      if (stylesheet) {
        element.rel = "stylesheet";
        element.href = assets[key];
      } else {
        element.src = assets[key];
      }
      if (key === "vendorCss" || key === "rhyeCss") {
        element.setAttribute("data-az-theme-style", key);
        var nextStyles = key === "vendorCss" && document.querySelector('[data-az-theme-style="rhyeCss"]');
        document.head.insertBefore(element, nextStyles || document.querySelector("[data-az-site-styles]"));
      } else {
        document.head.appendChild(element);
      }
    }).catch(function (error) {
      featureLoads.delete(key);
      throw error;
    });
    featureLoads.set(key, promise);
    return promise;
  }

  function startDesktopSlider() {
    if (rhyeRequested || REDUCE || !desktopHome.matches || !activeRoute || activeRoute.name !== "home") return;
    if (document.documentElement.classList.contains("az-loading") || document.hidden) return;
    if (connection && (connection.saveData || /^(slow-2g|2g|3g)$/.test(connection.effectiveType))) return;

    rhyeRequested = true;
    Promise.all([
      loadFeature("vendorCss", true),
      loadFeature("rhyeCss", true),
      loadFeature("vendor"),
    ]).then(function () {
      if (!desktopHome.matches || activeRoute.name !== "home") {
        rhyeRequested = false;
        return;
      }
      return loadFeature("rhye").then(function () {
        if (!activeRoute || activeRoute.name !== "home" || !desktopHome.matches) {
          rhyeRequested = false;
          return;
        }
        window.AZ_startDesktopSlider();
      });
    }).catch(function () { rhyeRequested = false; });
  }

  function startHomeFeatures() {
    if (document.documentElement.classList.contains("az-loading")) {
      if (!pendingHomeFeatures) {
        pendingHomeFeatures = true;
        window.addEventListener("az:preloader-end", function () {
          pendingHomeFeatures = false;
          if (activeRoute && activeRoute.name === "home") startHomeFeatures();
        }, { once: true });
      }
      return;
    }
    ["profile", "tour", "arrows"].forEach(function (key) {
      if (key === "profile" && document.querySelector("script[data-az-home-profile]")) return;
      loadFeature(key).catch(function () {});
    });
    // Leave the first background and the welcome UI ahead of the optional renderer.
    window.requestAnimationFrame(startDesktopSlider);
  }

  function routeUrl(route, locale) {
    return pathForRoute(route, locale) + (boot.preview ? "?preview=1" : "");
  }

  function fetchRoute(url) {
    if (routeCache.has(url)) return Promise.resolve(routeCache.get(url));
    if (routeLoads.has(url)) return routeLoads.get(url);
    var controller = new AbortController();
    var deadline = window.setTimeout(function () { controller.abort(); }, 30000);
    var pending = fetch(url, {
      credentials: "same-origin",
      headers: { "Accept": "application/json", "X-AZ-Fragment": "1" },
      signal: controller.signal,
    }).then(function (response) {
      if (!response.ok) throw new Error("Route HTTP " + response.status);
      return response.json();
    }).finally(function () {
      window.clearTimeout(deadline);
      routeLoads.delete(url);
    });
    routeLoads.set(url, pending);
    return pending;
  }

  function reportRouteStatus(message, retry) {
    if (!routeStatus) return;
    routeStatus.hidden = !message;
    routeStatus.querySelector("span").textContent = message;
    var button = routeStatus.querySelector("button");
    button.hidden = !retry;
    button.textContent = currentLang() === "fa" ? "تلاش دوباره" : "Retry";
    button.onclick = retry || null;
  }

  function mountRoute(route, data, locale) {
    var existing = views[route.name];
    // Keep the mounted project browser, carousel and transcript across language switches.
    var experience = existing && route.name === "work" && existing.querySelector("#work-experience");
    var localizeExperience = experience && experience.lang !== locale;
    if (existing && route.name !== "project" && !localizeExperience) return;
    if (existing && route.name === "project" && existing.dataset.azSlug === route.slug) return;
    var fragment = document.createElement("template");
    fragment.innerHTML = data.html;
    var next = fragment.content.firstElementChild;
    if (!next || next.id !== "view-" + route.name || !next.classList.contains("az-view")) {
      throw new Error("Unexpected route view");
    }
    if (localizeExperience) {
      var translated = next.querySelector("#work-experience");
      if (translated) experience.replaceWith(translated);
      return;
    }
    next.hidden = true;
    next.classList.remove("is-active", "is-visible");
    next.setAttribute("aria-hidden", "true");
    if (route.name === "project") next.dataset.azSlug = route.slug;
    if (existing) existing.replaceWith(next);
    else app.appendChild(next);
    views[route.name] = next;
    if (route.name === "work") workBrowser = next.querySelector("#az-work-browser");
    if (route.name === "project") projectRoot = next.querySelector("#az-project-root");
  }

  function loadRoute(route, mode, locale) {
    var url = routeUrl(route, locale);
    var token = ++requestedRoute;
    var pendingStatus = window.setTimeout(function () {
      if (token === requestedRoute) reportRouteStatus(locale === "fa" ? "در حال بارگذاری…" : "Loading…");
    }, 180);
    reportRouteStatus("");
    if (nav) nav.setAttribute("aria-busy", "true");
    return fetchRoute(url).then(function (data) {
      if (token !== requestedRoute) return false;
      if (!data.seo || !data.hreflang || !Array.isArray(data.projects)) throw new Error("Incomplete route");
      return (route.name === "home" || route.name === "ask") && !window.AZ_assistant
        ? loadFeature("assistant").then(function () { return data; }) : data;
    }).then(function (data) {
      if (!data || token !== requestedRoute) return false;
      mountRoute(route, data, locale);
      routeCache.set(url, data);
      activeRouteData = data;
      if (locale !== currentLang() && window.AZ_i18n) window.AZ_i18n.setLang(locale, { resetScroll: true });
      if (mode && window.location.pathname + window.location.search !== url) {
        if (mode === "replace") {
          history.replaceState({ az: true }, "", url + (route.name === "work" ? workSectionHash() : ""));
        } else history.pushState({ az: true }, "", url);
      }
      if (route.name === "work" && workProjects !== data.projects) {
        window.clearTimeout(workFadeTimer);
        workProjects = data.projects;
        if (workBrowser) delete workBrowser.dataset.ready;
      }
      if (window.AZ_i18n) window.AZ_i18n.apply(views[route.name]);
      if (window.AZ_assistant) window.AZ_assistant.bind();
      showView(route, mode === "replace");
      if (route.name === "work" && !workBrowser.dataset.ready) renderWorkGrid();
      setDocumentTitle(route);
      return true;
    }).catch(function () {
      if (token === requestedRoute) {
        reportRouteStatus(locale === "fa" ? "صفحه باز نشد." : "Couldn’t open the page.", function () {
          loadRoute(route, mode, locale);
        });
      }
      return false;
    }).finally(function () {
      window.clearTimeout(pendingStatus);
      if (token === requestedRoute && nav) nav.removeAttribute("aria-busy");
    });
  }

  function localeFromPath(path) {
    return /^\/fa(?:\/|$)/.test(path) ? "fa" : "en";
  }

  function applyFromLocation(instant) {
    var route = resolveRouteFromLocation();
    if (!serverRouted) return showView(route, instant);
    return loadRoute(route, null, localeFromPath(window.location.pathname));
  }

  /** Rewrite dock / brand hrefs after language switches so middle-click stays correct. */
  function syncNavHrefs(lang) {
    var prefix = localePrefix(lang);
    var paths = {
      home: prefix || "/",
      work: prefix + "/work",
      about: prefix + "/about",
      ask: prefix + "/ask",
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
    var section = route.name === "work" ? workSectionHash() : "";
    var clean = pathForRoute(route, localeFromPath(window.location.pathname)) + query + section;
    var current = window.location.pathname + query + hash;
    if (current !== clean) {
      history.replaceState({ az: true }, "", clean);
    }
    return route;
  }

  function navigateClient(route, replace, locale) {
    if (!Object.prototype.hasOwnProperty.call(views, route.name)) return Promise.resolve(false);
    if (serverRouted) return loadRoute(route, replace ? "replace" : "push", locale || currentLang());
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
    var locale = href.charAt(0) === "#" ? currentLang() : localeFromPath(new URL(href, window.location.origin).pathname);
    navigateClient(route, false, locale);
  }

  document.addEventListener("click", onNavClick);
  window.addEventListener("az:ask", function () {
    navigateClient({ name: "ask" }, false);
  });
  function openDestination(detail) {
    detail = detail || {};
    var destination = String(detail.destination || "").toLowerCase();
    var url = String(detail.url || "");
    if (destination === "cv") {
      if (url) window.open(url, "_blank", "noopener,noreferrer");
      return Promise.resolve(!!url);
    }
    if (destination === "work" || destination === "about" || destination === "contact" || destination === "ask" || destination === "home") {
      return navigateClient({ name: destination }, false);
    }
    return Promise.resolve(false);
  }

  window.addEventListener("az:navigate", function (event) {
    openDestination(event.detail);
  });
  window.addEventListener("hashchange", function () {
    if (activeRoute && activeRoute.name === "work" && workSectionHash()) return;
    if (serverRouted) {
      canonicalizeLegacyUrl();
    }
    applyFromLocation(false);
  });
  window.addEventListener("popstate", function () {
    applyFromLocation(false);
  });
  window.addEventListener("resize", function () {
    if (activeRoute) setDockActive(activeRoute);
  });
  window.addEventListener("az:langchange", function () {
    if (serverRouted) syncNavHrefs(currentLang());
    else refreshLocalizedUi();
  });
  window.addEventListener("az:langrequest", function (event) {
    if (serverRouted) navigateClient(activeRoute || resolveRouteFromLocation(), true, event.detail.lang);
  });

  if (serverRouted) {
    var initial = parsePortfolioPath(window.location.pathname) || { name: "home" };
    activeRouteData = boot.routeData;
    if (initial.name === "work") workProjects = activeRouteData.projects;
    if (initial.name === "project" && views.project) views.project.dataset.azSlug = initial.slug;
    if (window.AZ_assistant) window.AZ_assistant.bind();
    else if (assets.assistant && (initial.name === "home" || initial.name === "ask")) {
      loadFeature("assistant").catch(function () {});
    }
    var initialView = views[initial.name];
    if (initialView) routeCache.set(routeUrl(initial, boot.lang), Object.assign({
      html: initialView.outerHTML,
    }, activeRouteData));
    syncNavHrefs(boot.lang);
    showView(initial, true);
    var clean = canonicalizeLegacyUrl();
    if (routeKey(clean) !== routeKey(initial)) applyFromLocation(true);

    document.addEventListener("visibilitychange", startDesktopSlider);
    if (connection && connection.addEventListener) connection.addEventListener("change", startDesktopSlider);
    desktopHome.addEventListener("change", function () {
      setHomeSliderLive(desktopHome.matches && activeRoute.name === "home");
      startDesktopSlider();
    });
    window.addEventListener("az:rhye-ready", function () {
      document.documentElement.classList.add("az-rhye-ready");
      applyHomeSliderLive();
      if (window.AZ_HOME_ARROWS && window.AZ_HOME_ARROWS.sync) window.AZ_HOME_ARROWS.sync();
    });
    document.addEventListener("click", function (event) {
      var trigger = event.target.closest("[data-az-booking-open]");
      if (!trigger) return;
      event.preventDefault();
      if (trigger.disabled) return;
      trigger.disabled = true;
      trigger.setAttribute("aria-busy", "true");
      Promise.all([
        loadFeature("bookingCss", true),
        loadFeature("jalali").then(function () { return loadFeature("booking"); }),
      ]).then(function () {
        if (activeRoute.name === "contact" && !trigger.closest(".az-view").hidden) window.AZ_booking.open(trigger);
      }).catch(function () {
        reportRouteStatus(currentLang() === "fa" ? "تقویم باز نشد." : "Couldn’t open the calendar.", function () {
          reportRouteStatus("");
          if (activeRoute.name === "contact") trigger.click();
        });
      }).finally(function () {
        trigger.disabled = false;
        trigger.removeAttribute("aria-busy");
      });
    });
  } else {
    if (!window.location.hash || window.location.hash === "#") {
      history.replaceState(null, "", hashForRoute(resolveRouteFromLocation()));
    }
    applyFromLocation(true);
  }

  window.AZ_nav = {
    go: function (name) {
      var routeName = String(name || "");
      if (!routeName) return;
      return navigateClient({ name: routeName }, false);
    },
    open: openDestination,
    current: function () {
      return activeRoute || resolveRouteFromLocation();
    },
    pathFor: pathForRoute,
  };
  document.documentElement.classList.add("az-app-ready");
  window.dispatchEvent(new Event("az:app-ready"));
})();
