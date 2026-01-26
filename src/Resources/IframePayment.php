<?php

declare(strict_types=1);

namespace Done\PayTR\Resources;

use Done\PayTR\Config;
use Done\PayTR\Contracts\HttpClient;
use Done\PayTR\Crypto\Signature;
use Done\PayTR\DTO\Request\IframeTokenRequest;
use Done\PayTR\DTO\Response\IframeTokenResponse;
use Done\PayTR\Exceptions\ApiException;

/**
 * Iframe token alımı ve ödeme formu başlatma (1. Adım).
 */
final class IframePayment
{
    private const GET_TOKEN_URL = 'https://www.paytr.com/odeme/api/get-token';

    public function __construct(
        private Config $config,
        private HttpClient $httpClient
    ) {
    }

    /**
     * PayTR'dan iframe token alır; token ile iframe URL oluşturulur.
     *
     * @throws ApiException PayTR status "failed" dönerse
     * @throws \Done\PayTR\Exceptions\HttpException İstek hatası
     */
    public function getToken(IframeTokenRequest $request): IframeTokenResponse
    {
        $paytrToken = Signature::iframeToken(
            $this->config->merchantKey,
            $this->config->merchantSalt,
            $this->config->merchantId,
            $request->userIp,
            $request->merchantOid,
            $request->email,
            $request->paymentAmount,
            $request->userBasket,
            $request->noInstallment,
            $request->maxInstallment,
            $request->currency,
            $request->getTestModeForHash()
        );

        $body = $request->toArray();
        $body['merchant_id'] = $this->config->merchantId;
        $body['paytr_token'] = $paytrToken;

        $response = $this->httpClient->post(self::GET_TOKEN_URL, $body);
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
     * Alınan token ile iframe src URL'sini döndürür.
     */
    public static function iframeSrcUrl(string $token): string
    {
        return 'https://www.paytr.com/odeme/guvenli/' . $token;
    }
}
