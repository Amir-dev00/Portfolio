(function () {
  "use strict";

  var html = document.documentElement;
  var panel = document.querySelector(".az-welcome-tour");
  var launch = document.querySelector("[data-az-tour-launch]");
  if (!panel || !launch) return;

  var title = panel.querySelector("[data-az-tour-title]");
  var copy = panel.querySelector("[data-az-tour-copy]");
  var count = panel.querySelector("[data-az-tour-step]");
  var avatar = panel.querySelector("[data-az-tour-avatar]");
  var next = panel.querySelector("[data-az-tour-next]");
  var close = panel.querySelector("[data-az-tour-close]");
  var steps = [".az-lang-switch", "[data-az-assistant-home] .az-assistant__composer", "#glass-nav .glass-nav__bar"];
  var motion = window.matchMedia("(prefers-reduced-motion: reduce)");
  var storageKey = "az-welcome-tour-v1";
  var index = -1;
  var highlighted = null;
  var flight = null;
  var returning = null;
  var greeted = false;
  var frame = 0;
  var pointAngle = 0;

  avatar.appendChild(launch.firstElementChild.cloneNode(true));

  function ready() {
    var home = launch.closest("[data-az-assistant-home]");
    return html.dataset.azRoute === "home" && html.classList.contains("az-app-ready") &&
      !html.classList.contains("az-preloader-open") && !html.classList.contains("az-loading") &&
      home && !home.hidden && !!window.AZ_i18n;
  }

  function tr(key, values) {
    return window.AZ_i18n.t("guide." + key, values);
  }

  function availableTarget(selector) {
    var element = document.querySelector(selector);
    return element && element.getBoundingClientRect().width > 0 ? element : null;
  }

  function aimAt(target) {
    var face = avatar.getBoundingClientRect();
    var dx = target.left + target.width / 2 - (face.left + face.width / 2);
    var dy = target.top + target.height / 2 - (face.top + face.height / 2);
    var angle = Math.atan2(dy, dx);
    // Keep the wrist turn short when the target crosses the ±180° boundary.
    pointAngle += Math.atan2(Math.sin(angle - pointAngle), Math.cos(angle - pointAngle));
    avatar.style.setProperty("--az-avatar-point-angle", pointAngle + "rad");
    avatar.style.setProperty("--az-avatar-look-x", Math.max(-2, Math.min(2, dx / 36)) + "px");
    avatar.style.setProperty("--az-avatar-look-y", Math.max(-2, Math.min(2, dy / 36)) + "px");
  }

  function place() {
    if (panel.hidden || !highlighted) return;

    var target = highlighted.getBoundingClientRect();
    var box = panel.getBoundingClientRect();
    var viewport = window.visualViewport;
    var margin = 12;
    var gap = 14;
    var minX = (viewport ? viewport.offsetLeft : 0) + margin;
    var minY = (viewport ? viewport.offsetTop : 0) + margin;
    var maxX = minX + (viewport ? viewport.width : html.clientWidth) - 2 * margin - box.width;
    var maxY = minY + (viewport ? viewport.height : html.clientHeight) - 2 * margin - box.height;
    var centerX = target.left + (target.width - box.width) / 2;
    var centerY = target.top + (target.height - box.height) / 2;
    var vertical = [[centerX, target.bottom + gap], [centerX, target.top - box.height - gap]];
    var horizontal = [[target.right + gap, centerY], [target.left - box.width - gap, centerY]];
    var candidates = html.clientWidth >= 768 ? horizontal.concat(vertical) : vertical.concat(horizontal);
    if (isStationaryStep(index) && panel.style.left && panel.style.top) {
      candidates = [[parseFloat(panel.style.left), parseFloat(panel.style.top)]];
    }
    var obstacles = [target];
    steps.forEach(function (selector) {
      var element = availableTarget(selector);
      if (element && element !== highlighted) obstacles.push(element.getBoundingClientRect());
    });

    var best;
    var bestScore = Infinity;
    candidates.forEach(function (point) {
      var x = Math.max(minX, Math.min(maxX, point[0]));
      var y = Math.max(minY, Math.min(maxY, point[1]));
      var score = Math.abs(x - point[0]) + Math.abs(y - point[1]);
      obstacles.forEach(function (rect, i) {
        var width = Math.max(0, Math.min(x + box.width, rect.right + 5) - Math.max(x, rect.left - 5));
        var height = Math.max(0, Math.min(y + box.height, rect.bottom + 5) - Math.max(y, rect.top - 5));
        score += width * height * (i === 0 ? 20 : 2);
      });
      if (score < bestScore) {
        best = [x, y];
        bestScore = score;
      }
    });
    panel.style.left = Math.round(best[0]) + "px";
    panel.style.top = Math.round(best[1]) + "px";
    if (index >= 0) aimAt(target);
  }

  function isStationaryStep(step) {
    return step === 1 && html.clientWidth < 768;
  }

  function show(step, focus) {
    stopReturn();
    var target = step < 0 ? launch.closest("form") : availableTarget(steps[step]);
    if (!target) return advance(step);
    var opening = panel.hidden;
    var changingStep = !opening && step >= 0 && step !== index && !isStationaryStep(step);
    var from = (opening ? launch : avatar).getBoundingClientRect();
    if (flight) {
      flight.cancel();
      flight = null;
    }
    if (highlighted) highlighted.classList.remove("az-tour-target");
    highlighted = target;
    index = step;
    if (step >= 0) highlighted.classList.add("az-tour-target");

    var key = step < 0 ? "invite" : "step" + (step + 1);
    title.textContent = tr(key + ".title");
    copy.textContent = tr(key + ".body");
    count.hidden = step < 0;
    count.textContent = step < 0 ? "" : tr("step", { n: step + 1, total: steps.length });
    close.textContent = tr(step < 0 ? "notNow" : "skip");
    next.textContent = tr(step < 0 ? "start" : step === steps.length - 1 ? "done" : "next");
    panel.hidden = false;
    avatar.setAttribute("data-az-tour-pose", step < 0 ? "wave" : "point");
    launch.classList.add("is-guiding");
    launch.setAttribute("aria-expanded", "true");
    place();

    if (!motion.matches && avatar.animate && from.width && (opening || changingStep)) {
      var to = avatar.getBoundingClientRect();
      var x = from.left + (from.width - to.width) / 2 - to.left;
      var y = from.top + (from.height - to.height) / 2 - to.top;
      if (opening) {
        flight = avatar.animate([
          { transform: "translate(" + x + "px," + y + "px) scale(.96)" },
          { transform: "translate(0,0) scale(1)" },
        ], { duration: 520, easing: "cubic-bezier(.2,.75,.25,1)" });
      } else if (Math.hypot(x, y) >= 8) {
        flight = runToStep(x, y);
      }
      if (flight) {
        var arrival = flight;
        arrival.onfinish = function () {
          if (flight !== arrival) return;
          flight = null;
          if (index >= 0) pointAtStep();
        };
      }
    }
    if (focus) title.focus({ preventScroll: true });
  }

  function runToStep(x, y) {
    var distance = Math.hypot(x, y);
    var cycles = Math.max(2, Math.min(5, Math.ceil(distance / 125)));
    var duration = cycles * 210;
    avatar.setAttribute("data-az-tour-pose", "run");
    avatar.style.setProperty("--az-avatar-run-lean", (x > 0 ? -9 : 9) + "deg");

    var frames = [{ transform: "translate(" + x + "px," + y + "px)" }];
    for (var stride = 1; stride < cycles * 4; stride += 1) {
      var fraction = stride / (cycles * 4);
      var bounce = stride % 2 ? 4 : 0;
      frames.push({
        offset: fraction,
        transform: "translate(" + x * (1 - fraction) + "px," + (y * (1 - fraction) - bounce) + "px)",
      });
    }
    frames.push({ transform: "translate(0,0)" });
    return avatar.animate(frames, { duration: duration, easing: "linear" });
  }

  function pointAtStep() {
    avatar.setAttribute("data-az-tour-pose", "point");
    aimAt(highlighted.getBoundingClientRect());
  }

  function stopReturn() {
    if (returning) {
      var current = returning;
      returning = null;
      current.animation.cancel();
      current.copy.remove();
    }
    launch.classList.remove("is-guiding");
  }

  function returnToChat(from) {
    var to = launch.getBoundingClientRect();
    if (!from.width || !to.width || !avatar.animate) return false;

    var x = to.left + (to.width - from.width) / 2 - from.left;
    var y = to.top + (to.height - from.height) / 2 - from.top + 4;
    var lift = Math.min(38, Math.hypot(x, y) * 0.18);
    var copy = avatar.cloneNode(true);
    copy.classList.add("az-assistant__avatar--flying", "az-tour-return");
    copy.removeAttribute("data-az-tour-avatar");
    copy.setAttribute("data-az-tour-pose", "return");
    copy.style.width = from.width + "px";
    copy.style.height = from.height + "px";
    copy.style.boxSizing = "border-box";
    copy.style.left = from.left + "px";
    copy.style.top = from.top + "px";
    document.body.appendChild(copy);

    var animation = copy.animate([
      { transform: "translate(0,0) scale(1)" },
      { transform: "translate(" + x * 0.55 + "px," + (y * 0.55 - lift) + "px) scale(.94)", offset: 0.55 },
      { transform: "translate(" + x + "px," + y + "px) scale(1)" },
    ], { duration: 640, easing: "cubic-bezier(.32,0,.24,1)", fill: "forwards" });
    returning = { animation: animation, copy: copy };
    animation.onfinish = function () {
      if (returning && returning.animation === animation) stopReturn();
    };
    return true;
  }

  function dismiss(restoreFocus, animateReturn) {
    if (panel.hidden) return;
    var shouldReturn = animateReturn && !motion.matches && ready();
    var from = shouldReturn && avatar.getBoundingClientRect();
    if (flight) {
      flight.cancel();
      flight = null;
    }
    if (highlighted) highlighted.classList.remove("az-tour-target");
    panel.hidden = true;
    highlighted = null;
    index = -1;
    launch.setAttribute("aria-expanded", "false");
    if (!shouldReturn || !returnToChat(from)) stopReturn();
    if (restoreFocus && ready()) launch.focus({ preventScroll: true });
  }

  function advance(step) {
    var following = step + 1;
    while (following < steps.length && !availableTarget(steps[following])) following += 1;
    if (following === steps.length) dismiss(true, true);
    else show(following, true);
  }

  function sync() {
    if (!ready()) {
      dismiss(false);
      stopReturn();
      return;
    }
    if (greeted) return;
    greeted = true;
    try {
      if (window.sessionStorage.getItem(storageKey)) return;
      window.sessionStorage.setItem(storageKey, "seen");
    } catch (err) {
      // The in-memory flag still covers SPA navigation when storage is unavailable.
    }
    if (!document.activeElement || !document.activeElement.matches("input, textarea, [contenteditable]")) show(-1, false);
  }

  function schedulePlace() {
    if (returning) stopReturn();
    if (frame || panel.hidden) return;
    frame = window.requestAnimationFrame(function () {
      frame = 0;
      place();
    });
  }

  launch.hidden = false;
  launch.addEventListener("click", function () {
    if (!ready()) return;
    if (panel.hidden) show(-1, true);
    else dismiss(true, true);
  });
  close.addEventListener("click", function () { dismiss(true, true); });
  next.addEventListener("click", function () { advance(index); });
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && !panel.hidden) dismiss(panel.contains(document.activeElement), true);
  });
  document.addEventListener("focusin", function (event) {
    if (event.target.matches("[data-az-assistant-input]")) dismiss(false);
  });
  window.addEventListener("az:langchange", function () {
    if (!panel.hidden && ready()) show(index, false);
    else sync();
  });
  motion.addEventListener("change", function () {
    if (!motion.matches) return;
    if (flight) {
      flight.cancel();
      flight = null;
    }
    stopReturn();
    if (!panel.hidden) {
      launch.classList.add("is-guiding");
      if (index >= 0) pointAtStep();
    }
    schedulePlace();
  });
  window.addEventListener("resize", schedulePlace);
  window.addEventListener("scroll", schedulePlace, { passive: true });
  if (window.visualViewport) {
    window.visualViewport.addEventListener("resize", schedulePlace);
    window.visualViewport.addEventListener("scroll", schedulePlace);
  }
  new MutationObserver(sync).observe(html, { attributes: true, attributeFilter: ["class", "data-az-route"] });
  var home = launch.closest("[data-az-assistant-home]");
  if (home) new MutationObserver(sync).observe(home, { attributes: true, attributeFilter: ["hidden"] });
  sync();
})();
