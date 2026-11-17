<section id="work-experience" class="az-experience" lang="{{ $locale }}" aria-labelledby="work-experience-title" tabindex="-1">
  <h2 id="work-experience-title" class="az-panel__title" data-i18n="work.experience.title">{{ $locale === 'fa' ? 'سوابق کاری' : 'Work experience' }}</h2>
  <ol class="az-experience__timeline" role="list">
    @foreach ($experience as $entry)
      <li class="az-experience__event">
        <p class="az-experience__period">
          <time datetime="{{ $entry['start_month'] }}">{{ $entry['start_label'] }}</time>
          <span aria-hidden="true">–</span>
          @if (!empty($entry['end_month']))
            <time datetime="{{ $entry['end_month'] }}">{{ $entry['end_label'] }}</time>
          @else
            <span class="az-experience__ongoing">{{ $entry['end_label'] }}</span>
          @endif
        </p>
        <span class="az-experience__marker" @if (empty($entry['end_month'])) data-ongoing @endif aria-hidden="true"></span>
        <article class="az-experience__card">
          <div class="az-experience__logo" aria-hidden="true">
            @if ($entry['logo_url'])
              <img src="{{ $entry['logo_url'] }}" alt="" width="52" height="52" loading="lazy" decoding="async">
            @else
              {{ \Illuminate\Support\Str::substr($entry['company'], 0, 1) }}
            @endif
          </div>
          <div class="az-experience__copy">
            <h3 dir="auto">{{ $entry['role'] }}</h3>
            <p dir="auto">
              @if (!empty($entry['website']))
                <a href="{{ $entry['website'] }}" target="_blank" rel="noopener noreferrer">{{ $entry['company'] }} <span aria-hidden="true">↗</span></a>
              @else
                {{ $entry['company'] }}
              @endif
            </p>
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
            @if ($entry['description'])
              <p class="az-experience__description" dir="auto">{{ $entry['description'] }}</p>
            @endif
          </div>
        </article>
      </li>
    @endforeach
  </ol>
</section>
