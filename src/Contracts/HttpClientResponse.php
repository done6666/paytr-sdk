<?php

declare(strict_types=1);

namespace Done\PayTR\Contracts;

/**
 * HTTP isteğine dönen yanıtı temsil eder.
 */
interface HttpClientResponse
{
    /**
     * HTTP status kodu (örn. 200, 404).
     */
    public function getStatusCode(): int;

    /**
     * Response body ham string.
     */
    public function getBody(): string;

    /**
     * Body JSON ise decode edilmiş array, değilse null.
     *
     * @return array<string, mixed>|null
     */
    public function getJson(): ?array;
}
