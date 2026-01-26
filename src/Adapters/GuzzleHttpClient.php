<?php

declare(strict_types=1);

namespace Done\PayTR\Adapters;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Done\PayTR\Contracts\HttpClient;
use Done\PayTR\Contracts\HttpClientResponse;
use Done\PayTR\Exceptions\HttpException;

/**
 * Guzzle ile HttpClient implementasyonu.
 * Composer'da guzzlehttp/guzzle kurulu olmalıdır.
 */
final class GuzzleHttpClient implements HttpClient
{
    private const DEFAULT_OPTIONS = [
        'timeout' => 20,
        'connect_timeout' => 10,
    ];

    public function __construct(
        private ClientInterface $guzzle
    ) {
    }

    /**
     * Varsayılan Guzzle client ile instance oluşturur.
     */
    public static function default(): self
    {
        return new self(new \GuzzleHttp\Client(self::DEFAULT_OPTIONS));
    }

    /** @inheritDoc */
    public function post(string $url, array $body = [], array $options = []): HttpClientResponse
    {
        $requestOptions = array_merge([
            'form_params' => $body,
            'headers' => $options['headers'] ?? [],
        ], self::DEFAULT_OPTIONS, $options);

        try {
            $response = $this->guzzle->request('POST', $url, $requestOptions);
        } catch (GuzzleException $e) {
            throw new HttpException('HTTP isteği başarısız: ' . $e->getMessage(), 0, $e);
        }

        return new GuzzleHttpClientResponse($response);
    }
}
