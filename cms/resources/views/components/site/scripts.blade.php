    <script>
      window.AZ_BOOTSTRAP = {{ \Illuminate\Support\Js::from($bootstrap) }};
      window.AZ_ASSETS = {{ \Illuminate\Support\Js::from([
        'vendorCss' => $assetV('css/vendor.css'),
        'rhyeCss' => $assetV('css/main.css'),
        'vendor' => $assetV('js/vendor.js'),
        'rhye' => $assetV('js/components.js'),
        'bookingCss' => $assetV('css/az-booking.css'),
        'jalali' => $assetV('js/az-jalali.js'),
        'booking' => $assetV('js/az-booking.js'),
        'profile' => $assetV('js/az-profile.js'),
        'tour' => $assetV('js/az-welcome-tour.js'),
        'arrows' => $assetV('js/az-home-arrows.js'),
        'assistant' => $assetV('js/az-assistant.js'),
      ]) }};
    </script>
    <script src="{{ $assetV('js/az-i18n.js') }}" defer></script>
    <script src="{{ $assetV('js/navbar-glass.js') }}" defer></script>
    @if($viewName === 'home')
      <script src="{{ $assetV('js/az-profile.js') }}" async data-az-home-profile
        onerror="this.remove()"></script>
    @endif
    <script src="{{ $assetV('js/az-app.js') }}" defer
      onerror="window.AZ_STARTUP_FAILED=true; window.dispatchEvent(new Event('az:startup-error'))"></script>
