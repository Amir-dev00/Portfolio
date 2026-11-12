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
    <script src="{{ $assetV('js/az-home-arrows.js') }}"></script>
    <script src="{{ $assetV('js/az-projects.js') }}"></script>
    <script src="{{ $assetV('js/az-app.js') }}"></script>
    <script src="{{ $assetV('js/az-jalali.js') }}"></script>
    <script src="{{ $assetV('js/az-booking.js') }}"></script>
    <script src="{{ $assetV('js/az-assistant.js') }}"></script>
    <!-- - COMPONENTS -->
