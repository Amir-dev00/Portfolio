<section id="work-experience" class="az-experience" lang="{{ $locale }}" aria-labelledby="work-experience-title" tabindex="-1">
  <h2 id="work-experience-title" class="az-panel__title" data-i18n="work.experience.title">{{ $locale === 'fa' ? 'سوابق کاری' : 'Work experience' }}</h2>
  <ul class="az-experience__list" role="list">
    @foreach ($experience as $entry)
      <li class="az-experience__card">
        <div class="az-experience__logo" aria-hidden="true">
          @if ($entry['logo_url'])
            <img src="{{ $entry['logo_url'] }}" alt="" width="56" height="56" loading="lazy" decoding="async">
          @else
            {{ \Illuminate\Support\Str::substr($entry['company'], 0, 1) }}
          @endif
        </div>
        <div class="az-experience__copy" dir="{{ $locale === 'fa' ? 'rtl' : 'ltr' }}">
          <div class="az-experience__header">
            <div>
              <p class="az-experience__company" dir="auto">
                @if (!empty($entry['website']))
                  <a href="{{ $entry['website'] }}" target="_blank" rel="noopener noreferrer">{{ $entry['company'] }} <span aria-hidden="true">↗</span></a>
                @else
                  {{ $entry['company'] }}
                @endif
              </p>
              <h3 dir="auto">{{ $entry['role'] }}</h3>
            </div>
            <p class="az-experience__period">
              <time datetime="{{ $entry['start_month'] }}">{{ $entry['start_label'] }}</time>
              <span aria-hidden="true">–</span>
              @if (!empty($entry['end_month']))
                <time datetime="{{ $entry['end_month'] }}">{{ $entry['end_label'] }}</time>
              @else
                <span class="az-experience__ongoing">{{ $entry['end_label'] }}</span>
              @endif
            </p>
          </div>
          @if ($entry['description'])
            <p class="az-experience__description" dir="auto">{{ $entry['description'] }}</p>
          @endif
          @if ($entry['type_label'] || $entry['duration'])
            <div class="az-experience__meta">
              @if ($entry['type_label'])
                <span class="az-experience__type">{{ $entry['type_label'] }}</span>
              @endif
              @if ($entry['duration'])
                <span>{{ $entry['duration'] }}</span>
              @endif
            </div>
          @endif
        </div>
      </li>
    @endforeach
  </ul>
</section>
