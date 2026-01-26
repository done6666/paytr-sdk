<?php

declare(strict_types=1);

namespace Done\PayTR\Resources;

use Done\PayTR\Config;
use Done\PayTR\Contracts\HttpClient;
use Done\PayTR\DTO\Response\IframeTokenResponse;
use Done\PayTR\Exceptions\ApiException;
use Done\PayTR\Options;
use Done\PayTR\Request\Iframe\CreateTokenRequest;

/**
 * Iframe token ve URL. createToken ile fluent request, iframeUrl ile ödeme sayfası URL'i.
 */
final class Iframe
{
    private const GET_TOKEN_PATH = '/odeme/api/get-token';
    private const IFRAME_BASE = 'https://www.paytr.com/odeme/guvenli/';

    public function __construct(
        private Config $config,
        private HttpClient $httpClient,
        private ?Options $options = null
    ) {
    }

    /**
     * Fluent CreateTokenRequest ile token alır. Client Options ile kurulmuş olmalı.
     *
     * @throws ApiException PayTR status "failed" dönerse
     * @throws \Done\PayTR\Exceptions\HttpException İstek hatası
     * @throws \InvalidArgumentException Options yoksa
     */
    public function createToken(CreateTokenRequest $request): IframeTokenResponse
    {
        if ($this->options === null) {
            throw new \InvalidArgumentException('createToken için Client Options ile oluşturulmalıdır.');
        }
        $payload = $request->toPayload($this->options);
        $baseUrl = rtrim($this->options->getBaseUrl(), '/');
        $url = $baseUrl . self::GET_TOKEN_PATH;

        $response = $this->httpClient->post($url, $payload);
        $json = $response->getJson();
        if ($json === null) {
            throw new ApiException('Geçersiz JSON yanıt.', null, []);
        }

        $dto = IframeTokenResponse::fromArray($json);
        if (!$dto->isSuccess()) {
            throw new ApiException(
                'PayTR iframe token hatası: ' . ($dto->reason ?? 'bilinmeyen'),
                $dto->reason,
                $json
            );
        }

        return $dto;
    }

    /**
     * Token ile iframe src URL'si.
     */
    public function iframeUrl(string $token): string
    {
        return self::IFRAME_BASE . $token;
    }

    /**
     * Statik URL (token parametre ile).
     */
    public static function iframeUrlForToken(string $token): string
    {
        return self::IFRAME_BASE . $token;
    }
}
