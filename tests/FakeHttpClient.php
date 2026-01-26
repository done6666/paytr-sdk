<?php

declare(strict_types=1);

namespace Done\PayTR\Tests;

use Done\PayTR\Contracts\HttpClient;
use Done\PayTR\Contracts\HttpClientResponse;

/**
 * Gerçek PayTR'ye istek atmadan test için kullanılan sahte HTTP client.
 */
final class FakeHttpClient implements HttpClient
{
    /** @var list<array{url: string, body: array}> */
    private array $requests = [];

    private ?string $nextBody = null;

    private int $nextStatus = 200;

    /**
     * Bir sonraki post() çağrısında dönecek JSON body'yi ayarlar.
     */
    public function setNextResponse(int $status = 200, ?string $jsonBody = null): self
    {
        $this->nextStatus = $status;
        $this->nextBody = $jsonBody;
        return $this;
    }

    /**
     * Yapılan istekleri döndürür.
     *
     * @return list<array{url: string, body: array}>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /** @inheritDoc */
    public function post(string $url, array $body = [], array $options = []): HttpClientResponse
    {
        $this->requests[] = ['url' => $url, 'body' => $body];
        return new FakeHttpClientResponse(
            $this->nextStatus,
            $this->nextBody ?? '{}'
        );
    }
}
