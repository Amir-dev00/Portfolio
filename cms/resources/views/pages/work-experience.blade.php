<section id="work-experience" class="az-experience" lang="{{ $locale }}" aria-labelledby="work-experience-title" tabindex="-1">
  <h2 id="work-experience-title" class="az-panel__title" data-i18n="work.experience.title">{{ $locale === 'fa' ? 'سوابق کاری' : 'Work experience' }}</h2>
  @forelse ($experience as $entry)
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
        <div class="az-experience__meta">
          @if ($entry['type_label'])
            <span class="az-experience__type">{{ $entry['type_label'] }}</span>
          @endif
          <span><time datetime="{{ $entry['start_month'] }}">{{ $entry['start_label'] }}</time>
            <span aria-hidden="true">–</span>
            @if (!empty($entry['end_month']))
              <time datetime="{{ $entry['end_month'] }}">{{ $entry['end_label'] }}</time>
            @else
              {{ $entry['end_label'] }}
            @endif
          </span>
          @if ($entry['duration'])
            <span>{{ $entry['duration'] }}</span>
          @endif
        </div>
        @if ($entry['description'])
          <p class="az-experience__description" dir="auto">{{ $entry['description'] }}</p>
        @endif
      </div>
    </article>
  @empty
    <p class="az-panel__lead" data-i18n="work.experience.empty">{{ $locale === 'fa' ? 'هنوز سابقه‌ای منتشر نشده.' : 'No work experience has been published yet.' }}</p>
  @endforelse
</section>
