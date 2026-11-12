    <!-- VENDOR SCRIPTS -->
        <script>
      window.AZ_BOOTSTRAP = @json($bootstrap);
      window.AZ_CSRF_TOKEN = @json(csrf_token());
    </script>
    <script src="{{ $assetV('js/az-i18n.js') }}"></script>
    <script src="{{ $assetV('js/az-preloader.js') }}"></script>
    <script src="{{ $assetV('js/navbar-glass.js') }}"></script>
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
    <script src="{{ $assetV('js/vendor.js') }}"></script>
    <!-- - VENDOR SCRIPTS -->
    <!-- COMPONENTS -->
    <script src="{{ $assetV('js/components.js') }}"></script>
    <script src="{{ $assetV('js/az-profile.js') }}"></script>
    <script src="{{ $assetV('js/az-home-arrows.js') }}"></script>
    <script src="{{ $assetV('js/az-projects.js') }}"></script>
    <script src="{{ $assetV('js/az-app.js') }}"></script>
    <script src="{{ $assetV('js/az-jalali.js') }}"></script>
    <script src="{{ $assetV('js/az-booking.js') }}"></script>
    <script src="{{ $assetV('js/az-assistant.js') }}"></script>
    <!-- - COMPONENTS -->
