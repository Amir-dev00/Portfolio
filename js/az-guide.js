/* Homepage bilingual guided tour — WebP sprite companion (no GLB / WebGL). */
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

  // 4×4 atlas, 144px cells, zero-based LTR / top-to-bottom.
  var FRAMES = {
    idle: [0, 1, 2],
    wink: [3],
    wave: [4, 5, 6, 7],
    point_left: [8, 9, 10, 11],
    point_right: [12, 13, 14, 15],
    point_left_hold: 9,
    point_right_hold: 13,
  };

  var root = document.querySelector("[data-az-guide]");
  var launch = document.querySelector("[data-az-guide-launch]");
  var companion = document.querySelector("[data-az-guide-companion]");
  var sprite = document.querySelector("[data-az-guide-sprite]");
  if (!root || !launch || !companion || !sprite) return;

  var scrim = root.querySelector("[data-az-guide-scrim]");
  var spot = root.querySelector("[data-az-guide-spotlight]");
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
    lastFocused: null,
    activeTarget: null,
    size: 48,
    poseTimer: 0,
    idleTimer: 0,
    moveTimer: 0,
    x: -80,
    y: 0,
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

  function framePosition(index) {
    var i = clamp(index | 0, 0, 15);
    var col = i % 4;
    var row = (i / 4) | 0;
    return col * (100 / 3) + "% " + row * (100 / 3) + "%";
  }

  function setFrame(index) {
    sprite.style.backgroundPosition = framePosition(index);
    sprite.setAttribute("data-pose", String(index));
  }

  function clearPoseTimer() {
    if (state.poseTimer) {
      window.clearTimeout(state.poseTimer);
      state.poseTimer = 0;
    }
  }

  function clearIdleTimer() {
    if (state.idleTimer) {
      window.clearTimeout(state.idleTimer);
      state.idleTimer = 0;
    }
  }

  function playSequence(frames, opts) {
    opts = opts || {};
    clearPoseTimer();
    clearIdleTimer();
    var hold = opts.hold;
    var fps = opts.fps || 7;
    var loop = !!opts.loop;
    var onDone = typeof opts.onDone === "function" ? opts.onDone : null;

    if (reducedMotion || !frames || !frames.length) {
      var staticFrame =
        hold != null ? hold : frames && frames.length ? frames[Math.min(1, frames.length - 1)] : 0;
      setFrame(staticFrame);
      if (onDone) onDone();
      return Promise.resolve();
    }

    return new Promise(function (resolve) {
      var i = 0;
      var stepMs = Math.max(70, Math.round(1000 / fps));

      function tick() {
        setFrame(frames[i]);
        i += 1;
        if (i < frames.length) {
          state.poseTimer = window.setTimeout(tick, stepMs);
          return;
        }
        if (hold != null) setFrame(hold);
        if (loop) {
          i = 0;
          state.poseTimer = window.setTimeout(tick, stepMs + 180);
          return;
        }
        state.poseTimer = 0;
        if (onDone) onDone();
        resolve();
      }

      tick();
    });
  }

  function startIdle() {
    clearPoseTimer();
    clearIdleTimer();
    if (reducedMotion) {
      setFrame(FRAMES.idle[0]);
      return;
    }
    var i = 0;
    function beat() {
      setFrame(FRAMES.idle[i % FRAMES.idle.length]);
      i += 1;
      state.idleTimer = window.setTimeout(beat, 520);
    }
    beat();
  }

  function playGesture(name) {
    if (name === "wave") {
      return playSequence(FRAMES.wave, { fps: 8, hold: FRAMES.idle[0] }).then(function () {
        if (!state.touring) startIdle();
      });
    }
    if (name === "wink") {
      return playSequence(FRAMES.wink, { fps: 5, hold: FRAMES.idle[0] }).then(function () {
        if (!state.touring) startIdle();
      });
    }
    if (name === "point_left") {
      return playSequence(FRAMES.point_left, {
        fps: 8,
        hold: FRAMES.point_left_hold,
      });
    }
    if (name === "point_right") {
      return playSequence(FRAMES.point_right, {
        fps: 8,
        hold: FRAMES.point_right_hold,
      });
    }
    startIdle();
    return Promise.resolve();
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
    companion.classList.toggle("is-touring", state.touring);
  }

  function launcherRect() {
    return launch.getBoundingClientRect();
  }

  function placeCompanionAt(x, y, instant) {
    state.x = x;
    state.y = y;
    if (instant || reducedMotion) {
      companion.style.transition = "opacity 200ms ease";
      companion.style.transform = "translate3d(" + Math.round(x) + "px," + Math.round(y) + "px,0)";
      return;
    }
    companion.style.transition = "opacity 200ms ease, transform 320ms cubic-bezier(0.22, 1, 0.36, 1)";
    companion.style.transform = "translate3d(" + Math.round(x) + "px," + Math.round(y) + "px,0)";
  }

  function parkOnLauncher(instant) {
    var r = launcherRect();
    var x = r.left + r.width / 2 - state.size / 2;
    var y = r.top + r.height / 2 - state.size / 2;
    placeCompanionAt(x, y, instant);
  }

  function placeMascotNear(targetRect) {
    var size = state.size;
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var safeL = 8;
    var safeR = 8;
    var safeT = 8;
    var safeB = 12;

    var preferLeft = targetRect.left + targetRect.width / 2 > vw * 0.55;
    var gap = 12;
    var x;
    var y;

    if (preferLeft) x = targetRect.left - gap - size;
    else x = targetRect.right + gap;
    y = targetRect.top + targetRect.height / 2 - size / 2;

    var isDockish = targetRect.bottom > vh * 0.72;
    if (vw <= 767) {
      if (isDockish) {
        y = targetRect.top - gap - size;
        x = preferLeft ? targetRect.left - size * 0.15 : targetRect.right - size * 0.85;
      } else if (targetRect.top < vh * 0.28) {
        y = targetRect.bottom + gap;
        x = preferLeft ? targetRect.left - size * 0.25 : targetRect.right - size * 0.75;
      }
    }

    x = clamp(x, safeL, vw - size - safeR);
    y = clamp(y, safeT, vh - size - safeB);
    placeCompanionAt(x, y, false);
    return { x: x, y: y, cx: x + size / 2, cy: y + size / 2 };
  }

  function placeInvite() {
    if (!invite) return;
    var vh = window.innerHeight;
    invite.style.left = "50%";
    invite.style.top = vh < 640 ? "42%" : "50%";
    invite.style.right = "auto";
    invite.style.bottom = "auto";
    invite.style.transform = "translate(-50%, -50%)";
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
      { left: mascot.cx - cw / 2, top: mascot.y + state.size + gap },
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
    return targetCx < mascot.cx ? "point_left" : "point_right";
  }

  function layoutStep() {
    if (!state.touring || state.step < 0) return null;
    var conf = STEPS[state.step];
    var el = resolveTarget(conf);
    if (!el) return null;
    var rect = paddedRect(el, conf.pad || 10);
    setTargetLive(el);
    updateSpotlight(rect, conf.radius || 14);
    var mascot = placeMascotNear(rect);
    placeCallout(rect, mascot);
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
    placeInvite();
    parkOnLauncher(false);
    applyI18n(invite);
    if (btnStart) btnStart.focus({ preventScroll: true });
    playGesture("wave");
  }

  function returnToLauncher() {
    setTouring(false);
    parkOnLauncher(false);
    startIdle();
  }

  function closeGuide() {
    setTouring(false);
    state.step = -1;
    clearTargetLive();
    showEl(invite, false);
    showEl(callout, false);
    setOpen(false);
    returnToLauncher();

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

    var laid = layoutStep();
    if (btnNext) btnNext.focus({ preventScroll: true });
    if (!laid) return;
    playGesture(choosePointClip(laid.mascot, laid.rect));
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
    playGesture("wink").then(done, done);
    window.setTimeout(done, reducedMotion ? 120 : 900);
  }

  function onKeydown(e) {
    if (!state.open) return;
    if (e.key === "Escape") {
      e.preventDefault();
      closeGuide();
      return;
    }
    if (!state.touring) return;
    if (e.key === "ArrowRight") {
      e.preventDefault();
      nextStep();
    }
  }

  function onResize() {
    if (!isHome()) return;
    if (state.touring) layoutStep();
    else {
      placeInvite();
      parkOnLauncher(true);
    }
  }

  function onRouteWatch() {
    if (state.open && !isHome()) closeGuide();
    launch.hidden = !isHome();
    companion.hidden = !isHome();
    if (isHome()) {
      companion.classList.add("is-ready");
      parkOnLauncher(true);
      if (!state.open) startIdle();
    } else {
      companion.classList.remove("is-ready");
    }
  }

  function markReadyWhenAtlasLoads() {
    var url = "";
    try {
      var raw = getComputedStyle(sprite).backgroundImage || "";
      var m = /url\(["']?([^"')]+)["']?\)/.exec(raw);
      url = m ? m[1] : "";
    } catch (err) {}

    var show = function () {
      parkOnLauncher(true);
      companion.classList.add("is-ready");
      startIdle();
    };

    if (!url || reducedMotion) {
      show();
      return;
    }

    var img = new Image();
    img.onload = show;
    img.onerror = show;
    img.src = url;
    if (img.complete) show();
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
    if (!state.open) {
      parkOnLauncher(true);
      return;
    }
    applyI18n(root);
    if (state.touring) fillCallout();
    onResize();
  });

  window.addEventListener("arts/preloader/end", function () {
    if (!isHome()) return;
    parkOnLauncher(true);
    companion.classList.add("is-ready");
    if (!state.open) startIdle();
  });

  var routeObs = new MutationObserver(function () {
    onRouteWatch();
    // Preloader just closed — snap the idle sprite onto the launcher.
    if (isHome() && !document.documentElement.classList.contains("az-preloader-open")) {
      parkOnLauncher(true);
    }
  });
  routeObs.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["data-az-route", "class"],
  });

  // Reveal as soon as the homepage header is in play (preloader may still be open — CSS hides then).
  onRouteWatch();
  markReadyWhenAtlasLoads();

  window.AZ_guide = {
    open: openInvite,
    close: closeGuide,
  };
})();
