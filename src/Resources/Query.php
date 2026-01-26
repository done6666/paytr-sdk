<?php

declare(strict_types=1);

namespace Done\PayTR\Resources;

use Done\PayTR\Config;
use Done\PayTR\Contracts\HttpClient;
use Done\PayTR\Crypto\Signature;
use Done\PayTR\DTO\Request\QueryRequest as QueryRequestDto;
use Done\PayTR\DTO\Response\QueryResponse;
use Done\PayTR\Exceptions\ApiException;
use Done\PayTR\Request\Query\StatusRequest;

/**
 * Ödeme durum sorgulama (Mağaza / Pazaryeri).
 */
final class Query
{
    private const DURUM_SORGU_URL = 'https://www.paytr.com/odeme/durum-sorgu';

    public function __construct(
        private Config $config,
        private HttpClient $httpClient
    ) {
    }

    /**
     * Fluent StatusRequest ile sipariş durumu sorgular.
     *
     * @throws ApiException status "error" ise
     * @throws \Done\PayTR\Exceptions\HttpException İstek hatası
     */
    public function status(StatusRequest $request): QueryResponse
    {
        $request->validate();
        return $this->query(new QueryRequestDto($request->getMerchantOid()));
    }

    /**
     * Sipariş durumunu sorgular.
     *
     * @throws ApiException status "error" ise
     * @throws \Done\PayTR\Exceptions\HttpException İstek hatası
     *
     * @deprecated status(StatusRequest) tercih edin
     */
    public function query(QueryRequestDto $request): QueryResponse
    {
        $paytrToken = Signature::queryToken(
            $this->config->merchantKey,
            $this->config->merchantSalt,
            $this->config->merchantId,
            $request->merchantOid
        );

        $body = array_merge($request->toArray(), [
            'merchant_id' => $this->config->merchantId,
            'paytr_token' => $paytrToken,
        ]);

        $response = $this->httpClient->post(self::DURUM_SORGU_URL, $body);
        $json = $response->getJson();
        if ($json === null) {
            throw new ApiException('Geçersiz JSON yanıt.', null, []);
        }

        $dto = QueryResponse::fromArray($json);
        if (!$dto->isSuccess()) {
            throw new ApiException(
                'PayTR durum sorgu hatası: ' . ($dto->errMsg ?? $dto->errNo ?? 'bilinmeyen'),
                $dto->errMsg,
                $json
            );
        }

        return $dto;
    }
}
