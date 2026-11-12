    <!-- VENDOR SCRIPTS -->
        <script>
      window.AZ_BOOTSTRAP = @json($bootstrap);
      window.AZ_CSRF_TOKEN = @json(csrf_token());
    </script>
    <script src="{{ $assetV('js/az-i18n.js') }}" defer></script>
    <script src="{{ $assetV('js/navbar-glass.js') }}" defer></script>
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
      })();
    </script>
    {{-- Startup chain: keep Rhye order (vendor → components → app). defer preserves sequence. --}}
    <script src="{{ $assetV('js/vendor.js') }}" defer></script>
    <!-- - VENDOR SCRIPTS -->
    <!-- COMPONENTS -->
    <script src="{{ $assetV('js/components.js') }}" defer></script>
    <script src="{{ $assetV('js/az-profile.js') }}" defer></script>
    <script src="{{ $assetV('js/az-projects.js') }}" defer></script>
    <script src="{{ $assetV('js/az-app.js') }}" defer></script>
    {{-- Noncritical features: after first view path; still deferred so order stays after az-app. --}}
    <script src="{{ $assetV('js/az-jalali.js') }}" defer></script>
    <script src="{{ $assetV('js/az-booking.js') }}" defer></script>
    <script src="{{ $assetV('js/az-assistant.js') }}" defer></script>
    <script src="{{ $assetV('js/az-home-arrows.js') }}" defer></script>
    <!-- - COMPONENTS -->
