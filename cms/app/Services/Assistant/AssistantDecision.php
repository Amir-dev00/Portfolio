<?php

namespace App\Services\Assistant;

/**
 * Validates model (or local) assistant decisions before they reach the frontend.
 */
class AssistantDecision
{
    public function __construct(private AssistantDestinations $destinations) {}

    /**
     * @return array{
     *   action:string,
     *   reply:string,
     *   destination:?string,
     *   url:?string,
     *   label:?string,
     *   mode:?string
     * }
     */
    public function answer(string $reply): array
    {
        return [
            'action' => 'answer',
            'reply' => $this->clipReply($reply),
            'destination' => null,
            'url' => null,
            'label' => null,
            'mode' => null,
        ];
    }

    /**
     * @return array{
     *   action:string,
     *   reply:string,
     *   destination:?string,
     *   url:?string,
     *   label:?string,
     *   mode:?string
     * }|null
     */
    public function navigate(string $destinationId, string $locale, ?string $reply = null, bool $preview = false): ?array
    {
        $resolved = $this->destinations->resolve($destinationId, $locale, $preview);
        if ($resolved === null) {
            return null;
        }

        $confirmation = $this->clipReply((string) ($reply ?? ''));
        if ($confirmation === '') {
            $confirmation = $locale === 'fa'
                ? 'دارم «'.$resolved['label'].'» را باز می‌کنم.'
                : 'Opening '.$resolved['label'].'.';
        }

        return [
            'action' => 'navigate',
            'reply' => $confirmation,
            'destination' => $resolved['id'],
            'url' => $resolved['url'],
            'label' => $resolved['label'],
            'mode' => $resolved['mode'],
        ];
    }

    /**
     * @return array{
     *   action:string,
     *   reply:string,
     *   destination:?string,
     *   url:?string,
     *   label:?string,
     *   mode:?string
     * }
     */
    public function fromModelPayload(mixed $payload, string $locale, bool $preview = false): array
    {
        if (! is_array($payload)) {
            return $this->invalidFallback($locale);
        }

        $action = strtolower(trim((string) ($payload['action'] ?? '')));
        $reply = $this->clipReply((string) ($payload['reply'] ?? ''));
        $destination = strtolower(trim((string) ($payload['destination'] ?? '')));

        if ($action === 'navigate') {
            $decision = $this->navigate($destination, $locale, $reply !== '' ? $reply : null, $preview);
            if ($decision !== null) {
                return $decision;
            }

            return $this->invalidFallback($locale);
        }

        if ($action === 'answer') {
            if ($reply === '') {
                return $this->invalidFallback($locale);
            }

            return $this->answer($reply);
        }

        // Unsupported / missing action.
        if ($reply !== '' && ($destination === '' || ! $this->destinations->isAllowed($destination))) {
            return $this->answer($reply);
        }

        return $this->invalidFallback($locale);
    }

    /**
     * @return array{
     *   action:string,
     *   reply:string,
     *   destination:?string,
     *   url:?string,
     *   label:?string,
     *   mode:?string
     * }
     */
    public function invalidFallback(string $locale): array
    {
        return $this->answer(
            $locale === 'fa'
                ? 'نتونستم اون صفحه را باز کنم. دربارهٔ کار امیرحسین بپرسید، یا بگویید نمونه‌کارها، درباره، تماس یا رزومه.'
                : 'I couldn’t open that page. Ask about Amirhossein’s work, or say Work, About, Contact, or CV.'
        );
    }

    protected function clipReply(string $reply): string
    {
        $reply = trim(strip_tags($reply));
        $reply = str_replace("\0", '', $reply);
        if (mb_strlen($reply) > 4000) {
            $reply = mb_substr($reply, 0, 4000);
        }

        return trim($reply);
    }
}
