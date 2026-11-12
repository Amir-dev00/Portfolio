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
    monthToken: 0,
    slotsToken: 0,
    bootstrapPromise: null,
    bootstrapKey: "",
    prefetchBound: false,
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

  function resolveApi(trigger) {
    var el = trigger || document.querySelector(TRIGGER);
    return ((el && el.getAttribute("data-booking-api")) || state.api || "/api/booking").replace(/\/$/, "");
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

  function renderCalendarSkeleton(stage) {
    var weekdays = lang() === "fa"
      ? ["ش", "ی", "د", "س", "چ", "پ", "ج"]
      : ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
    var cells = "";
    var i;
    for (i = 0; i < 35; i++) {
      cells += '<span class="az-booking__day is-skeleton" aria-hidden="true"><span></span></span>';
    }
    stage.innerHTML =
      '<div class="az-booking__cal-nav">' +
      '  <span class="az-booking__icon-btn is-ghost" aria-hidden="true">' + ICONS.chevL + "</span>" +
      '  <h3 class="az-booking__month az-booking__month--pulse" data-i18n="booking.loading"></h3>' +
      '  <span class="az-booking__icon-btn is-ghost" aria-hidden="true">' + ICONS.chevR + "</span>" +
      "</div>" +
      '<div class="az-booking__weekdays">' +
      weekdays.map(function (w) { return "<span>" + escapeHtml(w) + "</span>"; }).join("") +
      "</div>" +
      '<div class="az-booking__grid" aria-busy="true">' + cells + "</div>";
    applyI18n(stage);
  }

  function renderCalendar(stage) {
    var data = state.monthData;
    if (!data) {
      renderCalendarSkeleton(stage);
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
        var token = ++state.slotsToken;
        var stageEl = state.root.querySelector(".az-booking__stage");
        stageEl.innerHTML = '<p class="az-booking__loading" data-i18n="booking.loading"></p>';
        applyI18n(stageEl);
        loadSlots().then(function () {
          if (token !== state.slotsToken || !state.open) return;
          setStep("time");
        });
      });
    });
  }

  function formatSelectedSummary() {
    var dateLabel = state.selectedDate || "";
    var timeLabel = state.selectedSlot ? state.selectedSlot.label : "";
    if (dateLabel && window.AZ_jalali && lang() === "fa") {
      var parts = dateLabel.split("-");
      if (parts.length === 3) {
        var j = window.AZ_jalali.toJalali(+parts[0], +parts[1], +parts[2]);
        var month = window.AZ_jalali.monthName ? window.AZ_jalali.monthName(j[1], "fa") : String(j[1]);
        dateLabel = digits(String(j[2])) + " " + month + " " + digits(String(j[0]));
      }
    } else if (dateLabel) {
      try {
        var d = new Date(dateLabel + "T12:00:00");
        dateLabel = d.toLocaleDateString(lang() === "fa" ? "fa-IR" : "en-US", {
          weekday: "short",
          month: "short",
          day: "numeric",
          year: "numeric",
        });
      } catch (e) {}
    }
    if (!timeLabel) return dateLabel;
    return dateLabel + " · " + digits(timeLabel);
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
      '<p class="az-booking__picked">' + escapeHtml(formatSelectedSummary()) + "</p>" + list;
    applyI18n(stage);
    stage.querySelectorAll(".az-booking__slot").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var utc = btn.getAttribute("data-start");
        state.selectedSlot = state.slots.find(function (s) { return s.start_utc === utc; }) || null;
        if (state.selectedSlot) setStep("details");
      });
    });
  }

  function renderForm(stage) {
    stage.innerHTML =
      '<p class="az-booking__picked" aria-live="polite">' + escapeHtml(formatSelectedSummary()) + "</p>" +
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
    var nameInput = stage.querySelector('input[name="name"]');
    if (nameInput) {
      window.requestAnimationFrame(function () {
        try {
          nameInput.focus({ preventScroll: true });
        } catch (e) {
          nameInput.focus();
        }
      });
    }
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

  function showBootstrapError(stage, trigger, err) {
    var key = err && err.code === "disabled" ? "booking.disabled" : "booking.error.body";
    stage.innerHTML =
      '<div class="az-booking__notice"><h3 data-i18n="booking.setup.title"></h3><p data-i18n="' +
      key +
      '"></p>' +
      (trigger && trigger.getAttribute("data-booking-email")
        ? '<a class="az-contact-pill az-contact-pill_primary" href="mailto:' +
          escapeAttr(trigger.getAttribute("data-booking-email")) +
          '"><span data-i18n="booking.emailInstead"></span></a>'
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
    var token = ++state.monthToken;
    var stage = state.root.querySelector(".az-booking__stage");
    renderCalendarSkeleton(stage);
    loadMonth().then(function () {
      if (token !== state.monthToken || !state.open) return;
      setStep("date");
    }).catch(function (err) {
      if (token !== state.monthToken || !state.open) return;
      showBootstrapError(stage, state.trigger, err);
    });
  }

  function applyBootstrapPayload(data) {
    if (!data || !data.config) throw new Error("Invalid bootstrap");
    state.config = data.config;
    state.calendar = data.config.calendar || (lang() === "fa" ? "jalali" : "gregorian");
    if (!data.config.enabled) {
      throw Object.assign(new Error("Booking is disabled"), { code: "disabled" });
    }
    if (data.month) {
      state.monthData = data.month;
      state.year = data.month.year;
      state.month = data.month.month;
      state.calendar = data.month.calendar || state.calendar;
    }
  }

  function fetchBootstrap(force) {
    state.api = resolveApi(state.trigger);
    var key = state.api + "|" + lang();
    if (!force && state.bootstrapPromise && state.bootstrapKey === key) {
      return state.bootstrapPromise;
    }
    state.bootstrapKey = key;
    state.bootstrapPromise = api("/bootstrap?locale=" + encodeURIComponent(lang()))
      .then(function (data) {
        return data;
      })
      .catch(function (err) {
        if (state.bootstrapKey === key) state.bootstrapPromise = null;
        throw err;
      });
    return state.bootstrapPromise;
  }

  function loadMonth() {
    var token = state.monthToken;
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
      if (token !== state.monthToken) return;
      state.monthData = data;
      state.year = data.year;
      state.month = data.month;
    });
  }

  function loadSlots() {
    var token = state.slotsToken;
    return api("/slots?date=" + encodeURIComponent(state.selectedDate) + "&tz=" + encodeURIComponent(visitorTz())).then(function (data) {
      if (token !== state.slotsToken) return;
      state.slots = data.slots || [];
      state.selectedSlot = null;
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
      .to(shade, { opacity: 1, duration: 0.22, ease: "power2.out" }, 0)
      .to(blur, { opacity: 1, duration: 0.26, ease: "power2.out" }, 0.02)
      .to(dialog, { opacity: 1, y: 0, scale: 1, duration: 0.28, ease: "power3.out" }, 0.04);
    state.root.classList.add("is-open");
  }

  function animateStage(stage) {
    if (reducedMotion() || !window.gsap) return;
    window.gsap.fromTo(stage, { opacity: 0, y: 8 }, { opacity: 1, y: 0, duration: 0.2, ease: "power2.out" });
  }

  function open(trigger) {
    if (state.open) return;
    ensureRoot();
    state.api = resolveApi(trigger);
    state.open = true;
    state.step = "date";
    state.selectedDate = null;
    state.selectedSlot = null;
    state.confirmed = null;
    state.monthToken += 1;
    var openToken = state.monthToken;
    trigger.setAttribute("aria-expanded", "true");
    state.trigger = trigger;
    lockPage(true);
    renderAside(trigger);
    renderSteps();
    var stage = state.root.querySelector(".az-booking__stage");
    renderCalendarSkeleton(stage);
    animateIn();
    document.addEventListener("keydown", onKey);
    if (window.AZ_i18n && window.AZ_i18n.apply) window.AZ_i18n.apply(state.root);

    fetchBootstrap(false)
      .then(function (data) {
        if (!state.open || openToken !== state.monthToken) return;
        applyBootstrapPayload(data);
        renderAside(trigger);
        setStep("date");
      })
      .catch(function (err) {
        if (!state.open || openToken !== state.monthToken) return;
        showBootstrapError(stage, trigger, err);
      });
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
      duration: 0.2,
      ease: "power2.in",
      onComplete: done,
    });
    window.gsap.to([state.root.querySelector(".az-booking__shade"), state.root.querySelector(".az-booking__blur")], {
      opacity: 0,
      duration: 0.2,
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

  function schedulePrefetch() {
    var run = function () {
      var trigger = document.querySelector(TRIGGER);
      if (!trigger) return;
      state.api = resolveApi(trigger);
      fetchBootstrap(false).catch(function () {});
    };
    var kick = function () {
      if (window.requestIdleCallback) window.requestIdleCallback(run, { timeout: 1800 });
      else window.setTimeout(run, 400);
    };
    var maybeKick = function () {
      var contact = document.getElementById("view-contact");
      var onContact =
        document.documentElement.getAttribute("data-az-route") === "contact" ||
        (contact && contact.classList.contains("is-active"));
      if (onContact) kick();
    };

    if (!state.prefetchBound) {
      state.prefetchBound = true;
      window.addEventListener("hashchange", maybeKick);
      window.addEventListener("popstate", maybeKick);
      if ("MutationObserver" in window) {
        var mo = new MutationObserver(maybeKick);
        mo.observe(document.documentElement, { attributes: true, attributeFilter: ["data-az-route"] });
      }
      var contact = document.getElementById("view-contact");
      if (contact && "IntersectionObserver" in window) {
        var io = new IntersectionObserver(
          function (entries) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting) kick();
            });
          },
          { root: null, threshold: 0.12 }
        );
        io.observe(contact);
      }
    }
    maybeKick();
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
    schedulePrefetch();
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", bind);
  else bind();

  window.addEventListener("az:langchange", function () {
    state.bootstrapPromise = null;
    state.bootstrapKey = "";
    if (state.open && state.step === "date") {
      var token = ++state.monthToken;
      var stage = state.root.querySelector(".az-booking__stage");
      renderCalendarSkeleton(stage);
      fetchBootstrap(true)
        .then(function (data) {
          if (!state.open || token !== state.monthToken) return;
          applyBootstrapPayload(data);
          renderAside(state.trigger);
          setStep("date");
        })
        .catch(function (err) {
          if (!state.open || token !== state.monthToken) return;
          showBootstrapError(stage, state.trigger, err);
        });
    } else {
      schedulePrefetch();
    }
  });

  window.AZ_booking = { open: open, close: close, bind: bind, prefetch: fetchBootstrap };
})();
