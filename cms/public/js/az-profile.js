/* Homepage circular profile — entrance + desktop tilt. Independent of AZ preloader. */
(function () {
  "use strict";

  var root = document.getElementById("az-profile");
  if (!root || root.dataset.azBound) return;
  root.dataset.azBound = "1";

  var orb = root.querySelector(".az-profile__orb");
  var glass = root.querySelector(".az-profile__glass");
  var shine = root.querySelector(".az-profile__shine");
  var reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)");
  var revealed = false;
  var tiltBound = false;
  var raf = 0;
  var targetX = 0;
  var targetY = 0;
  var currentX = 0;
  var currentY = 0;

  function preloaderStillOpen() {
    var html = document.documentElement;
    return html.classList.contains("az-preloader-open") || html.classList.contains("az-loading");
  }

  function reveal() {
    if (revealed) return;
    revealed = true;
    root.classList.add("is-ready");

    if (reduced) {
      root.classList.add("is-visible");
      root.style.opacity = "1";
      root.style.filter = "none";
      return;
    }

    var gsapApi = window.gsap;
    if (gsapApi && typeof gsapApi.fromTo === "function") {
      gsapApi.fromTo(
        root,
        { autoAlpha: 0, scale: 0.9, filter: "blur(8px)", y: 12 },
        {
          autoAlpha: 1,
          scale: 1,
          filter: "blur(0px)",
          y: 0,
          duration: 0.85,
          ease: "power3.out",
          delay: 0.08,
          onStart: function () {
            root.classList.add("is-visible");
          },
          onComplete: function () {
            gsapApi.set(root, { clearProps: "transform,filter,opacity,visibility" });
            root.style.opacity = "1";
            root.style.visibility = "visible";
            root.style.filter = "none";
          },
        }
      );
      if (glass) {
        gsapApi.fromTo(
          glass,
          { scale: 0.94 },
          {
            scale: 1,
            duration: 0.95,
            ease: "power3.out",
            delay: 0.1,
            onComplete: function () {
              gsapApi.set(glass, { clearProps: "transform" });
            },
          }
        );
      }
      return;
    }

    root.classList.add("is-visible");
    root.style.transition = "opacity 700ms cubic-bezier(0.23,1,0.32,1), filter 700ms cubic-bezier(0.23,1,0.32,1), transform 820ms cubic-bezier(0.16,1,0.3,1)";
    root.style.opacity = "1";
    root.style.filter = "blur(0px)";
    root.style.transform = "scale(1)";
  }

  function tryReveal() {
    if (!preloaderStillOpen()) reveal();
  }

  function onPreloaderEnd() {
    window.setTimeout(reveal, 180);
  }

  window.addEventListener("arts/preloader/end", onPreloaderEnd);
  window.addEventListener("az:preloader-end", tryReveal, { once: true });
  tryReveal();

  var htmlWatcher = new MutationObserver(function () {
    tryReveal();
  });
  htmlWatcher.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["class"],
  });

  window.setTimeout(tryReveal, 1600);
  window.setTimeout(function () {
    if (!revealed) reveal();
  }, 9000);

  function applyTilt() {
    raf = 0;
    currentX += (targetX - currentX) * 0.12;
    currentY += (targetY - currentY) * 0.12;
    if (glass) {
      glass.style.transform =
        "rotateX(" + currentY.toFixed(2) + "deg) rotateY(" + currentX.toFixed(2) + "deg) scale(1.035) translateZ(0)";
    }
    if (shine) {
      shine.style.transform =
        "translate3d(" + (currentX * 0.55).toFixed(2) + "%, " + (-currentY * 0.45).toFixed(2) + "%, 0)";
    }
    if (Math.abs(currentX - targetX) > 0.05 || Math.abs(currentY - targetY) > 0.05) {
      raf = window.requestAnimationFrame(applyTilt);
    }
  }

  function queueTilt() {
    if (!raf) raf = window.requestAnimationFrame(applyTilt);
  }

  function onMove(event) {
    if (!orb || reduced) return;
    var rect = orb.getBoundingClientRect();
    var px = (event.clientX - rect.left) / rect.width - 0.5;
    var py = (event.clientY - rect.top) / rect.height - 0.5;
    targetX = Math.max(-1, Math.min(1, px)) * 8;
    targetY = Math.max(-1, Math.min(1, py)) * -7;
    queueTilt();
  }

  function onLeave() {
    targetX = 0;
    targetY = 0;
    queueTilt();
    if (glass) {
      glass.style.transform = "";
    }
    if (shine) {
      shine.style.transform = "";
    }
  }

  function bindTilt(enabled) {
    if (!orb || reduced) return;
    if (enabled && !tiltBound) {
      orb.addEventListener("pointermove", onMove);
      orb.addEventListener("pointerleave", onLeave);
      tiltBound = true;
    } else if (!enabled && tiltBound) {
      orb.removeEventListener("pointermove", onMove);
      orb.removeEventListener("pointerleave", onLeave);
      tiltBound = false;
      onLeave();
    }
  }

  bindTilt(finePointer.matches);
  if (typeof finePointer.addEventListener === "function") {
    finePointer.addEventListener("change", function (event) {
      bindTilt(event.matches);
    });
  }
})();
