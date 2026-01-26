<?php

declare(strict_types=1);

namespace Done\PayTR\Adapters;

use Done\PayTR\Contracts\HttpClientResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Guzzle ResponseInterface'i HttpClientResponse'a uyarlar.
 */
final class GuzzleHttpClientResponse implements HttpClientResponse
{
    public function __construct(
        private ResponseInterface $response
    ) {
    }

    /** @inheritDoc */
    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /** @inheritDoc */
    public function getBody(): string
    {
        return (string) $this->response->getBody();
    }

    /** @inheritDoc */
    public function getJson(): ?array
    {
        $body = $this->getBody();
        if ($body === '') {
            return null;
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : null;
    }
}
