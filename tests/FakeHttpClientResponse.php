<?php

declare(strict_types=1);

namespace Done\PayTR\Tests;

use Done\PayTR\Contracts\HttpClientResponse;

final class FakeHttpClientResponse implements HttpClientResponse
{
    public function __construct(
        private int $statusCode,
        private string $body
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getJson(): ?array
    {
        if ($this->body === '') {
            return null;
        }
        $decoded = json_decode($this->body, true);
        return is_array($decoded) ? $decoded : null;
    }
}
