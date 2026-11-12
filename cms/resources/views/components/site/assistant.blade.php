              <div
                class="az-assistant"
                id="az-assistant"
                data-az-assistant
                data-assistant-api="{{ url('/api/assistant') }}"
                hidden
              >
                <div class="az-assistant__panel" data-az-assistant-panel>
                  <div class="az-assistant__chrome">
                    <p class="az-assistant__title" data-i18n="assistant.title">Ask about Amirhossein</p>
                    <div class="az-assistant__actions">
                      <button type="button" class="az-assistant__text-btn" data-az-assistant-reset hidden data-i18n="assistant.newChat">New chat</button>
                      <button type="button" class="az-assistant__icon-btn" data-az-assistant-collapse aria-label="Minimize chat" data-i18n-attr="aria-label:assistant.minimize" hidden>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M6 12h12"/></svg>
                      </button>
                    </div>
                  </div>

                  <div class="az-assistant__transcript" data-az-assistant-transcript hidden tabindex="0" aria-live="polite" aria-relevant="additions" aria-label="Conversation" data-i18n-attr="aria-label:assistant.transcript"></div>

                  <form class="az-assistant__composer" data-az-assistant-form>
                    <span class="az-assistant__avatar" data-az-assistant-avatar="idle" aria-hidden="true">
                      <span class="az-assistant__avatar-shell">
                        <span class="az-assistant__avatar-face">
                          <i class="az-assistant__avatar-eye"></i>
                          <i class="az-assistant__avatar-eye"></i>
                          <i class="az-assistant__avatar-mouth"></i>
                        </span>
                      </span>
                    </span>
                    <label class="az-assistant__sr" for="az-assistant-input" data-i18n="assistant.prompt">Ask about Amirhossein</label>
                    <input
                      id="az-assistant-input"
                      class="az-assistant__input"
                      name="message"
                      type="text"
                      maxlength="500"
                      autocomplete="off"
                      enterkeyhint="send"
                      data-i18n-attr="placeholder:assistant.prompt"
                      placeholder="Ask about Amirhossein"
                    />
                    <button type="submit" class="az-assistant__send" data-az-assistant-send aria-label="Send question" data-i18n-attr="aria-label:assistant.send">
                      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h12"/>
                        <path d="m13 6 6 6-6 6"/>
                      </svg>
                    </button>
                  </form>

                  <p class="az-assistant__status" data-az-assistant-status role="status" aria-live="polite" hidden></p>
                </div>

                {{-- Mobile homepage only: two text links under the collapsed composer. --}}
                <nav
                  class="az-selected-work"
                  data-az-selected-work
                  hidden
                  aria-label="Selected work"
                  data-i18n-attr="aria-label:home.selectedWork"
                >
                  <span class="az-selected-work__label" data-i18n="home.selectedWork">Selected work</span>
                  <div class="az-selected-work__links" data-az-selected-work-links></div>
                </nav>
              </div>
