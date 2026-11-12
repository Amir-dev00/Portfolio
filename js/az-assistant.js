(function () {
  "use strict";

  var ROOT = "[data-az-assistant]";
  var state = {
    root: null,
    sending: false,
    open: false,
    messages: [],
    configured: true,
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

  function qs(sel) {
    return state.root ? state.root.querySelector(sel) : null;
  }

  function react(pose, reply) {
    var avatar = qs("[data-az-assistant-avatar]");
    if (!avatar) return;

    window.clearTimeout(state.reactionTimer);
    if (state.navHint) state.navHint.classList.remove("az-assistant-nav-hint");
    state.navHint = null;

    // Highlight a known dock item only when the AI reply includes a local site path.
    var destination = reply && /(?:^|[\s(\[`])\/(?:fa\/)?(work|about|contact)(?=$|[\s/?)\]`.,!،؟])/i.exec(reply);
    if (destination) {
      state.navHint = document.querySelector('#glass-nav [data-nav="' + destination[1].toLowerCase() + '"]');
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

  /** Keep tech names / URLs readable inside RTL. */
  function formatText(text) {
    var safe = escapeHtml(text);
    return safe.replace(
      /(https?:\/\/[^\s<]+|[A-Za-z0-9][A-Za-z0-9+._/-]{1,}|\/[a-z0-9/_-]*)/g,
      function (m) {
        return '<span class="az-assistant__ltr">' + m + "</span>";
      }
    );
  }

  /** Under-composer status is unused; keep the node empty so idle UI has no reserved text. */
  function clearStatus() {
    var el = qs("[data-az-assistant-status]");
    if (!el) return;
    el.hidden = true;
    el.textContent = "";
    el.removeAttribute("data-tone");
  }

  function setOpen(open) {
    state.open = !!open;
    if (!state.root) return;
    state.root.classList.toggle("is-open", state.open);
    var reset = qs("[data-az-assistant-reset]");
    var collapse = qs("[data-az-assistant-collapse]");
    var transcript = qs("[data-az-assistant-transcript]");
    if (reset) reset.hidden = !state.open;
    if (collapse) collapse.hidden = !state.open;
    if (transcript) transcript.hidden = !state.open;
  }

  function renderTranscript() {
    var el = qs("[data-az-assistant-transcript]");
    if (!el) return;
    state.root.classList.toggle("has-messages", state.messages.length > 0);
    el.innerHTML = state.messages
      .map(function (m) {
        var cls = m.role === "user" ? "az-assistant__bubble--user" : "az-assistant__bubble--assistant";
        var body =
          m.pending
            ? '<span class="az-assistant__typing" aria-label="' +
              escapeHtml(t("assistant.thinking")) +
              '"><i></i><i></i><i></i></span>'
            : formatText(m.content);
        return '<div class="az-assistant__bubble ' + cls + '" data-role="' + m.role + '">' + body + "</div>";
      })
      .join("");
    el.scrollTop = el.scrollHeight;
  }

  function syncDirection() {
    if (!state.root) return;
    var l = lang();
    state.root.setAttribute("lang", l);
    state.root.setAttribute("dir", l === "fa" ? "rtl" : "ltr");
    applyI18n(state.root);
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
    if (!state.root) return;
    var nav = qs("[data-az-selected-work]");
    var linksHost = qs("[data-az-selected-work-links]");
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
    var input = qs("#az-assistant-input");
    if (input) input.value = "";

    setOpen(true);
    clearStatus();
    state.messages.push({ role: "user", content: message });

    if (!state.configured) {
      state.messages.push({ role: "assistant", content: t("assistant.error.config") });
      renderTranscript();
      react("error");
      return;
    }

    state.messages.push({ role: "assistant", content: "", pending: true });
    renderTranscript();

    state.sending = true;
    setBusy(true);
    react("thinking");

    postChat({ message: message, locale: lang() })
      .then(function (res) {
        // Drop pending bubble.
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
        var reply = String(res.data.reply || "");
        state.messages.push({ role: "assistant", content: reply });
        renderTranscript();
        react("reply", reply);
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
        state.sending = false;
        setBusy(false);
      });
  }

  function setBusy(busy) {
    var input = qs("#az-assistant-input");
    var sendBtn = qs("[data-az-assistant-send]");
    if (input) input.disabled = !!busy;
    if (sendBtn) sendBtn.disabled = !!busy;
  }

  function resetChat() {
    if (state.sending) return;
    react("idle");
    postChat({ reset: true, locale: lang() }).catch(function () {});
    state.messages = [];
    setOpen(false);
    clearStatus();
    renderTranscript();
    var input = qs("#az-assistant-input");
    if (input) {
      input.focus({ preventScroll: true });
    }
  }

  function onSubmit(e) {
    e.preventDefault();
    var input = qs("#az-assistant-input");
    send(input && input.value);
  }

  function bind() {
    state.root = document.querySelector(ROOT);
    if (!state.root) return;

    state.root.hidden = false;
    state.root.classList.add("is-ready");
    syncDirection();
    renderTranscript();

    var form = qs("[data-az-assistant-form]");
    if (form) form.addEventListener("submit", onSubmit);

    var reset = qs("[data-az-assistant-reset]");
    if (reset) reset.addEventListener("click", resetChat);

    var collapse = qs("[data-az-assistant-collapse]");
    if (collapse) {
      collapse.addEventListener("click", function () {
        setOpen(false);
      });
    }

    clearStatus();
    renderSelectedWork();

    // Soft status probe — store readiness only; never show setup text under the composer.
    fetch(apiBase() + "/status?locale=" + encodeURIComponent(lang()), {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        state.configured = !!(data && data.configured);
      })
      .catch(function () {});
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

  window.addEventListener("az:langchange", function () {
    syncDirection();
    // Keep transcript; re-render for LTR isolates / dir.
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
      setOpen(true);
    },
  };
})();
