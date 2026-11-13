<form class="az-assistant__composer" data-az-assistant-form method="post">
  @if($inputId === 'az-assistant-input')
  <button type="button" class="az-assistant__avatar az-tour-launch" data-az-assistant-avatar="idle" data-az-tour-launch hidden aria-expanded="false" aria-controls="az-welcome-tour" aria-label="{{ $locale === 'fa' ? 'باز کردن راهنمای سایت' : 'Open site guide' }}" data-i18n-attr="aria-label:guide.launch">
  @else
  <span class="az-assistant__avatar" data-az-assistant-avatar="idle" aria-hidden="true">
  @endif
    <span class="az-assistant__avatar-shell" aria-hidden="true">
      <span class="az-assistant__avatar-face">
        <i class="az-assistant__avatar-eye"></i>
        <i class="az-assistant__avatar-eye"></i>
        <i class="az-assistant__avatar-mouth"></i>
      </span>
      <span class="az-assistant__avatar-gesture">
        <span class="az-assistant__avatar-arm">
          <span class="az-assistant__avatar-hand"></span>
        </span>
      </span>
    </span>
  @if($inputId === 'az-assistant-input')
  </button>
  @else
  </span>
  @endif
  <label class="az-assistant__sr" for="{{ $inputId }}" data-i18n="assistant.prompt">{{ $strings['assistant.prompt'] ?? ($locale === 'fa' ? 'دربارهٔ امیرحسین بپرس' : 'Ask about Amirhossein') }}</label>
  <input
    id="{{ $inputId }}" data-az-assistant-input
    class="az-assistant__input"
    name="message"
    type="text"
    maxlength="500"
    autocomplete="off"
    enterkeyhint="send"
    dir="{{ ($locale ?? 'en') === 'fa' ? 'rtl' : 'ltr' }}"
    lang="{{ ($locale ?? 'en') === 'fa' ? 'fa' : 'en' }}"
    data-i18n-attr="placeholder:assistant.prompt"
    placeholder="{{ $strings['assistant.prompt'] ?? ($locale === 'fa' ? 'دربارهٔ امیرحسین بپرس' : 'Ask about Amirhossein') }}"
  />
  <button type="submit" class="az-assistant__send" data-az-assistant-send disabled aria-label="{{ $locale === 'fa' ? 'ارسال پرسش' : 'Send question' }}" data-i18n-attr="aria-label:assistant.send">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M5 12h12"/>
      <path d="m13 6 6 6-6 6"/>
    </svg>
  </button>
</form>
