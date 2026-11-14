          <!-- PROJECT DETAIL VIEW -->
          <section class="az-view {{ ($viewName ?? '') === 'project' ? 'is-active is-visible' : '' }}" id="view-project" data-view="project" aria-label="{{ $strings['project.aria'] ?? 'Project' }}" data-i18n-attr="aria-label:project.aria" @if(($viewName ?? '') !== 'project') hidden aria-hidden="true" @endif>
            <div id="az-project-root">
              @if(($viewName ?? '') === 'project' && !empty($project))
                @php($caseStudy = $project['caseStudy'] ?? [])
                <article class="az-panel az-project">
                  <p class="az-panel__eyebrow">{{ $project['category'] ?? '' }}@if(!empty($project['date'])) · {{ $project['date'] }}@endif</p>
                  <h1 class="az-panel__title">{{ $project['title'] ?? '' }}</h1>
                  <p class="az-panel__lead">{{ $caseStudy ? $project['shortDescription'] : ($project['fullDescription'] ?? '') }}</p>
                  @if($caseStudy)
                    <section class="az-project__case-study" aria-labelledby="az-project-case-title">
                      <h2 id="az-project-case-title">{{ $locale === 'fa' ? 'جزئیات پروژه' : 'Project details' }}</h2>
                      <div class="az-project__case-overview">
                        <h3>{{ $locale === 'fa' ? 'نمای کلی' : 'Overview' }}</h3>
                        <p>{{ $caseStudy['overview'] }}</p>
                      </div>
                      <div>
                        <h3>{{ $locale === 'fa' ? 'ویژگی‌های کلیدی' : 'Key features' }}</h3>
                        <ul>
                          @foreach($caseStudy['features'] as $feature)
                            <li>{{ $feature }}</li>
                          @endforeach
                        </ul>
                      </div>
                      <div>
                        <h3>{{ $locale === 'fa' ? 'طراحی و پیاده‌سازی' : 'Design and implementation' }}</h3>
                        <p>{{ $caseStudy['approach'] }}</p>
                      </div>
                    </section>
                  @endif
                </article>
              @endif
            </div>
          </section>
