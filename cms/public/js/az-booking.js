/* AZ booking — "Book a Call" dialog backed by a real scheduling provider.
   Config lives on the trigger: data-booking-url / -provider / -duration / -email / -admin.
   Supported embeds: Cal.com (incl. self-hosted with provider "calcom") and Calendly.
   Any other https URL opens in a new tab. Availability and confirmation always come
   from the provider; this file never renders times or claims a booking on its own. */
(function () {
  "use strict";

  var TRIGGER = "[data-az-booking-open]";
  var NS = "azBooking";
  var LOAD_TIMEOUT = 15000;
  var CAL_ORIGINS = {
    "cal.com": "https://app.cal.com",
    "www.cal.com": "https://app.cal.com",
    "app.cal.com": "https://app.cal.com",
    "cal.eu": "https://app.cal.eu",
    "app.cal.eu": "https://app.cal.eu",
  };
  var CALENDLY_SCRIPT = "https://assets.calendly.com/assets/external/widget.js";

  var ICONS = {
    close:
      '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
    back:
      '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>',
    clock:
      '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    globe:
      '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18"/></svg>',
    check:
      '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>',
    calendar:
      '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>',
  };

  var state = {
    root: null,
    dialog: null,
    stage: null,
    trigger: null,
    config: null,
    mountedKey: null,
    ready: false,
    readyAt: 0,
    interacted: false,
    booked: false,
    open: false,
    timeline: null,
    loadTimer: 0,
    stepTimer: 0,
    inerted: [],
    calendlyBound: false,
  };

  /* ---------- helpers ---------- */

  function t(key, vars) {
    var i18n = window.AZ_i18n;
    return i18n && typeof i18n.t === "function" ? i18n.t(key, vars) : key;
  }

  function reducedMotion() {
    return !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
  }

  function isCompact() {
    return !!(window.matchMedia && window.matchMedia("(max-width: 640px)").matches);
  }

  function isLocalHost() {
    var h = location.hostname;
    return location.protocol === "file:" || h === "localhost" || h === "127.0.0.1" || h === "[::1]";
  }

  function parseHttps(raw) {
    if (!raw) return null;
    try {
      var url = new URL(String(raw).trim());
      return url.protocol === "https:" ? url : null;
    } catch (err) {
      return null;
    }
  }

  function resolveProvider(url, preference) {
    if (!url) return null;
    var pref = (preference || "auto").toLowerCase();
    var host = url.hostname.toLowerCase();
    if (pref === "link") return "link";
    if (pref === "calcom" || CAL_ORIGINS[host]) return "calcom";
    if (pref === "calendly" || host === "calendly.com" || /\.calendly\.com$/.test(host)) return "calendly";
    return "link";
  }

  function readConfig(trigger) {
    var url = parseHttps(trigger.getAttribute("data-booking-url"));
    var duration = parseInt(trigger.getAttribute("data-booking-duration") || "", 10);
    return {
      url: url,
      provider: resolveProvider(url, trigger.getAttribute("data-booking-provider")),
      duration: duration > 0 ? duration : null,
      email: trigger.getAttribute("data-booking-email") || "",
      admin: trigger.getAttribute("data-booking-admin") === "1" || isLocalHost(),
    };
  }

  function timezoneLabel() {
    try {
      var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || "";
      var city = tz.split("/").pop().replace(/_/g, " ");
      var parts = new Intl.DateTimeFormat("en", { timeZoneName: "shortOffset" }).formatToParts(new Date());
      var offset = "";
      parts.forEach(function (p) {
        if (p.type === "timeZoneName") offset = p.value;
      });
      return city ? city + (offset ? " · " + offset : "") : offset;
    } catch (err) {
      return "";
    }
  }

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var existing = document.querySelector('script[data-az-booking-src="' + src + '"]');
      if (existing) {
        if (existing.dataset.loaded === "1") return resolve();
        existing.addEventListener("load", function () { resolve(); });
        existing.addEventListener("error", reject);
        return;
      }
      var s = document.createElement("script");
      s.src = src;
      s.async = true;
      s.setAttribute("data-az-booking-src", src);
      s.onload = function () {
        s.dataset.loaded = "1";
        resolve();
      };
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }

  function preconnect(origin) {
    if (!origin || document.querySelector('link[data-az-preconnect="' + origin + '"]')) return;
    var link = document.createElement("link");
    link.rel = "preconnect";
    link.href = origin;
    link.crossOrigin = "anonymous";
    link.setAttribute("data-az-preconnect", origin);
    document.head.appendChild(link);
  }

  function providerOrigin(config) {
    if (!config || !config.url) return null;
    if (config.provider === "calcom") return CAL_ORIGINS[config.url.hostname.toLowerCase()] || config.url.origin;
    if (config.provider === "calendly") return "https://calendly.com";
    return null;
  }

  /* ---------- dialog markup ---------- */

  function build() {
    if (state.root) return state.root;
    var root = document.createElement("div");
    root.className = "az-booking";
    root.id = "az-booking";
    root.hidden = true;
    root.innerHTML =
      '<div class="az-booking__shade" data-az-booking-dismiss></div>' +
      '<div class="az-booking__blur" data-az-booking-dismiss></div>' +
      '<div class="az-booking__dialog" role="dialog" aria-modal="true" aria-labelledby="az-booking-title" aria-describedby="az-booking-intro" tabindex="-1">' +
      '  <header class="az-booking__head">' +
      '    <div class="az-booking__bar">' +
      '      <button type="button" class="az-booking__icon-btn az-booking__back" hidden data-i18n-attr="aria-label:booking.back;title:booking.back">' + ICONS.back + "</button>" +
      '      <p class="az-booking__eyebrow"><span class="az-booking__eyebrow-icon">' + ICONS.calendar + '</span><span data-i18n="booking.eyebrow"></span></p>' +
      '      <button type="button" class="az-booking__icon-btn az-booking__close" data-i18n-attr="aria-label:booking.close;title:booking.close">' + ICONS.close + "</button>" +
      "    </div>" +
      '    <h2 class="az-booking__title" id="az-booking-title" data-i18n="booking.title"></h2>' +
      '    <p class="az-booking__intro" id="az-booking-intro" data-i18n="booking.intro"></p>' +
      '    <ul class="az-booking__meta">' +
      '      <li class="az-booking__chip az-booking__chip_duration" hidden>' + ICONS.clock + "<span></span></li>" +
      '      <li class="az-booking__chip az-booking__chip_tz">' + ICONS.globe + "<span></span></li>" +
      "    </ul>" +
      '    <p class="az-booking__status" role="status" aria-live="polite"><span class="az-booking__status-icon">' + ICONS.check + '</span><span class="az-booking__status-text"></span></p>' +
      "  </header>" +
      '  <div class="az-booking__body">' +
      '    <div class="az-booking__frame">' +
      '      <div class="az-booking__skeleton" aria-hidden="true"><div class="az-booking__skeleton-head"></div><div class="az-booking__skeleton-grid"></div></div>' +
      '      <p class="az-booking__loading" data-i18n="booking.loading"></p>' +
      '      <div class="az-booking__stage"></div>' +
      '      <div class="az-booking__notice" hidden></div>' +
      "    </div>" +
      "  </div>" +
      '  <footer class="az-booking__foot">' +
      '    <span data-i18n="booking.preferEmail"></span> ' +
      '    <a class="az-booking__mail az-ltr" href="#"></a>' +
      "  </footer>" +
      "</div>";

    var grid = root.querySelector(".az-booking__skeleton-grid");
    for (var i = 0; i < 35; i += 1) {
      grid.appendChild(document.createElement("span"));
    }

    document.body.appendChild(root);
    state.root = root;
    state.dialog = root.querySelector(".az-booking__dialog");
    state.stage = root.querySelector(".az-booking__stage");

    root.addEventListener("click", function (event) {
      if (event.target.closest("[data-az-booking-dismiss]") || event.target.closest(".az-booking__close")) {
        close();
      } else if (event.target.closest(".az-booking__back")) {
        resetScheduler();
      }
    });
    root.addEventListener("keydown", onKeydown);
    return root;
  }

  function translate() {
    if (!state.root) return;
    if (window.AZ_i18n && typeof window.AZ_i18n.apply === "function") window.AZ_i18n.apply(state.root);

    var cfg = state.config || {};
    var durationChip = state.root.querySelector(".az-booking__chip_duration");
    durationChip.hidden = !cfg.duration;
    if (cfg.duration) durationChip.querySelector("span").textContent = t("booking.duration", { min: cfg.duration });

    var tz = timezoneLabel();
    var tzChip = state.root.querySelector(".az-booking__chip_tz");
    tzChip.hidden = !tz;
    tzChip.querySelector("span").textContent = t("booking.timezone", { tz: tz });

    var mail = state.root.querySelector(".az-booking__mail");
    mail.textContent = cfg.email || "";
    mail.href = cfg.email ? "mailto:" + cfg.email : "#";
    state.root.querySelector(".az-booking__foot").hidden = !cfg.email;

    if (state.booked) setStatus(t("booking.confirmed"));
    var notice = state.root.querySelector(".az-booking__notice");
    if (!notice.hidden && notice.dataset.kind) renderNotice(notice.dataset.kind);
  }

  /* ---------- states ---------- */

  function setPhase(phase) {
    if (!state.root) return;
    state.root.setAttribute("data-phase", phase);
  }

  function setStatus(text) {
    var status = state.root.querySelector(".az-booking__status");
    status.querySelector(".az-booking__status-text").textContent = text || "";
    status.classList.toggle("is-visible", !!text);
  }

  function renderNotice(kind) {
    var notice = state.root.querySelector(".az-booking__notice");
    var cfg = state.config || {};
    var html = "";
    var mailBtn = cfg.email
      ? '<a class="az-contact-pill az-contact-pill_primary" href="mailto:' + encodeURI(cfg.email) + '"><span>' + escapeHtml(t("booking.emailInstead")) + '</span><span class="az-contact-pill__arrow" aria-hidden="true">↗</span></a>'
      : "";

    if (kind === "setup") {
      html =
        '<span class="az-booking__notice-icon">' + ICONS.calendar + "</span>" +
        '<h3 class="az-booking__notice-title">' + escapeHtml(t("booking.setup.title")) + "</h3>" +
        '<p class="az-booking__notice-body">' + escapeHtml(t("booking.setup.body")) + "</p>" +
        '<div class="az-booking__notice-actions">' + mailBtn + "</div>" +
        (cfg.admin ? '<p class="az-booking__admin-hint">' + escapeHtml(t("booking.setup.admin")) + "</p>" : "");
    } else {
      html =
        '<span class="az-booking__notice-icon">' + ICONS.calendar + "</span>" +
        '<h3 class="az-booking__notice-title">' + escapeHtml(t("booking.error.title")) + "</h3>" +
        '<p class="az-booking__notice-body">' + escapeHtml(t("booking.error.body")) + "</p>" +
        '<div class="az-booking__notice-actions">' +
        (cfg.url
          ? '<a class="az-contact-pill" href="' + escapeHtml(cfg.url.href) + '" target="_blank" rel="noopener noreferrer"><span>' + escapeHtml(t("booking.openExternal")) + '</span><span class="az-contact-pill__arrow" aria-hidden="true">↗</span></a>'
          : "") +
        mailBtn +
        "</div>";
    }
    notice.dataset.kind = kind;
    notice.innerHTML = html;
    notice.hidden = false;
  }

  function hideNotice() {
    var notice = state.root.querySelector(".az-booking__notice");
    notice.hidden = true;
    notice.dataset.kind = "";
    notice.innerHTML = "";
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }

  function markReady() {
    if (state.ready) return;
    state.ready = true;
    window.clearTimeout(state.loadTimer);
    hideNotice();
    setPhase("ready");
  }

  function markFailed() {
    window.clearTimeout(state.loadTimer);
    if (state.ready) return;
    state.mountedKey = null;
    setPhase("error");
    renderNotice("error");
  }

  function markStepChanged() {
    if (!state.ready || state.booked || !state.interacted) return;
    // Providers resize/route while settling right after load; only treat later changes as navigation.
    if (Date.now() - state.readyAt < 1200) return;
    state.root.querySelector(".az-booking__back").hidden = false;
  }

  function markBooked() {
    state.booked = true;
    state.root.querySelector(".az-booking__back").hidden = true;
    setStatus(t("booking.confirmed"));
    state.root.classList.add("is-booked");
    window.dispatchEvent(new CustomEvent("az:booking", { detail: { status: "booked" } }));
  }

  /* ---------- providers ---------- */

  function ensureCal(scriptSrc) {
    if (window.Cal && window.Cal.__azReady) return window.Cal;
    var cal = function () {
      var args = arguments;
      if (args[0] === "init" && typeof args[1] === "string") {
        var name = args[1];
        var api = cal.ns[name];
        if (!api) {
          api = function () {
            api.q.push(arguments);
          };
          api.q = [];
          cal.ns[name] = api;
        }
        api.q.push(args);
        cal.q.push(["initNamespace", name]);
        return;
      }
      cal.q.push(args);
    };
    cal.ns = {};
    cal.q = [];
    cal.loaded = true;
    cal.__azReady = true;
    window.Cal = cal;
    loadScript(scriptSrc).catch(markFailed);
    return cal;
  }

  function mountCal(host, config) {
    var origin = providerOrigin(config);
    var Cal = ensureCal(origin + "/embed/embed.js");
    if (!Cal.ns[NS]) {
      Cal("init", NS, { origin: origin });
      var api = Cal.ns[NS];
      api("ui", {
        theme: "dark",
        layout: "month_view",
        hideEventTypeDetails: false,
        cssVarsPerTheme: {
          dark: {
            "cal-brand": "#f4f1ea",
            "cal-brand-emphasis": "#ffffff",
            "cal-brand-text": "#111111",
          },
        },
      });
      api("on", { action: "linkReady", callback: function () { state.readyAt = Date.now(); markReady(); } });
      api("on", { action: "linkFailed", callback: markFailed });
      api("on", { action: "bookingSuccessfulV2", callback: markBooked });
      api("on", { action: "bookingSuccessful", callback: markBooked });
      api("on", { action: "__dimensionChanged", callback: markStepChanged });
    }

    var params = {};
    config.url.searchParams.forEach(function (value, key) {
      params[key] = value;
    });
    params.layout = "month_view";
    params.theme = "dark";

    Cal.ns[NS]("inline", {
      elementOrSelector: host,
      calLink: config.url.pathname.replace(/^\/+|\/+$/g, ""),
      config: params,
    });
  }

  function onCalendlyMessage(event) {
    if (event.origin !== "https://calendly.com" || !event.data || typeof event.data.event !== "string") return;
    var name = event.data.event;
    if (name === "calendly.event_type_viewed" || name === "calendly.profile_page_viewed") {
      state.readyAt = Date.now();
      markReady();
    } else if (name === "calendly.date_and_time_selected") {
      markReady();
      state.readyAt = 0;
      state.interacted = true;
      markStepChanged();
    } else if (name === "calendly.event_scheduled") {
      markBooked();
    }
  }

  function mountCalendly(host, config) {
    if (!state.calendlyBound) {
      window.addEventListener("message", onCalendlyMessage);
      state.calendlyBound = true;
    }
    var url = new URL(config.url.href);
    url.searchParams.set("hide_gdpr_banner", "1");
    url.searchParams.set("background_color", "141417");
    url.searchParams.set("text_color", "f4f1ea");
    url.searchParams.set("primary_color", "f4f1ea");
    loadScript(CALENDLY_SCRIPT)
      .then(function () {
        if (!window.Calendly || typeof window.Calendly.initInlineWidget !== "function") throw new Error("Calendly unavailable");
        window.Calendly.initInlineWidget({ url: url.href, parentElement: host, resize: true });
        // Calendly only posts "viewed" events for bookable pages; its own notices (e.g. no openings) just load.
        var frame = host.querySelector("iframe");
        if (frame) {
          frame.addEventListener("load", function () {
            if (state.stage.contains(frame)) {
              state.readyAt = Date.now();
              markReady();
            }
          });
        }
      })
      .catch(markFailed);
  }

  function mountScheduler() {
    var cfg = state.config;
    var key = cfg.provider + "|" + cfg.url.href;
    if (state.mountedKey === key && !state.booked) return;

    state.mountedKey = key;
    state.ready = false;
    state.booked = false;
    state.readyAt = 0;
    state.interacted = false;
    state.root.classList.remove("is-booked");
    state.root.querySelector(".az-booking__back").hidden = true;
    setStatus("");
    hideNotice();
    setPhase("loading");

    state.stage.innerHTML = "";
    var host = document.createElement("div");
    host.className = "az-booking__embed az-booking__embed_" + cfg.provider;
    state.stage.appendChild(host);

    window.clearTimeout(state.loadTimer);
    state.loadTimer = window.setTimeout(markFailed, LOAD_TIMEOUT);

    if (cfg.provider === "calcom") mountCal(host, cfg);
    else mountCalendly(host, cfg);
  }

  function resetScheduler() {
    state.mountedKey = null;
    mountScheduler();
    state.dialog.focus({ preventScroll: true });
  }

  /* ---------- open / close ---------- */

  function setInert(on) {
    if (on) {
      if (state.inerted.length) return;
      Array.prototype.forEach.call(document.body.children, function (el) {
        if (el === state.root || el.tagName === "SCRIPT" || el.hasAttribute("inert")) return;
        el.setAttribute("inert", "");
        el.setAttribute("aria-hidden", "true");
        state.inerted.push(el);
      });
    } else {
      state.inerted.forEach(function (el) {
        el.removeAttribute("inert");
        el.removeAttribute("aria-hidden");
      });
      state.inerted = [];
    }
  }

  function lockScroll(on) {
    var html = document.documentElement;
    if (on) {
      var gap = window.innerWidth - html.clientWidth;
      html.style.setProperty("--az-booking-gap", gap > 0 ? gap + "px" : "0px");
      html.classList.add("az-booking-open");
    } else {
      html.classList.remove("az-booking-open");
      html.style.removeProperty("--az-booking-gap");
    }
  }

  function buildTimeline() {
    var gsap = window.gsap;
    var root = state.root;
    var shade = root.querySelector(".az-booking__shade");
    var blur = root.querySelector(".az-booking__blur");
    var dialog = state.dialog;
    var pieces = root.querySelectorAll(
      ".az-booking__bar, .az-booking__title, .az-booking__intro, .az-booking__meta, .az-booking__frame, .az-booking__foot"
    );

    if (gsap) gsap.set([dialog].concat(Array.prototype.slice.call(pieces)), { clearProps: "transform,opacity" });

    if (!gsap || reducedMotion()) {
      var tl = gsap ? gsap.timeline({ paused: true }) : null;
      if (tl) {
        tl.fromTo([shade, blur], { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.18, ease: "none" }, 0)
          .fromTo(dialog, { opacity: 0 }, { opacity: 1, duration: 0.18, ease: "none" }, 0);
      }
      return tl;
    }

    // Dialog and content tween opacity, not autoAlpha: visibility:hidden would block focusing the close button on open.
    var compact = isCompact();
    var timeline = gsap.timeline({ paused: true, defaults: { overwrite: "auto" } });
    timeline
      .fromTo(shade, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.35, ease: "power2.out" }, 0)
      .fromTo(blur, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.5, ease: "power2.out" }, 0.08)
      .fromTo(
        dialog,
        compact ? { opacity: 0, yPercent: 12 } : { opacity: 0, y: 28, scale: 0.965 },
        compact
          ? { opacity: 1, yPercent: 0, duration: 0.6, ease: "expo.out" }
          : { opacity: 1, y: 0, scale: 1, duration: 0.7, ease: "expo.out" },
        0.16
      )
      .fromTo(
        pieces,
        { opacity: 0, y: 10 },
        { opacity: 1, y: 0, duration: 0.5, ease: "power3.out", stagger: 0.05 },
        0.3
      );
    return timeline;
  }

  function open(trigger) {
    var source = trigger || document.querySelector(TRIGGER);
    if (!source) return;
    var config = readConfig(source);

    if (config.url && config.provider === "link") {
      window.open(config.url.href, "_blank", "noopener,noreferrer");
      return;
    }

    build();
    if (state.open) return;
    state.open = true;
    state.trigger = source;
    state.config = config;
    translate();

    if (!config.url) {
      state.mountedKey = null;
      state.stage.innerHTML = "";
      setStatus("");
      setPhase("setup");
      renderNotice("setup");
      if (config.admin && window.console) {
        console.info("[AZ booking] No booking URL configured. Set it in Admin → Site content → Booking (or BOOKING_URL), then publish.");
      }
    } else {
      if (state.booked) state.mountedKey = null;
      mountScheduler();
    }

    state.root.hidden = false;
    lockScroll(true);
    setInert(true);
    source.setAttribute("aria-expanded", "true");

    if (state.timeline) state.timeline.kill();
    state.timeline = buildTimeline();
    if (state.timeline) {
      state.root.classList.remove("is-css");
      state.timeline.eventCallback("onReverseComplete", finishClose);
      state.timeline.timeScale(1).play(0);
    } else {
      state.root.classList.add("is-css");
      void state.root.offsetWidth;
      state.root.classList.add("is-open");
    }

    window.requestAnimationFrame(function () {
      var target = state.root.querySelector(".az-booking__close");
      (target || state.dialog).focus({ preventScroll: true });
    });
  }

  function close() {
    if (!state.open) return;
    state.open = false;
    if (state.trigger) state.trigger.setAttribute("aria-expanded", "false");
    if (state.timeline) {
      state.timeline.timeScale(reducedMotion() ? 1 : 2.2).reverse();
    } else {
      state.root.classList.remove("is-open");
      window.setTimeout(finishClose, 240);
    }
  }

  function finishClose() {
    if (state.open) return;
    state.root.hidden = true;
    setInert(false);
    lockScroll(false);
    if (state.trigger && document.contains(state.trigger)) {
      state.trigger.focus({ preventScroll: true });
    }
  }

  function focusables() {
    return Array.prototype.filter.call(
      state.dialog.querySelectorAll('button:not([disabled]), a[href], iframe, select, input, textarea, [tabindex]:not([tabindex="-1"])'),
      function (el) {
        return !el.hidden && el.offsetParent !== null;
      }
    );
  }

  function onKeydown(event) {
    if (!state.open) return;
    if (event.key === "Escape") {
      event.preventDefault();
      event.stopPropagation();
      close();
      return;
    }
    if (event.key !== "Tab") return;
    var items = focusables();
    if (!items.length) {
      event.preventDefault();
      state.dialog.focus();
      return;
    }
    var first = items[0];
    var last = items[items.length - 1];
    if (event.shiftKey && (document.activeElement === first || document.activeElement === state.dialog)) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  /* ---------- wiring ---------- */

  function warm(trigger) {
    var cfg = readConfig(trigger);
    preconnect(providerOrigin(cfg));
  }

  document.addEventListener("click", function (event) {
    var trigger = event.target.closest && event.target.closest(TRIGGER);
    if (!trigger) return;
    event.preventDefault();
    open(trigger);
  });

  ["pointerenter", "focusin", "touchstart"].forEach(function (type) {
    document.addEventListener(
      type,
      function (event) {
        var trigger = event.target && event.target.closest && event.target.closest(TRIGGER);
        if (trigger && !trigger.dataset.azWarm) {
          trigger.dataset.azWarm = "1";
          warm(trigger);
        }
      },
      { passive: true, capture: true }
    );
  });

  document.addEventListener("keydown", function (event) {
    if (state.open && event.key === "Escape") {
      event.preventDefault();
      close();
    }
  });

  window.addEventListener("blur", function () {
    window.setTimeout(function () {
      if (state.open && state.stage && state.stage.contains(document.activeElement)) state.interacted = true;
    }, 0);
  });

  window.addEventListener("az:langchange", translate);

  window.AZ_booking = {
    open: open,
    close: close,
  };
})();
