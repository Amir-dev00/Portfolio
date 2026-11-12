(function () {
  "use strict";

  var ROOT = "[data-az-assistant]";
  var state = {
    root: null,
    sending: false,
    open: false,
    messages: [],
    configured: true,
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
      return;
    }

    state.messages.push({ role: "assistant", content: "", pending: true });
    renderTranscript();

    state.sending = true;
    setBusy(true);

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
          return;
        }
        state.messages.push({ role: "assistant", content: String(res.data.reply || "") });
        renderTranscript();
      })
      .catch(function () {
        if (state.messages.length && state.messages[state.messages.length - 1].pending) {
          state.messages.pop();
        }
        state.messages.push({ role: "assistant", content: t("assistant.error.retry") });
        renderTranscript();
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
