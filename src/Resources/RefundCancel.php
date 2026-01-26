<?php

declare(strict_types=1);

namespace Done\PayTR\Resources;

use Done\PayTR\Config;
use Done\PayTR\Contracts\HttpClient;
use Done\PayTR\Crypto\Signature;
use Done\PayTR\DTO\Request\RefundRequest as RefundRequestDto;
use Done\PayTR\DTO\Response\RefundResponse;
use Done\PayTR\Exceptions\ApiException;
use Done\PayTR\Request\Refund\RefundRequest as FluentRefundRequest;

/**
 * İade işlemi.
 */
final class RefundCancel
{
    private const IADE_URL = 'https://www.paytr.com/odeme/iade';

    public function __construct(
        private Config $config,
        private HttpClient $httpClient
    ) {
    }

    /**
     * İade talebi. Fluent RefundRequest veya eski DTO kabul eder.
     *
     * @throws ApiException status "error" ise
     * @throws \Done\PayTR\Exceptions\HttpException İstek hatası
     */
    public function refund(RefundRequestDto|FluentRefundRequest $request): RefundResponse
    {
        if ($request instanceof FluentRefundRequest) {
            return $this->refundWith($request);
        }
        return $this->refundByDto($request);
    }

    /**
     * Fluent RefundRequest ile iade. Geriye uyumluluk için korunur.
     *
     * @deprecated refund(RefundRequest) kullanın
     */
    public function refundWith(FluentRefundRequest $request): RefundResponse
    {
        $request->validate();
        $dto = new RefundRequestDto(
            $request->getMerchantOid(),
            $request->getReturnAmount(),
            $request->getReferenceNo()
        );
        return $this->refundByDto($dto);
    }

    /**
     * @deprecated refund(FluentRefundRequest) tercih edin
     */
    private function refundByDto(RefundRequestDto $request): RefundResponse
    {
        $paytrToken = Signature::refundToken(
            $this->config->merchantKey,
            $this->config->merchantSalt,
            $this->config->merchantId,
            $request->merchantOid,
            $request->returnAmount
        );

        $body = array_merge($request->toArray(), [
            'merchant_id' => $this->config->merchantId,
            'paytr_token' => $paytrToken,
        ]);

        $response = $this->httpClient->post(self::IADE_URL, $body);
        $json = $response->getJson();
        if ($json === null) {
            throw new ApiException('Geçersiz JSON yanıt.', null, []);
        }

        $dto = RefundResponse::fromArray($json);
        if (!$dto->isSuccess()) {
            throw new ApiException(
                'PayTR iade hatası: ' . ($dto->errMsg ?? $dto->errNo ?? 'bilinmeyen'),
                $dto->errMsg,
                $json
            );
        }

        return $dto;
    }
}
