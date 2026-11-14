<section
  class="az-view {{ $activeView === 'ask' ? 'is-active is-visible' : '' }}"
  id="view-ask"
  data-view="ask"
  aria-labelledby="az-ask-title"
  @if($activeView !== 'ask') hidden aria-hidden="true" @endif
>
  <div class="az-assistant az-ask" data-az-assistant data-assistant-api="{{ url('/api/assistant') }}">
    <header class="az-ask__header">
      <h1 class="az-ask__title" id="az-ask-title" tabindex="-1" data-i18n="assistant.title">
        {{ $strings['assistant.title'] ?? ($locale === 'fa' ? 'دربارهٔ امیرحسین بپرس' : 'Ask about Amirhossein') }}
      </h1>
      <button type="button" class="az-assistant__text-btn" data-az-assistant-reset hidden data-i18n="assistant.newChat">
        {{ $strings['assistant.newChat'] ?? ($locale === 'fa' ? 'گفتگوی تازه' : 'New chat') }}
      </button>
    </header>

    <div class="az-ask__conversation">
      <div class="az-assistant__transcript" data-az-assistant-transcript role="log" tabindex="0" aria-label="{{ $locale === 'fa' ? 'گفتگو' : 'Conversation' }}" data-i18n-attr="aria-label:assistant.transcript"></div>
      <p class="az-ask__status" data-az-assistant-status role="status" hidden></p>
    </div>

    <div class="az-ask__composer-area">
      <div class="az-ask__suggestions" data-az-assistant-suggestions hidden>
        <div class="az-ask__suggestions-inner" role="group" aria-label="{{ $locale === 'fa' ? 'سؤال‌های پیشنهادی' : 'Suggested questions' }}" data-i18n-attr="aria-label:assistant.suggestions.label">
          @foreach ([
            'work' => ['work', 'What has Amirhossein worked on?', 'امیرحسین روی چه پروژه‌هایی کار کرده؟'],
            'about' => ['about', 'What are his skills?', 'امیرحسین چه مهارت‌هایی داره؟'],
            'experience' => ['work', 'What’s his professional experience?', 'امیرحسین چه سابقهٔ کاری‌ای داره؟'],
            'education' => ['about', 'What did he study?', 'امیرحسین چی خونده؟'],
            ...(($booking['enabled'] ?? true)
              ? ['contact' => ['contact', 'How can I book a call?', 'چطور می‌تونم تماس رزرو کنم؟']]
              : ['reachOut' => ['contact', 'How can I contact him?', 'چطور می‌تونم باهاش ارتباط بگیرم؟']]),
          ] as $suggestion => [$destination, $questionEn, $questionFa])
            <button type="button" class="az-ask__suggestion" data-az-assistant-suggestion="{{ $suggestion }}" data-az-assistant-destination="{{ $destination }}">
              <span data-i18n="assistant.suggestions.{{ $suggestion }}.question">{{ $locale === 'fa' ? $questionFa : $questionEn }}</span>
              <span aria-hidden="true">↗</span>
            </button>
          @endforeach
        </div>
      </div>
      @include('components.site.assistant-composer', ['inputId' => 'az-ask-input'])
    </div>
  </div>
</section>
