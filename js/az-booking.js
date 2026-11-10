/* AZ booking — self-hosted Book a Call (Laravel API). No Cal.com / Calendly. */
(function () {
  "use strict";

  var TRIGGER = "[data-az-booking-open]";
  var state = {
    root: null,
    dialog: null,
    open: false,
    step: "date",
    api: "/api/booking",
    config: null,
    calendar: "gregorian",
    year: 0,
    month: 0,
    monthData: null,
    selectedDate: null,
    selectedSlot: null,
    slots: [],
    inerted: [],
    timeline: null,
  };

  var ICONS = {
    close: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
    back: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>',
    chevL: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>',
    chevR: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>',
    clock: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    check: '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
  };

  function t(key, vars) {
    var i18n = window.AZ_i18n;
    return i18n && typeof i18n.t === "function" ? i18n.t(key, vars) : key;
  }

  function lang() {
    return (window.AZ_i18n && window.AZ_i18n.getLang && window.AZ_i18n.getLang()) || document.documentElement.lang || "en";
  }

  function reducedMotion() {
    return !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
  }

  function csrf() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    var m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : "";
  }

  function visitorTz() {
    try {
      return Intl.DateTimeFormat().resolvedOptions().timeZone || "Asia/Tehran";
    } catch (e) {
      return "Asia/Tehran";
    }
  }

  function digits(v) {
    return window.AZ_jalali ? window.AZ_jalali.digits(v, lang()) : String(v);
  }

  function api(path, opts) {
    opts = opts || {};
    var headers = Object.assign({ Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }, opts.headers || {});
    if (opts.method && opts.method.toUpperCase() !== "GET") {
      headers["X-CSRF-TOKEN"] = csrf();
      headers["X-XSRF-TOKEN"] = csrf();
      if (!(opts.body instanceof FormData)) headers["Content-Type"] = "application/json";
    }
    return fetch(state.api + path, Object.assign({ credentials: "same-origin" }, opts, { headers: headers })).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (data) {
        if (!res.ok) {
          var err = new Error((data && data.message) || "Request failed");
          err.status = res.status;
          err.data = data;
          throw err;
        }
        return data;
      });
    });
  }

  function ensureRoot() {
    if (state.root) return state.root;
    var root = document.createElement("div");
    root.className = "az-booking";
    root.id = "az-booking";
    root.hidden = true;
    root.innerHTML =
      '<div class="az-booking__shade" data-az-booking-dismiss></div>' +
      '<div class="az-booking__blur" data-az-booking-dismiss></div>' +
      '<div class="az-booking__dialog" role="dialog" aria-modal="true" aria-labelledby="az-booking-title" tabindex="-1">' +
      '  <button type="button" class="az-booking__icon-btn az-booking__close" data-i18n-attr="aria-label:booking.close;title:booking.close">' + ICONS.close + "</button>" +
      '  <div class="az-booking__layout">' +
      '    <aside class="az-booking__aside"></aside>' +
      '    <div class="az-booking__main">' +
      '      <header class="az-booking__head">' +
      '        <button type="button" class="az-booking__icon-btn az-booking__back" hidden>' + ICONS.back + "</button>" +
      '        <div class="az-booking__steps" aria-hidden="true"></div>' +
      "      </header>" +
      '      <div class="az-booking__stage"></div>' +
      "    </div>" +
      "  </div>" +
      "</div>";
    document.body.appendChild(root);
    state.root = root;
    state.dialog = root.querySelector(".az-booking__dialog");
    root.addEventListener("click", function (e) {
      if (e.target.closest("[data-az-booking-dismiss]") || e.target.closest(".az-booking__close")) close();
      else if (e.target.closest(".az-booking__back")) goBack();
    });
    return root;
  }

  function renderAside(trigger) {
    var cfg = state.config || {};
    var photo = (trigger && trigger.getAttribute("data-booking-photo")) || (cfg.profile && cfg.profile.photo) || "assets/img/amir/profile.jpg";
    var name = (trigger && trigger.getAttribute("data-booking-name")) || (cfg.profile && cfg.profile.name) || "Amirhossein Zamanzade";
    var duration = (cfg.duration || (trigger && trigger.getAttribute("data-booking-duration")) || 30) + "";
    var aside = state.root.querySelector(".az-booking__aside");
    aside.innerHTML =
      '<img class="az-booking__avatar" src="' + escapeAttr(photo) + '" alt="" width="72" height="72"/>' +
      '<p class="az-booking__eyebrow" data-i18n="booking.eyebrow"></p>' +
      '<h2 class="az-booking__host">' + escapeHtml(name) + "</h2>" +
      '<p class="az-booking__role" data-i18n="booking.role"></p>' +
      '<h3 class="az-booking__title" id="az-booking-title" data-i18n="booking.title"></h3>' +
      '<p class="az-booking__intro" data-i18n="booking.intro"></p>' +
      '<ul class="az-booking__meta">' +
      '  <li class="az-booking__chip">' + ICONS.clock + "<span>" + escapeHtml(t("booking.duration", { min: duration })) + "</span></li>" +
      '  <li class="az-booking__chip"><span>' + escapeHtml(t("booking.timezone", { tz: visitorTz() })) + "</span></li>" +
      "</ul>";
    applyI18n(aside);
  }

  function renderSteps() {
    var steps = ["date", "time", "details", "done"];
    var labels = [t("booking.step.date"), t("booking.step.time"), t("booking.step.details"), t("booking.step.done")];
    var el = state.root.querySelector(".az-booking__steps");
    el.innerHTML = steps
      .map(function (s, i) {
        var cls = "az-booking__step";
        if (s === state.step) cls += " is-active";
        if (steps.indexOf(state.step) > i) cls += " is-done";
        return '<span class="' + cls + '">' + escapeHtml(labels[i]) + "</span>";
      })
      .join("");
    state.root.querySelector(".az-booking__back").hidden = state.step === "date" || state.step === "done";
  }

  function setStep(step) {
    state.step = step;
    renderSteps();
    var stage = state.root.querySelector(".az-booking__stage");
    if (step === "date") renderCalendar(stage);
    else if (step === "time") renderTimes(stage);
    else if (step === "details") renderForm(stage);
    else if (step === "done") renderDone(stage);
    animateStage(stage);
  }

  function goBack() {
    if (state.step === "time") {
      state.selectedSlot = null;
      setStep("date");
    } else if (state.step === "details") {
      setStep("time");
    }
  }

  function renderCalendar(stage) {
    var data = state.monthData;
    if (!data) {
      stage.innerHTML = '<p class="az-booking__loading" data-i18n="booking.loading"></p>';
      applyI18n(stage);
      return;
    }
    var startPad = ((data.days[0] && data.days[0].weekday) - (data.week_starts_on || 0) + 7) % 7;
    var cells = "";
    var i;
    for (i = 0; i < startPad; i++) cells += '<span class="az-booking__day is-empty"></span>';
    data.days.forEach(function (d) {
      var cls = "az-booking__day";
      if (!d.available) cls += " is-disabled";
      if (d.date === data.today) cls += " is-today";
      if (d.date === state.selectedDate) cls += " is-selected";
      cells +=
        '<button type="button" class="' +
        cls +
        '" data-date="' +
        d.date +
        '"' +
        (d.available ? "" : " disabled") +
        '><span>' +
        escapeHtml(d.label) +
        "</span></button>";
    });
    stage.innerHTML =
      '<div class="az-booking__cal-nav">' +
      '  <button type="button" class="az-booking__icon-btn" data-nav="-1" aria-label="Previous">' +
      ICONS.chevL +
      "</button>" +
      '  <h3 class="az-booking__month">' +
      escapeHtml(data.month_label) +
      "</h3>" +
      '  <button type="button" class="az-booking__icon-btn" data-nav="1" aria-label="Next">' +
      ICONS.chevR +
      "</button>" +
      "</div>" +
      '<div class="az-booking__weekdays">' +
      data.weekdays.map(function (w) { return "<span>" + escapeHtml(w) + "</span>"; }).join("") +
      "</div>" +
      '<div class="az-booking__grid">' + cells + "</div>";

    stage.querySelectorAll("[data-nav]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        shiftMonth(parseInt(btn.getAttribute("data-nav"), 10));
      });
    });
    stage.querySelectorAll(".az-booking__day[data-date]:not([disabled])").forEach(function (btn) {
      btn.addEventListener("click", function () {
        state.selectedDate = btn.getAttribute("data-date");
        loadSlots().then(function () { setStep("time"); });
      });
    });
  }

  function renderTimes(stage) {
    var list =
      state.slots.length === 0
        ? '<p class="az-booking__empty" data-i18n="booking.noSlots"></p>'
        : '<div class="az-booking__slots">' +
          state.slots
            .map(function (s) {
              var sel = state.selectedSlot && state.selectedSlot.start_utc === s.start_utc ? " is-selected" : "";
              return (
                '<button type="button" class="az-booking__slot' +
                sel +
                '" data-start="' +
                escapeAttr(s.start_utc) +
                '">' +
                escapeHtml(digits(s.label)) +
                "</button>"
              );
            })
            .join("") +
          "</div>";
    stage.innerHTML =
      '<p class="az-booking__picked">' + escapeHtml(state.selectedDate) + "</p>" + list +
      '<div class="az-booking__actions">' +
      '  <button type="button" class="az-contact-pill az-contact-pill_primary" data-continue ' +
      (state.selectedSlot ? "" : "disabled") +
      '><span data-i18n="booking.continue"></span></button>' +
      "</div>";
    applyI18n(stage);
    stage.querySelectorAll(".az-booking__slot").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var utc = btn.getAttribute("data-start");
        state.selectedSlot = state.slots.find(function (s) { return s.start_utc === utc; }) || null;
        renderTimes(stage);
      });
    });
    var cont = stage.querySelector("[data-continue]");
    if (cont) cont.addEventListener("click", function () { if (state.selectedSlot) setStep("details"); });
  }

  function renderForm(stage) {
    stage.innerHTML =
      '<form class="az-booking__form" novalidate>' +
      '  <input type="text" name="company_website" class="az-booking__hp" tabindex="-1" autocomplete="off" aria-hidden="true"/>' +
      '  <label><span data-i18n="booking.name"></span><input name="name" required autocomplete="name"/></label>' +
      '  <label><span data-i18n="booking.email"></span><input name="email" type="email" required autocomplete="email"/></label>' +
      '  <label><span data-i18n="booking.phone"></span><input name="phone" type="tel" autocomplete="tel"/></label>' +
      '  <label><span data-i18n="booking.topic"></span><input name="topic" maxlength="160"/></label>' +
      '  <label><span data-i18n="booking.message"></span><textarea name="message" rows="3" maxlength="2000"></textarea></label>' +
      '  <p class="az-booking__privacy" data-i18n="booking.privacy"></p>' +
      '  <p class="az-booking__form-error" hidden></p>' +
      '  <button type="submit" class="az-contact-pill az-contact-pill_primary"><span data-i18n="booking.confirm"></span></button>' +
      "</form>";
    applyI18n(stage);
    stage.querySelector("form").addEventListener("submit", onSubmit);
  }

  function renderDone(stage) {
    var b = state.confirmed || {};
    stage.innerHTML =
      '<div class="az-booking__success">' +
      '  <div class="az-booking__success-icon">' + ICONS.check + "</div>" +
      '  <h3 data-i18n="booking.step.done"></h3>' +
      '  <p data-i18n="booking.confirmed"></p>' +
      '  <ul class="az-booking__summary">' +
      "    <li><strong data-i18n=\"booking.reference\"></strong> <span>" + escapeHtml(b.reference || "") + "</span></li>" +
      "    <li><strong>" + escapeHtml(b.local_date || "") + "</strong> · " + escapeHtml(digits(b.local_time || "")) + "</li>" +
      "    <li>" + escapeHtml(t("booking.duration", { min: b.duration || "" })) + " · " + escapeHtml(b.timezone || "") + "</li>" +
      "  </ul>" +
      (b.ics_url
        ? '<a class="az-contact-pill" href="' + escapeAttr(b.ics_url) + '"><span data-i18n="booking.downloadIcs"></span></a>'
        : "") +
      "</div>";
    applyI18n(stage);
  }

  function onSubmit(event) {
    event.preventDefault();
    var form = event.currentTarget;
    var err = form.querySelector(".az-booking__form-error");
    err.hidden = true;
    var payload = {
      name: form.name.value.trim(),
      email: form.email.value.trim(),
      phone: form.phone.value.trim(),
      topic: form.topic.value.trim(),
      message: form.message.value.trim(),
      company_website: form.company_website.value,
      starts_at: state.selectedSlot.start_utc,
      timezone: visitorTz(),
    };
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    api("/", { method: "POST", body: JSON.stringify(payload) })
      .then(function (data) {
        state.confirmed = data.booking;
        setStep("done");
      })
      .catch(function (e) {
        var msg =
          (e.data && e.data.errors && (e.data.errors.starts_at || e.data.errors.booking || e.data.errors.email)) ||
          (e.data && e.data.message) ||
          t("booking.unavailable");
        if (Array.isArray(msg)) msg = msg[0];
        err.textContent = msg;
        err.hidden = false;
        btn.disabled = false;
        if (e.status === 422) loadSlots();
      });
  }

  function shiftMonth(delta) {
    var m = state.month + delta;
    var y = state.year;
    if (m < 1) {
      m = 12;
      y -= 1;
    }
    if (m > 12) {
      m = 1;
      y += 1;
    }
    state.year = y;
    state.month = m;
    loadMonth().then(function () { setStep("date"); });
  }

  function loadMonth() {
    var q =
      "?year=" +
      encodeURIComponent(state.year) +
      "&month=" +
      encodeURIComponent(state.month) +
      "&calendar=" +
      encodeURIComponent(state.calendar) +
      "&locale=" +
      encodeURIComponent(lang());
    return api("/month" + q).then(function (data) {
      state.monthData = data;
    });
  }

  function loadSlots() {
    return api("/slots?date=" + encodeURIComponent(state.selectedDate) + "&tz=" + encodeURIComponent(visitorTz())).then(function (data) {
      state.slots = data.slots || [];
      state.selectedSlot = null;
    });
  }

  function loadConfig(trigger) {
    return api("/config?locale=" + encodeURIComponent(lang())).then(function (cfg) {
      state.config = cfg;
      state.calendar = cfg.calendar || (lang() === "fa" ? "jalali" : "gregorian");
      var parts = window.AZ_jalali
        ? window.AZ_jalali.todayParts(state.calendar)
        : { year: new Date().getFullYear(), month: new Date().getMonth() + 1 };
      state.year = parts.year;
      state.month = parts.month;
      if (!cfg.enabled) {
        throw Object(code: "disabled");
      }
    });
  }

  function lockPage(on) {
    var html = document.documentElement;
    if (on) {
      var gap = window.innerWidth - document.documentElement.clientWidth;
      html.style.setProperty("--az-booking-gap", gap > 0 ? gap + "px" : "0px");
      html.classList.add("az-booking-open");
      state.inerted = [];
      Array.prototype.forEach.call(document.body.children, function (el) {
        if (el === state.root || el.id === "js-preloader") return;
        if (!el.hasAttribute("inert")) {
          el.setAttribute("inert", "");
          state.inerted.push(el);
        }
      });
    } else {
      html.classList.remove("az-booking-open");
      html.style.removeProperty("--az-booking-gap");
      state.inerted.forEach(function (el) { el.removeAttribute("inert"); });
      state.inerted = [];
    }
  }

  function animateIn() {
    if (reducedMotion() || !window.gsap) {
      state.root.hidden = false;
      state.root.classList.add("is-open");
      return;
    }
    state.root.hidden = false;
    var shade = state.root.querySelector(".az-booking__shade");
    var blur = state.root.querySelector(".az-booking__blur");
    var dialog = state.dialog;
    window.gsap.set([shade, blur], { opacity: 0 });
    window.gsap.set(dialog, { opacity: 0, y: 24, scale: 0.96 });
    state.timeline = window.gsap.timeline();
    state.timeline
      .to(shade, { opacity: 1, duration: 0.35, ease: "power2.out" }, 0)
      .to(blur, { opacity: 1, duration: 0.4, ease: "power2.out" }, 0.05)
      .to(dialog, { opacity: 1, y: 0, scale: 1, duration: 0.45, ease: "power3.out" }, 0.08);
    state.root.classList.add("is-open");
  }

  function animateStage(stage) {
    if (reducedMotion() || !window.gsap) return;
    window.gsap.fromTo(stage, { opacity: 0, y: 10 }, { opacity: 1, y: 0, duration: 0.28, ease: "power2.out" });
  }

  function open(trigger) {
    if (state.open) return;
    ensureRoot();
    state.api = (trigger.getAttribute("data-booking-api") || "/api/booking").replace(/\/$/, "");
    state.open = true;
    state.step = "date";
    state.selectedDate = null;
    state.selectedSlot = null;
    state.confirmed = null;
    trigger.setAttribute("aria-expanded", "true");
    state.trigger = trigger;
    lockPage(true);
    renderAside(trigger);
    renderSteps();
    var stage = state.root.querySelector(".az-booking__stage");
    stage.innerHTML = '<p class="az-booking__loading" data-i18n="booking.loading"></p>';
    applyI18n(stage);
    animateIn();
    loadConfig(trigger)
      .then(function () { return loadMonth(); })
      .then(function () { setStep("date"); })
      .catch(function (err) {
        var key = err && err.code === "disabled" ? "booking.disabled" : "booking.error.body";
        stage.innerHTML =
          '<div class="az-booking__notice"><h3 data-i18n="booking.setup.title"></h3><p data-i18n="' +
          key +
          '"></p>' +
          (trigger.getAttribute("data-booking-email")
            ? '<a class="az-contact-pill az-contact-pill_primary" href="mailto:' +
              escapeAttr(trigger.getAttribute("data-booking-email")) +
              '"><span data-i18n="booking.emailInstead"></span></a>'
            : "") +
          "</div>";
        applyI18n(stage);
      });
    document.addEventListener("keydown", onKey);
    if (window.AZ_i18n && window.AZ_i18n.apply) window.AZ_i18n.apply(state.root);
  }

  function close() {
    if (!state.open) return;
    state.open = false;
    document.removeEventListener("keydown", onKey);
    if (state.trigger) state.trigger.setAttribute("aria-expanded", "false");
    function done() {
      state.root.hidden = true;
      state.root.classList.remove("is-open");
      lockPage(false);
      if (state.trigger) state.trigger.focus();
    }
    if (reducedMotion() || !window.gsap) {
      done();
      return;
    }
    window.gsap.to(state.dialog, {
      opacity: 0,
      y: 16,
      scale: 0.97,
      duration: 0.25,
      ease: "power2.in",
      onComplete: done,
    });
    window.gsap.to([state.root.querySelector(".az-booking__shade"), state.root.querySelector(".az-booking__blur")], {
      opacity: 0,
      duration: 0.25,
    });
  }

  function onKey(e) {
    if (e.key === "Escape") close();
  }

  function applyI18n(scope) {
    if (window.AZ_i18n && window.AZ_i18n.apply) window.AZ_i18n.apply(scope);
    else {
      scope.querySelectorAll("[data-i18n]").forEach(function (el) {
        el.textContent = t(el.getAttribute("data-i18n"));
      });
    }
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c];
    });
  }
  function escapeAttr(s) {
    return escapeHtml(s);
  }

  function bind() {
    document.querySelectorAll(TRIGGER).forEach(function (btn) {
      if (btn.dataset.azBookingBound === "1") return;
      btn.dataset.azBookingBound = "1";
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        open(btn);
      });
    });
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", bind);
  else bind();

  window.addEventListener("az:langchange", function () {
    if (state.open && state.step === "date") {
      state.calendar = lang() === "fa" ? "jalali" : "gregorian";
      var parts = window.AZ_jalali ? window.AZ_jalali.todayParts(state.calendar) : { year: state.year, month: state.month };
      state.year = parts.year;
      state.month = parts.month;
      loadMonth().then(function () {
        renderAside(state.trigger);
        setStep("date");
      });
    }
  });

  window.AZ_booking = { open: open, close: close, bind: bind };
})();
