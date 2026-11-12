    <!-- VENDOR SCRIPTS -->
        <script>
      window.AZ_BOOTSTRAP = @json($bootstrap);
      window.AZ_CSRF_TOKEN = @json(csrf_token());
      window.AZ_bootMark = function (id, pct) {
        try {
          if (window.AZ_preloader && typeof window.AZ_preloader.mark === "function") {
            window.AZ_preloader.mark(id, pct);
          }
        } catch (err) {}
      };
      window.AZ_bootFail = function (reason) {
        try {
          if (window.AZ_preloader && typeof window.AZ_preloader.fail === "function") {
            window.AZ_preloader.fail(reason);
          }
        } catch (err2) {}
      };
    </script>
    <script src="{{ $assetV('js/az-i18n.js') }}" defer onload="AZ_bootMark('i18n', 22)" onerror="AZ_bootFail('Language pack failed to load.')"></script>
    <script src="{{ $assetV('js/navbar-glass.js') }}" defer onload="AZ_bootMark('navbar', 34)"></script>
    {{-- Strip desktop slide textures before Rhye boots so mobile never requests them. --}}
    <script>
      (function () {
        if (!window.matchMedia("(max-width: 767px)").matches) return;
        document.documentElement.classList.add("az-home-mobile");
        var bgs = document.querySelectorAll("#view-home .az-home-bg");
        for (var i = 0; i < bgs.length; i++) {
          bgs[i].removeAttribute("data-texture-src");
          bgs[i].removeAttribute("data-az-defer-texture");
          bgs[i].removeAttribute("data-src");
          bgs[i].style.backgroundImage = "none";
        }
        var images = document.querySelector("#view-home .js-slider-fullscreen-projects__images");
        if (images) {
          images.setAttribute("data-autoplay-enabled", "false");
          images.removeAttribute("data-transition-displacement-img");
          images.setAttribute("data-transition-effect", "slide");
        }
        if (typeof AZ_bootMark === "function") AZ_bootMark("mobile-strip", 30);
      })();
    </script>
    {{-- Startup chain: keep Rhye order (vendor → components → app). defer preserves sequence. --}}
    <script src="{{ $assetV('js/vendor.js') }}" defer onload="AZ_bootMark('vendor', 48)" onerror="AZ_bootFail('Core scripts failed to load.')"></script>
    <!-- - VENDOR SCRIPTS -->
    <!-- COMPONENTS -->
    <script src="{{ $assetV('js/components.js') }}" defer onload="AZ_bootMark('components', 58)" onerror="AZ_bootFail('UI components failed to load.')"></script>
    <script src="{{ $assetV('js/az-profile.js') }}" defer onload="AZ_bootMark('profile', 64)"></script>
    <script src="{{ $assetV('js/az-projects.js') }}" defer onload="AZ_bootMark('projects', 68)"></script>
    <script src="{{ $assetV('js/az-app.js') }}" defer onload="AZ_bootMark('app', 76); if (window.AZ_preloader) AZ_preloader.ready();" onerror="AZ_bootFail('App shell failed to load.')"></script>
    {{-- Noncritical features: after first view path; still deferred so order stays after az-app. --}}
    <script src="{{ $assetV('js/az-jalali.js') }}" defer></script>
    <script src="{{ $assetV('js/az-booking.js') }}" defer></script>
    <script src="{{ $assetV('js/az-assistant.js') }}" defer></script>
    <script src="{{ $assetV('js/az-guide.js') }}" defer></script>
    <script src="{{ $assetV('js/az-home-arrows.js') }}" defer></script>
    <!-- - COMPONENTS -->
