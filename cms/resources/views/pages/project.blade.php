          <!-- PROJECT DETAIL VIEW -->
          <section class="az-view {{ ($viewName ?? '') === 'project' ? 'is-active is-visible' : '' }}" id="view-project" data-view="project" aria-label="{{ $strings['project.aria'] ?? 'Project' }}" data-i18n-attr="aria-label:project.aria" @if(($viewName ?? '') !== 'project') hidden aria-hidden="true" @endif>
            <div id="az-project-root">
              @if(($viewName ?? '') === 'project' && !empty($project))
                <article class="az-panel">
                  <p class="az-panel__eyebrow">{{ $project['category'] ?? '' }} · {{ $project['date'] ?? '' }}</p>
                  <h1 class="az-panel__title">{{ $project['title'] ?? '' }}</h1>
                  <p class="az-panel__lead">{{ $project['fullDescription'] ?? '' }}</p>
                </article>
              @endif
            </div>
          </section>
