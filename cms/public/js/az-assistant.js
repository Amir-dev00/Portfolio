(function () {
  "use strict";

  var ROOT = "[data-az-assistant]";
  var DESTINATIONS = ["work", "about", "contact", "cv"];
  var state = {
    root: null,
    sending: false,
    home: null,
    messages: [],
    reactionTimer: 0,
    navHint: null,
  };

  function lang() {
    if (window.AZ_i18n && typeof window.AZ_i18n.getLang === "function") {
      return window.AZ_i18n.getLang() === "fa" ? "fa" : "en";
    }
    return document.documentElement.lang === "fa" ? "fa" : "en";
  }

  function t(key, vars) {
    if (window.AZ_i18n && typeof window.AZ_i18n.t === "function") {
      return window.AZ_i18n.t(key, vars);
    }
    return key;
  }

  function applyI18n(scope) {
    if (window.AZ_i18n && typeof window.AZ_i18n.apply === "function") {
      window.AZ_i18n.apply(scope || state.root);
    }
  }

  function csrf() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return (window.AZ_CSRF_TOKEN || (meta && meta.getAttribute("content")) || "").trim();
  }

  function apiBase() {
    var el = state.root;
    return ((el && el.getAttribute("data-assistant-api")) || "/api/assistant").replace(/\/$/, "");
  }

  function react(pose, destination) {
    var avatar = qs("[data-az-assistant-avatar]");
    if (!avatar) return;

    window.clearTimeout(state.reactionTimer);
    if (state.navHint) state.navHint.classList.remove("az-assistant-nav-hint");
    state.navHint = null;

    var dest = String(destination || "").toLowerCase();
    if (dest === "work" || dest === "about" || dest === "contact" || dest === "cv") {
      state.navHint = document.querySelector('#glass-nav [data-nav="' + dest + '"]');
      if (state.navHint) {
        state.navHint.classList.add("az-assistant-nav-hint");
        pose = "point";
      }
    }

    avatar.setAttribute("data-az-assistant-avatar", pose);
    if (pose === "reply" || pose === "point" || pose === "error") {
      state.reactionTimer = window.setTimeout(function () { react("idle"); }, 1600);
    }
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  /** Keep tech names / URLs readable inside RTL. Links stay plain text, never navigation. */
  function formatText(text) {
    var safe = escapeHtml(text);
    return safe.replace(
      /(https?:\/\/[^\s<]+|[A-Za-z0-9][A-Za-z0-9+._/-]{1,}|\/[a-z0-9/_-]*)/g,
      function (m) {
        return '<span class="az-assistant__ltr">' + m + "</span>";
      }
    );
  }

  function actionLabel(message) {
    if (message && message.label) return String(message.label);
    var dest = String((message && message.destination) || "").toLowerCase();
    var key = "assistant.open." + dest;
    var labeled = t(key);
    if (labeled && labeled !== key) return labeled;
    return dest || t("assistant.open.page");
  }

  function showStatus(message) {
    var el = qs("[data-az-assistant-status]");
    if (!el) return;
    el.textContent = message;
    el.hidden = !message;
  }

  function renderTranscript() {
    var el = qs("[data-az-assistant-transcript]");
    if (!el) return;
    var reset = qs("[data-az-assistant-reset]");
    if (reset) reset.hidden = !state.messages.length;
    el.innerHTML = state.messages
      .map(function (m, index) {
        var cls = m.role === "user" ? "az-assistant__bubble--user" : "az-assistant__bubble--assistant";
        var body =
          m.pending
            ? '<span class="az-assistant__typing" aria-label="' +
              escapeHtml(t("assistant.thinking")) +
              '"><i></i><i></i><i></i></span>'
            : formatText(m.content);
        var action = "";
        if (!m.pending && m.deferred && m.action === "navigate" && m.destination && m.url) {
          action =
            '<button type="button" class="az-assistant__nav-action" data-az-nav-action="' +
            index +
            '">' +
            escapeHtml(t("assistant.open.action", { label: actionLabel(m) })) +
            "</button>";
        }
        return (
          '<div class="az-assistant__bubble ' +
          cls +
          '" data-role="' +
          m.role +
          '" dir="auto">' +
          body +
          action +
          "</div>"
        );
      })
      .join("");
    el.scrollTop = el.scrollHeight;
  }

  function flyTo(link) {
    if (!link || window.matchMedia("(prefers-reduced-motion: reduce)").matches) return Promise.resolve();
    var container = document.documentElement.dataset.azRoute === "home" ? state.home : state.root;
    var avatar = container && container.querySelector("[data-az-assistant-avatar]");
    if (!avatar || typeof avatar.animate !== "function") return Promise.resolve();

    var icon = link.querySelector(".glass-nav__icon, .glass-nav__action-icon") || link;
    var start = avatar.getBoundingClientRect();
    var end = icon.getBoundingClientRect();
    if (!start.width || !end.width) return Promise.resolve();

    var x = end.left + (end.width - start.width) / 2 - start.left;
    var y = end.top + (end.height - start.height) / 2 - start.top;
    var copy = avatar.cloneNode(true);
    copy.classList.add("az-assistant__avatar--flying");
    copy.setAttribute("data-az-assistant-avatar", "point");
    copy.style.left = start.left + "px";
    copy.style.top = start.top + "px";
    document.body.appendChild(copy);

    var animation;
    try {
      animation = copy.animate([
        { transform: "translate(0, 0) scale(1)" },
        { transform: "translate(" + x * 0.68 + "px," + (y * 0.60 - 14) + "px) scale(0.9)", offset: 0.6 },
        { transform: "translate(" + x + "px," + y + "px) scale(0.58)" },
      ], { duration: 300, easing: "cubic-bezier(0.22, 0.61, 0.36, 1)", fill: "forwards" });
    } catch (err) {
      copy.remove();
      return Promise.resolve();
    }

    document.documentElement.classList.add("az-avatar-in-flight");
    return animation.finished.then(function () { return true; }, function () { return false; }).finally(function () {
      copy.remove();
      document.documentElement.classList.remove("az-avatar-in-flight");
    });
  }

  function destinationLink(id) {
    return document.querySelector('#glass-nav a[data-nav="' + id + '"]');
  }

  function isDockTargetVisible(link) {
    if (!link || !link.isConnected) return false;
    var style = window.getComputedStyle(link);
    if (style.display === "none" || style.visibility === "hidden" || Number(style.opacity) === 0) {
      return false;
    }
    var box = link.getBoundingClientRect();
    return box.width > 0 && box.height > 0;
  }

  function resumeHref(value) {
    if (!value) return "";
    try {
      var url = new URL(value, window.location.href);
      return url.protocol === "http:" || url.protocol === "https:" ? url.href : "";
    } catch (err) {
      return "";
    }
  }

  function openDestination(detail, fromUrl) {
    var destination = detail.destination;
    if (DESTINATIONS.indexOf(destination) === -1 ||
        window.location.href !== fromUrl) return Promise.resolve(false);

    var link = destinationLink(destination);
    var url = detail.url;
    if (destination === "cv") {
      url = resumeHref(url) || resumeHref(link && link.getAttribute("href")) ||
        resumeHref(window.AZ_BOOTSTRAP && window.AZ_BOOTSTRAP.cvUrl);
      if (!url) return Promise.resolve(false);
    }

    // Flight only for visible Work / About / Contact dock targets.
    // Hidden CV (mobile) opens the published PDF directly.
    var canFly = destination !== "cv" && isDockTargetVisible(link);
    react("point", canFly ? destination : null);

    var flight = canFly ? flyTo(link) : Promise.resolve(false);
    return flight.then(function (landed) {
      if (window.location.href !== fromUrl) return false;
      if (landed && link) {
        link.classList.add("az-avatar-landing");
        window.setTimeout(function () { link.classList.remove("az-avatar-landing"); }, 320);
      }

      if (destination === "cv") window.location.assign(url);
      else if (window.AZ_nav && typeof window.AZ_nav.open === "function") {
        window.AZ_nav.open({ destination: destination, url: url });
      } else {
        window.dispatchEvent(new CustomEvent("az:navigate", { detail: { destination: destination, url: url } }));
      }
      return true;
    });
  }

  function applyDecision(data, fromUrl) {
    var action = String((data && data.action) || "answer").toLowerCase();
    var reply = String((data && data.reply) || "");
    var destination = data && data.destination ? String(data.destination).toLowerCase() : "";
    var url = data && data.url ? String(data.url) : "";
    var label = data && data.label ? String(data.label) : "";
    var mode = data && data.mode ? String(data.mode) : "";

    var message = {
      role: "assistant",
      content: reply,
      action: action,
      destination: destination || null,
      url: url || null,
      label: label || null,
      mode: mode || null,
    };

    if (action === "navigate" && DESTINATIONS.indexOf(destination) !== -1 && url &&
        (destination !== "cv" || resumeHref(url))) {
      state.messages.push(message);
      renderTranscript();
      return openDestination(message, fromUrl).then(function (opened) {
        if (opened) return;
        message.deferred = true;
        renderTranscript();
      });
    }

    state.messages.push({ role: "assistant", content: reply || t("assistant.error.retry") });
    renderTranscript();
    react("reply");
  }

  function syncDirection() {
    var l = lang();
    [state.root, state.home].forEach(function (root) {
      if (!root) return;
      root.setAttribute("lang", l);
      root.setAttribute("dir", l === "fa" ? "rtl" : "ltr");
      applyI18n(root);
    });
  }

  function projectHref(slug) {
    var prefix = lang() === "fa" ? "/fa" : "";
    var boot = window.AZ_BOOTSTRAP;
    if (boot && boot.canonical) {
      return prefix + "/work/" + encodeURIComponent(slug);
    }
    return "#work/" + encodeURIComponent(slug);
  }

  function projectTitle(project) {
    if (!project) return "";
    if (window.AZ_i18n && typeof window.AZ_i18n.localizeProject === "function") {
      var localized = window.AZ_i18n.localizeProject(project);
      if (localized && localized.title) return localized.title;
    }
    var pack = project.i18n && project.i18n[lang()];
    if (pack && pack.title) return pack.title;
    return project.title || "";
  }

  /** Prefer featured projects; otherwise the first two in Work sort order. */
  function pickSelectedProjects() {
    var all = (window.AZ_PROJECTS || []).filter(function (p) {
      return p && p.slug && p.title;
    });
    if (all.length < 2) return [];

    var featured = all.filter(function (p) {
      return !!p.is_featured;
    });
    if (featured.length >= 2) return featured.slice(0, 2);

    var slugs = (window.AZ_BOOTSTRAP && window.AZ_BOOTSTRAP.featured_slugs) || [];
    if (slugs.length) {
      var bySlug = {};
      all.forEach(function (p) {
        bySlug[p.slug] = p;
      });
      var ordered = slugs
        .map(function (slug) {
          return bySlug[slug];
        })
        .filter(Boolean);
      if (ordered.length >= 2) return ordered.slice(0, 2);
    }

    return all.slice(0, 2);
  }

  function renderSelectedWork() {
    if (!state.home) return;
    var nav = state.home.querySelector("[data-az-selected-work]");
    var linksHost = state.home.querySelector("[data-az-selected-work-links]");
    if (!nav || !linksHost) return;

    var picks = pickSelectedProjects();
    if (picks.length < 2) {
      nav.hidden = true;
      linksHost.innerHTML = "";
      return;
    }

    linksHost.innerHTML = picks
      .map(function (project) {
        var title = escapeHtml(projectTitle(project));
        var href = escapeHtml(projectHref(project.slug));
        return (
          '<a class="az-selected-work__link" href="' +
          href +
          '">' +
          '<span class="az-selected-work__name">' +
          title +
          "</span>" +
          '<svg class="az-selected-work__arrow" viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10"/></svg>' +
          "</a>"
        );
      })
      .join("");

    nav.hidden = false;
    applyI18n(nav);
  }

  function postChat(payload) {
    return fetch(apiBase() + "/chat", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": csrf(),
        "X-XSRF-TOKEN": csrf(),
      },
      body: JSON.stringify(payload),
    }).then(function (res) {
      return res.json().catch(function () {
        return {};
      }).then(function (data) {
        return { ok: res.ok, status: res.status, data: data };
      });
    });
  }

  function send(text) {
    var message = String(text || "").trim();
    if (!message || state.sending) return;
    if (/^(?:cv|resume|résumé|رزومه)[.!؟?]*$/i.test(message)) {
      var cv = destinationLink("cv");
      var pdf = resumeHref(cv && cv.getAttribute("href"));
      if (pdf) {
        setBusy(true);
        openDestination({ destination: "cv", url: pdf }, window.location.href)
          .finally(function () { setBusy(false); });
        return;
      }
    }

    window.dispatchEvent(new Event("az:ask"));
    var fromUrl = window.location.href;
    showStatus("");
    state.messages.push({ role: "user", content: message });

    state.messages.push({ role: "assistant", content: "", pending: true });
    renderTranscript();

    setBusy(true);
    react("thinking");

    postChat({ message: message, locale: lang() })
      .then(function (res) {
        if (state.messages.length && state.messages[state.messages.length - 1].pending) {
          state.messages.pop();
        }
        if (!res.ok || !res.data || !res.data.ok) {
          var err =
            (res.data && res.data.message) ||
            t("assistant.error.retry");
          state.messages.push({ role: "assistant", content: err });
          renderTranscript();
          react("error");
          return;
        }
        return applyDecision(res.data, fromUrl);
      })
      .catch(function () {
        if (state.messages.length && state.messages[state.messages.length - 1].pending) {
          state.messages.pop();
        }
        state.messages.push({ role: "assistant", content: t("assistant.error.retry") });
        renderTranscript();
        react("error");
      })
      .finally(function () {
        setBusy(false);
      });
  }

  function setBusy(busy) {
    state.sending = busy;
    [state.root, state.home].forEach(function (root) {
      if (!root) return;
      var button = root.querySelector("[data-az-assistant-send]");
      if (button) button.disabled = busy;
    });
    var reset = qs("[data-az-assistant-reset]");
    if (reset) reset.disabled = busy;
  }

  function resetChat() {
    if (state.sending) return;
    setBusy(true);
    showStatus("");
    postChat({ reset: true, locale: lang() })
      .then(function (res) {
        if (!res.ok || !res.data.ok) throw new Error("reset");
        state.messages = [];
        react("idle");
        renderTranscript();
        var input = qs("[data-az-assistant-input]");
        if (input) {
          input.value = "";
          input.focus({ preventScroll: true });
        }
      })
      .catch(function () {
        showStatus(t("assistant.error.retry"));
      })
      .finally(function () {
        setBusy(false);
      });
  }

  function onSubmit(e) {
    e.preventDefault();
    var input = e.currentTarget.querySelector("[data-az-assistant-input]");
    if (!input || !input.value.trim() || state.sending) return;
    var message = input.value;
    input.value = "";
    send(message);
  }

  function onTranscriptClick(e) {
    var button = e.target.closest("[data-az-nav-action]");
    if (!button || !state.root.contains(button)) return;
    var index = parseInt(button.getAttribute("data-az-nav-action"), 10);
    var message = state.messages[index];
    if (!message || message.action !== "navigate" || state.sending) return;
    setBusy(true);
    openDestination(message, window.location.href).finally(function () { setBusy(false); });
  }

  function qs(sel) {
    return state.root ? state.root.querySelector(sel) : null;
  }

  function bind() {
    state.root = document.querySelector(ROOT);
    if (!state.root) return;
    state.home = document.querySelector("[data-az-assistant-home]");
    if (state.home) state.home.hidden = false;
    syncDirection();
    renderTranscript();

    document.querySelectorAll("[data-az-assistant-form]").forEach(function (form) {
      form.addEventListener("submit", onSubmit);
    });
    var reset = qs("[data-az-assistant-reset]");
    if (reset) reset.addEventListener("click", resetChat);
    var transcript = qs("[data-az-assistant-transcript]");
    if (transcript) transcript.addEventListener("click", onTranscriptClick);
    setBusy(false);

    var viewport = window.visualViewport;
    if (viewport) {
      var resizeChat = function () {
        if (viewport.scale === 1) {
          state.root.style.setProperty("--az-ask-height", Math.floor(viewport.height) + "px");
        }
      };
      viewport.addEventListener("resize", resizeChat);
      resizeChat();
    }

    renderSelectedWork();
  }

  /** Warm remaining slider textures after first paint without blocking the first slide. */
  function deferHomeTextures() {
    if (window.matchMedia("(max-width: 767px)").matches) return;
    var nodes = document.querySelectorAll(".az-home-bg[data-az-defer-texture]");
    if (!nodes.length) return;
    var run = function () {
      Array.prototype.forEach.call(nodes, function (el) {
        var src = el.getAttribute("data-texture-src");
        if (!src) return;
        var img = new Image();
        img.decoding = "async";
        img.src = src;
      });
    };
    if ("requestIdleCallback" in window) {
      window.requestIdleCallback(run, { timeout: 2500 });
    } else {
      window.setTimeout(run, 900);
    }
  }

  document.addEventListener("az:langchange", function () {
    syncDirection();
    renderTranscript();
    renderSelectedWork();
  });

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      bind();
      deferHomeTextures();
    });
  } else {
    bind();
    deferHomeTextures();
  }

  window.AZ_assistant = {
    send: send,
    reset: resetChat,
    open: function () {
      window.dispatchEvent(new Event("az:ask"));
    },
  };
})();
