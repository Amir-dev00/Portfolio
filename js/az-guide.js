/* Homepage bilingual guided tour — invitation + 3 spotlight steps + astronaut. */
(function () {
  "use strict";

  var STEPS = [
    {
      selector: ".az-lang-switch",
      titleKey: "guide.step1.title",
      bodyKey: "guide.step1.body",
      pad: 10,
      radius: 999,
    },
    {
      selector: ".az-assistant__composer, #az-assistant-input",
      titleKey: "guide.step2.title",
      bodyKey: "guide.step2.body",
      pad: 12,
      radius: 28,
    },
    {
      selector: "#glass-nav .glass-nav__bar",
      titleKey: "guide.step3.title",
      bodyKey: "guide.step3.body",
      pad: 10,
      radius: 28,
    },
  ];

  var root = document.querySelector("[data-az-guide]");
  var launch = document.querySelector("[data-az-guide-launch]");
  if (!root || !launch) return;

  var scrim = root.querySelector("[data-az-guide-scrim]");
  var spot = root.querySelector("[data-az-guide-spotlight]");
  var canvas = root.querySelector("[data-az-guide-canvas]");
  var fallback = root.querySelector("[data-az-guide-fallback]");
  var invite = root.querySelector("[data-az-guide-invite]");
  var callout = root.querySelector("[data-az-guide-callout]");
  var stepEl = root.querySelector("[data-az-guide-step]");
  var titleEl = root.querySelector("[data-az-guide-title]");
  var bodyEl = root.querySelector("[data-az-guide-body]");
  var btnStart = root.querySelector("[data-az-guide-start]");
  var btnDecline = root.querySelector("[data-az-guide-decline]");
  var btnNext = root.querySelector("[data-az-guide-next]");
  var btnSkip = root.querySelector("[data-az-guide-skip]");

  var state = {
    open: false,
    touring: false,
    step: -1,
    viewer: null,
    viewerFailed: false,
    loadingViewer: null,
    lastFocused: null,
    activeTarget: null,
    mascotSize: 120,
  };

  var reducedMotion = false;
  try {
    reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  } catch (e) {}

  function t(key, vars) {
    if (window.AZ_i18n && typeof window.AZ_i18n.t === "function") {
      return window.AZ_i18n.t(key, vars);
    }
    return key;
  }

  function applyI18n(scope) {
    if (window.AZ_i18n && typeof window.AZ_i18n.apply === "function") {
      window.AZ_i18n.apply(scope || root);
    }
  }

  function isHome() {
    var route = document.documentElement.getAttribute("data-az-route") || "home";
    return route === "home";
  }

  function clamp(n, lo, hi) {
    return Math.max(lo, Math.min(hi, n));
  }

  function resolveTarget(step) {
    if (!step) return null;
    return document.querySelector(step.selector);
  }

  function clearTargetLive() {
    if (state.activeTarget) {
      state.activeTarget.classList.remove("az-guide-target-live");
      state.activeTarget = null;
    }
  }

  function setTargetLive(el) {
    clearTargetLive();
    if (!el) return;
    state.activeTarget = el;
    el.classList.add("az-guide-target-live");
  }

  function showEl(el, on) {
    if (!el) return;
    if (on) el.hidden = false;
    else el.hidden = true;
  }

  function setOpen(open) {
    state.open = !!open;
    root.hidden = !state.open;
    root.classList.toggle("is-open", state.open);
    root.classList.toggle("is-active", state.open);
    document.documentElement.classList.toggle("az-guide-open", state.open);
  }

  function setTouring(on) {
    state.touring = !!on;
    root.classList.toggle("is-touring", state.touring);
  }

  function mascotBox() {
    var node = canvas && !canvas.hidden ? canvas : fallback;
    return node;
  }

  function placeMascotNear(targetRect) {
    var size = state.mascotSize;
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var safeL = 8;
    var safeR = 8;
    var safeT = 8;
    var safeB = 12;
    try {
      // Approximate safe areas from CSS env if present via body padding — keep simple insets.
      safeB = Math.max(12, (window.visualViewport && window.visualViewport.height < vh ? 12 : 12));
    } catch (err) {}

    var preferLeft = targetRect.left + targetRect.width / 2 > vw * 0.55;
    var gap = 14;
    var x;
    var y;

    // Default: beside the target on the roomier side, vertically centered to the target.
    if (preferLeft) {
      x = targetRect.left - gap - size;
    } else {
      x = targetRect.right + gap;
    }
    y = targetRect.top + targetRect.height / 2 - size / 2;

    // Mobile / short targets: if stacked above/below, nudge sideways so the gesture still reads.
    var stacked =
      targetRect.top < size + 24 ||
      targetRect.bottom > vh - size - 24 ||
      Math.abs(targetRect.left + targetRect.width / 2 - vw / 2) < vw * 0.18;

    if (vw <= 767 || stacked) {
      // Prefer below-left or below-right of header targets; above dock for the nav.
      var isDockish = targetRect.bottom > vh * 0.72;
      if (isDockish) {
        y = targetRect.top - gap - size;
        x = preferLeft ? targetRect.left - size * 0.15 : targetRect.right - size * 0.85;
      } else if (targetRect.top < vh * 0.28) {
        y = targetRect.bottom + gap;
        x = preferLeft ? targetRect.left - size * 0.35 : targetRect.right - size * 0.65;
      }
    }

    x = clamp(x, safeL, vw - size - safeR);
    y = clamp(y, safeT, vh - size - safeB);

    var node = mascotBox();
    if (!node) return { x: x, y: y, cx: x + size / 2, cy: y + size / 2 };
    node.style.left = Math.round(x) + "px";
    node.style.top = Math.round(y) + "px";
    node.style.width = size + "px";
    node.style.height = size + "px";
    return { x: x, y: y, cx: x + size / 2, cy: y + size / 2 };
  }

  function placeInvite() {
    if (!invite) return;
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    invite.style.left = "50%";
    invite.style.top = "50%";
    invite.style.right = "auto";
    invite.style.bottom = "auto";
    invite.style.transform = "translate(-50%, -50%)";
    // Keep clear of dock on short phones.
    if (vh < 640) {
      invite.style.top = "42%";
    }
    void vw;
  }

  function placeCallout(targetRect, mascot) {
    if (!callout) return;
    callout.style.transform = "none";
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var cw = Math.min(300, vw - 28);
    var ch = callout.offsetHeight || 150;
    var gap = 12;
    var dockClear = 88;

    var candidates = [
      { left: targetRect.left, top: targetRect.bottom + gap },
      { left: targetRect.right - cw, top: targetRect.bottom + gap },
      { left: targetRect.left, top: targetRect.top - ch - gap },
      { left: mascot.cx - cw / 2, top: mascot.y + state.mascotSize + gap },
      { left: mascot.cx - cw / 2, top: mascot.y - ch - gap },
    ];

    var best = null;
    var bestScore = -1e9;
    candidates.forEach(function (c) {
      var left = clamp(c.left, 10, vw - cw - 10);
      var top = clamp(c.top, 10, vh - ch - dockClear);
      var overlapsTarget =
        left < targetRect.right + 6 &&
        left + cw > targetRect.left - 6 &&
        top < targetRect.bottom + 6 &&
        top + ch > targetRect.top - 6;
      var score = 0;
      if (!overlapsTarget) score += 40;
      if (top + ch < vh - dockClear) score += 20;
      if (top > 8) score += 5;
      // Prefer near mascot.
      score -= Math.abs(left + cw / 2 - mascot.cx) * 0.02;
      score -= Math.abs(top + ch / 2 - mascot.cy) * 0.02;
      if (score > bestScore) {
        bestScore = score;
        best = { left: left, top: top };
      }
    });

    if (!best) best = { left: 14, top: Math.max(14, vh * 0.2) };
    callout.style.left = Math.round(best.left) + "px";
    callout.style.top = Math.round(best.top) + "px";
    callout.style.right = "auto";
    callout.style.bottom = "auto";
  }

  function updateSpotlight(rect, radius) {
    if (!spot || !rect) return;
    spot.style.left = Math.round(rect.left) + "px";
    spot.style.top = Math.round(rect.top) + "px";
    spot.style.width = Math.round(rect.width) + "px";
    spot.style.height = Math.round(rect.height) + "px";
    spot.style.borderRadius = (radius || 14) + "px";
  }

  function paddedRect(el, pad) {
    var r = el.getBoundingClientRect();
    return {
      left: r.left - pad,
      top: r.top - pad,
      width: r.width + pad * 2,
      height: r.height + pad * 2,
      right: r.right + pad,
      bottom: r.bottom + pad,
    };
  }

  function choosePointClip(mascot, targetRect) {
    var targetCx = targetRect.left + targetRect.width / 2;
    // If the target is left of the astronaut, point left; otherwise point right.
    return targetCx < mascot.cx ? "point_left" : "point_right";
  }

  function syncMascotSize() {
    var vw = window.innerWidth;
    if (vw <= 320) state.mascotSize = 84;
    else if (vw <= 390) state.mascotSize = 96;
    else if (vw <= 767) state.mascotSize = 108;
    else state.mascotSize = 128;
  }

  function layoutStep() {
    if (!state.touring || state.step < 0) return;
    var conf = STEPS[state.step];
    var el = resolveTarget(conf);
    if (!el) return;
    var rect = paddedRect(el, conf.pad || 10);
    setTargetLive(el);
    updateSpotlight(rect, conf.radius || 14);
    var mascot = placeMascotNear(rect);
    placeCallout(rect, mascot);
    if (state.viewer) {
      state.viewer.resize(state.mascotSize);
    }
    return { rect: rect, mascot: mascot, conf: conf };
  }

  function fillCallout() {
    var conf = STEPS[state.step];
    if (!conf) return;
    if (stepEl) stepEl.textContent = t("guide.step", { n: state.step + 1, total: STEPS.length });
    if (titleEl) titleEl.textContent = t(conf.titleKey);
    if (bodyEl) bodyEl.textContent = t(conf.bodyKey);
    if (btnNext) {
      var last = state.step >= STEPS.length - 1;
      btnNext.textContent = t(last ? "guide.done" : "guide.next");
      btnNext.setAttribute("data-i18n", last ? "guide.done" : "guide.next");
    }
    applyI18n(callout);
  }

  function ensureViewer() {
    if (state.viewer || state.viewerFailed) return Promise.resolve(state.viewer);
    if (state.loadingViewer) return state.loadingViewer;
    if (reducedMotion) {
      state.viewerFailed = true;
      return Promise.resolve(null);
    }

    var viewerUrl = root.getAttribute("data-viewer-url");
    var modelUrl = root.getAttribute("data-model-url");
    if (!viewerUrl || !modelUrl || !canvas) {
      state.viewerFailed = true;
      return Promise.resolve(null);
    }

    state.loadingViewer = import(viewerUrl)
      .then(function (mod) {
        if (!mod || typeof mod.createGuideViewer !== "function") {
          throw new Error("viewer missing");
        }
        return mod.createGuideViewer(canvas, modelUrl);
      })
      .then(function (viewer) {
        state.loadingViewer = null;
        if (!viewer) {
          state.viewerFailed = true;
          return null;
        }
        state.viewer = viewer;
        showEl(canvas, true);
        showEl(fallback, false);
        viewer.resize(state.mascotSize);
        viewer.start();
        return viewer;
      })
      .catch(function () {
        state.loadingViewer = null;
        state.viewerFailed = true;
        showEl(canvas, false);
        showEl(fallback, true);
        return null;
      });

    return state.loadingViewer;
  }

  function useFallbackMascot() {
    showEl(canvas, false);
    showEl(fallback, true);
  }

  function playGesture(name) {
    if (!state.viewer || reducedMotion) return Promise.resolve();
    return state.viewer.playOnce(name).catch(function () {});
  }

  function openInvite() {
    if (!isHome()) return;
    state.lastFocused = document.activeElement;
    setOpen(true);
    setTouring(false);
    state.step = -1;
    clearTargetLive();
    showEl(invite, true);
    showEl(callout, false);
    if (spot) spot.style.opacity = "0";
    syncMascotSize();
    placeInvite();

    // Park mascot near the launcher / language cluster for the wave.
    var cluster = document.querySelector(".az-header-end") || launch;
    var r = paddedRect(cluster, 4);
    placeMascotNear(r);
    showEl(fallback, true);
    showEl(canvas, false);

    applyI18n(invite);
    if (btnStart) btnStart.focus({ preventScroll: true });

    ensureViewer().then(function (viewer) {
      if (!state.open) return;
      if (viewer) {
        showEl(canvas, true);
        showEl(fallback, false);
        viewer.resize(state.mascotSize);
        placeMascotNear(r);
        playGesture("wave");
      } else {
        useFallbackMascot();
        placeMascotNear(r);
      }
    });
  }

  function closeGuide() {
    setTouring(false);
    state.step = -1;
    clearTargetLive();
    showEl(invite, false);
    showEl(callout, false);
    setOpen(false);

    if (state.viewer) {
      try {
        state.viewer.stop();
      } catch (e) {}
    }

    var restore = state.lastFocused && document.contains(state.lastFocused) ? state.lastFocused : launch;
    state.lastFocused = null;
    window.setTimeout(function () {
      try {
        restore.focus({ preventScroll: true });
      } catch (err) {}
    }, 0);
  }

  function showStep(index) {
    state.step = index;
    setTouring(true);
    showEl(invite, false);
    showEl(callout, true);
    fillCallout();
    syncMascotSize();

    var laid = layoutStep();
    if (btnNext) btnNext.focus({ preventScroll: true });

    if (!laid) return;
    var clip = choosePointClip(laid.mascot, laid.rect);
    ensureViewer().then(function (viewer) {
      if (!state.open || !state.touring) return;
      if (viewer) {
        showEl(canvas, true);
        showEl(fallback, false);
        viewer.resize(state.mascotSize);
        layoutStep();
        playGesture(clip);
      } else {
        useFallbackMascot();
        layoutStep();
      }
    });
  }

  function nextStep() {
    if (state.step < 0) {
      showStep(0);
      return;
    }
    if (state.step >= STEPS.length - 1) {
      finishTour();
      return;
    }
    showStep(state.step + 1);
  }

  function finishTour() {
    var closed = false;
    var done = function () {
      if (closed) return;
      closed = true;
      closeGuide();
    };
    ensureViewer().then(function (viewer) {
      if (viewer && !reducedMotion) {
        playGesture("wink").then(done, done);
        window.setTimeout(done, 1800);
      } else {
        done();
      }
    });
  }

  function onKeydown(e) {
    if (!state.open) return;
    if (e.key === "Escape") {
      e.preventDefault();
      closeGuide();
      return;
    }
    if (!state.touring) return;
    if (e.key === "Enter" && document.activeElement === btnNext) return;
    if (e.key === "ArrowRight" || e.key === "ArrowLeft") {
      // Don't invert for RTL — Next is always forward through the tour.
      if (e.key === "ArrowRight") {
        e.preventDefault();
        nextStep();
      }
    }
  }

  function onResize() {
    if (!state.open) return;
    syncMascotSize();
    if (state.touring) layoutStep();
    else placeInvite();
    if (state.viewer) state.viewer.resize(state.mascotSize);
  }

  function onRouteWatch() {
    if (state.open && !isHome()) closeGuide();
    launch.hidden = !isHome();
  }

  launch.addEventListener("click", function () {
    if (!isHome()) return;
    if (state.open) closeGuide();
    else openInvite();
  });

  if (btnStart) {
    btnStart.addEventListener("click", function () {
      nextStep();
    });
  }
  if (btnDecline) {
    btnDecline.addEventListener("click", function () {
      closeGuide();
    });
  }
  if (btnSkip) {
    btnSkip.addEventListener("click", function () {
      closeGuide();
    });
  }
  if (btnNext) {
    btnNext.addEventListener("click", function () {
      nextStep();
    });
  }
  if (scrim) {
    scrim.addEventListener("click", function (e) {
      // Clicks on the dimmed page dismiss only from the invitation, not mid-tour.
      if (!state.touring) closeGuide();
      e.preventDefault();
    });
  }

  document.addEventListener("keydown", onKeydown);
  window.addEventListener("resize", onResize, { passive: true });
  window.addEventListener("scroll", onResize, { passive: true, capture: true });
  if (window.visualViewport) {
    window.visualViewport.addEventListener("resize", onResize);
    window.visualViewport.addEventListener("scroll", onResize);
  }

  window.addEventListener("az:langchange", function () {
    if (!state.open) return;
    applyI18n(root);
    if (state.touring) fillCallout();
  });

  var routeObs = new MutationObserver(onRouteWatch);
  routeObs.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["data-az-route", "class"],
  });
  onRouteWatch();

  window.AZ_guide = {
    open: openInvite,
    close: closeGuide,
  };
})();
