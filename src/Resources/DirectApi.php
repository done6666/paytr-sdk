<?php

declare(strict_types=1);

namespace Done\PayTR\Resources;

use Done\PayTR\Config;
use Done\PayTR\Contracts\HttpClient;
use Done\PayTR\DTO\Response\BinDetailResponse;
use Done\PayTR\DTO\Response\DirectPaymentResponse;
use Done\PayTR\DTO\Response\InstallmentRatesResponse;
use Done\PayTR\Exceptions\ApiException;
use Done\PayTR\Options;
use Done\PayTR\Request\Direct\BinLookupRequest;
use Done\PayTR\Request\Direct\CreatePaymentRequest;
use Done\PayTR\Request\Direct\InstallmentRatesRequest;

/**
 * PayTR Direkt API. Ödeme isteği, BIN sorgulama, taksit oranları.
 * Doküman: https://dev.paytr.com/direkt-api
 */
final class DirectApi
{
    private const PAYMENT_PATH = '/odeme';
    private const BIN_DETAIL_PATH = '/odeme/api/bin-detail';
    private const INSTALLMENT_RATES_PATH = '/odeme/taksit-oranlari';

    public function __construct(
        private Config $config,
        private HttpClient $httpClient,
        private ?Options $options = null
    ) {
    }

    /**
     * Direkt API ödeme isteği (sync_mode=1). POST /odeme; JSON yanıt döner.
     * sync_mode=0 için payload'u createPaymentRequest->toPayload() ile alıp kendi formunuzdan POST edin.
     *
     * @throws ApiException PayTR status "failed" dönerse veya sync_mode=0 ile çağrılırsa
     * @throws \InvalidArgumentException Options yoksa
     */
    public function createPayment(CreatePaymentRequest $request): DirectPaymentResponse
    {
        if ($this->options === null) {
            throw new \InvalidArgumentException('createPayment için Client Options ile oluşturulmalıdır.');
        }
        $payload = $request->toPayload($this->options);
        if ((int)($payload['sync_mode'] ?? 0) !== 1) {
            throw new \InvalidArgumentException('createPayment() yalnızca sync_mode=1 ile kullanılır. sync_mode=0 için toPayload() ile form POST edin.');
        }
        $url = rtrim($this->options->getBaseUrl(), '/') . self::PAYMENT_PATH;
        $response = $this->httpClient->post($url, $payload);
        $json = $response->getJson();
        if ($json === null) {
            throw new ApiException('Direkt API ödeme yanıtı geçerli JSON değil.', null, []);
        }
        $dto = DirectPaymentResponse::fromArray($json);
        if ($dto->isFailed()) {
            throw new ApiException('Direkt API ödeme hatası: ' . $dto->msg, $dto->msg, $json);
        }
        return $dto;
    }

    /**
     * Ödeme formu POST URL'i. sync_mode=0 akışında form action olarak kullanın.
     */
    public function getPaymentFormUrl(): string
    {
        $options = $this->options ?? $this->configToOptions();
        return rtrim($options->getBaseUrl(), '/') . self::PAYMENT_PATH;
    }

    /**
     * BIN sorgulama. POST /odeme/api/bin-detail
     *
     * @throws ApiException status "error" dönerse
     */
    public function binLookup(BinLookupRequest $request): BinDetailResponse
    {
        $options = $this->options ?? $this->configToOptions();
        $payload = $request->toPayload($options);
        $url = rtrim($options->getBaseUrl(), '/') . self::BIN_DETAIL_PATH;
        $response = $this->httpClient->post($url, $payload);
        $json = $response->getJson();
        if ($json === null) {
            throw new ApiException('BIN sorgu yanıtı geçerli JSON değil.', null, []);
        }
        $dto = BinDetailResponse::fromArray($json);
        if ($dto->isError()) {
            throw new ApiException('BIN sorgu hatası: ' . ($dto->errMsg ?? 'bilinmeyen'), $dto->errMsg, $json);
        }
        return $dto;
    }

    /**
     * Taksit oranları sorgulama. POST /odeme/taksit-oranlari
     *
     * @throws ApiException status "error" dönerse
     */
    public function installmentRates(InstallmentRatesRequest $request): InstallmentRatesResponse
    {
        $options = $this->options ?? $this->configToOptions();
        $payload = $request->toPayload($options);
        $url = rtrim($options->getBaseUrl(), '/') . self::INSTALLMENT_RATES_PATH;
        $response = $this->httpClient->post($url, $payload);
        $json = $response->getJson();
        if ($json === null) {
            throw new ApiException('Taksit oranları yanıtı geçerli JSON değil.', null, []);
        }
        $dto = InstallmentRatesResponse::fromArray($json);
        if (!$dto->isSuccess()) {
            throw new ApiException('Taksit oranları hatası: ' . ($dto->errMsg ?? 'bilinmeyen'), $dto->errMsg, $json);
        }
        return $dto;
    }

    private function configToOptions(): Options
    {
        return (new Options())
            ->setMerchantId($this->config->merchantId)
            ->setMerchantKey($this->config->merchantKey)
            ->setMerchantSalt($this->config->merchantSalt);
    }
}
