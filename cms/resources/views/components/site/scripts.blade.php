    <!-- VENDOR SCRIPTS -->
        <script>
      window.AZ_BOOTSTRAP = @json($bootstrap);
      window.AZ_CSRF_TOKEN = @json(csrf_token());
    </script>
    <script src="{{ $assetV('js/az-i18n.js') }}"></script>
    <script src="{{ $assetV('js/az-preloader.js') }}"></script>
    <script src="{{ $assetV('js/navbar-glass.js') }}"></script>
    <script src="{{ $assetV('js/vendor.js') }}"></script>
    <!-- - VENDOR SCRIPTS -->
    <!-- COMPONENTS -->
    <script src="{{ $assetV('js/components.js') }}"></script>
    <script src="{{ $assetV('js/az-profile.js') }}"></script>
    <script src="{{ $assetV('js/az-projects.js') }}"></script>
    <script>
      (function () {
        var boot = window.AZ_BOOTSTRAP || {};
        var hash = boot.initialHash || '#home';
        if (!location.hash || location.hash === '#' || location.hash === '#home') {
          // Keep Laravel path (e.g. /work) and only attach the SPA fragment.
          history.replaceState({ az: true }, '', location.pathname + location.search + hash);
        }
      })();
    </script>
    <script src="{{ $assetV('js/az-app.js') }}"></script>
    <script src="{{ $assetV('js/az-booking.js') }}"></script>
    <!-- - COMPONENTS -->
