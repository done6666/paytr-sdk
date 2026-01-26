<?php

declare(strict_types=1);

namespace Done\PayTR\Resources;

use Done\PayTR\Config;
use Done\PayTR\Contracts\HttpClient;
use Done\PayTR\DTO\Response\CardStorage\DeleteCardResponse;
use Done\PayTR\DTO\Response\CardStorage\ListCardsResponse;
use Done\PayTR\DTO\Response\CardStorage\RecurringPaymentResponse;
use Done\PayTR\DTO\Response\DirectPaymentResponse;
use Done\PayTR\Exceptions\ApiException;
use Done\PayTR\Options;
use Done\PayTR\Request\CardStorage\AddCardRequest;
use Done\PayTR\Request\CardStorage\DeleteCardRequest;
use Done\PayTR\Request\CardStorage\ListCardsRequest;
use Done\PayTR\Request\CardStorage\PayWithRegisteredCardRequest;
use Done\PayTR\Request\CardStorage\RecurringPaymentRequest;

/**
 * PayTR Kart Saklama API (CAPI).
 * Doküman: https://dev.paytr.com/direkt-api/kart-saklama-api
 */
final class CardStorage
{
    private const PAYMENT_PATH = '/odeme';
    private const LIST_PATH = '/odeme/capi/list';
    private const DELETE_PATH = '/odeme/capi/delete';

    public function __construct(
        private Config $config,
        private HttpClient $httpClient,
        private ?Options $options = null
    ) {
    }

    /**
     * Ödeme sırasında yeni kart kaydetme (store_card=1). sync_mode=1 ile POST /odeme; JSON yanıt.
     * sync_mode=0 için toPayload() ile form POST edin; utoken/ctoken bildirim URL'den gelir.
     *
     * @throws ApiException PayTR status "failed" veya "error" dönerse
     * @throws \InvalidArgumentException Options yoksa veya sync_mode=0 ile çağrılırsa
     */
    public function addCard(AddCardRequest $request): DirectPaymentResponse
    {
        $options = $this->options ?? $this->configToOptions();
        $payload = $request->toPayload($options);
        if ((int)($payload['sync_mode'] ?? 0) !== 1) {
            throw new \InvalidArgumentException('addCard() HTTP çağrısı yalnızca sync_mode=1 ile kullanılır. sync_mode=0 için toPayload() alıp getPaymentFormUrl() adresine form POST edin.');
        }
        $url = rtrim($options->getBaseUrl(), '/') . self::PAYMENT_PATH;
        $response = $this->httpClient->post($url, $payload);
        $json = $response->getJson();
        if ($json === null || !is_array($json)) {
            throw new ApiException('Kart ekleme yanıtı geçerli JSON değil.', null, []);
        }
        $dto = DirectPaymentResponse::fromArray($json);
        if ($dto->isFailed()) {
            throw new ApiException('Kart ekleme hatası: ' . ($dto->msg ?: $dto->status), $dto->msg ?: $dto->status, $json);
        }
        return $dto;
    }

    /**
     * Kayıtlı kart ile ödeme. sync_mode=1 ile POST /odeme; JSON yanıt.
     *
     * @throws ApiException PayTR status "failed" veya "error" dönerse
     * @throws \InvalidArgumentException Options yoksa veya sync_mode=0 ile çağrılırsa
     */
    public function payWithRegisteredCard(PayWithRegisteredCardRequest $request): DirectPaymentResponse
    {
        $options = $this->options ?? $this->configToOptions();
        $payload = $request->toPayload($options);
        if ((int)($payload['sync_mode'] ?? 0) !== 1) {
            throw new \InvalidArgumentException('payWithRegisteredCard() HTTP çağrısı yalnızca sync_mode=1 ile kullanılır. sync_mode=0 için toPayload() alıp getPaymentFormUrl() adresine form POST edin.');
        }
        $url = rtrim($options->getBaseUrl(), '/') . self::PAYMENT_PATH;
        $response = $this->httpClient->post($url, $payload);
        $json = $response->getJson();
        if ($json === null || !is_array($json)) {
            throw new ApiException('Kayıtlı kart ödeme yanıtı geçerli JSON değil.', null, []);
        }
        $dto = DirectPaymentResponse::fromArray($json);
        if ($dto->isFailed()) {
            throw new ApiException('Kayıtlı kart ödeme hatası: ' . ($dto->msg ?: $dto->status), $dto->msg ?: $dto->status, $json);
        }
        return $dto;
    }

    /**
     * Kayıtlı kart listesi. POST /odeme/capi/list
     *
     * @throws ApiException status "error" dönerse
     */
    public function listCards(ListCardsRequest $request): ListCardsResponse
    {
        $options = $this->options ?? $this->configToOptions();
        $payload = $request->toPayload($options);
        $url = rtrim($options->getBaseUrl(), '/') . self::LIST_PATH;
        $response = $this->httpClient->post($url, $payload);
        $raw = $response->getBody();
        $decoded = $raw !== '' ? json_decode($raw, true) : null;
        if ($decoded === null && $raw !== '' && $raw !== '[]') {
            throw new ApiException('Kayıtlı kart listesi yanıtı geçerli JSON değil.', null, []);
        }
        $dto = ListCardsResponse::fromParsedResponse($decoded ?? []);
        if (!$dto->isSuccess()) {
            throw new ApiException('Kayıtlı kart listesi hatası: ' . $dto->getErrorMessage(), $dto->getErrorMessage(), is_array($decoded) ? $decoded : []);
        }
        return $dto;
    }

    /**
     * Kayıtlı kart silme. POST /odeme/capi/delete
     *
     * @throws ApiException status "error" dönerse
     */
    public function deleteCard(DeleteCardRequest $request): DeleteCardResponse
    {
        $options = $this->options ?? $this->configToOptions();
        $payload = $request->toPayload($options);
        $url = rtrim($options->getBaseUrl(), '/') . self::DELETE_PATH;
        $response = $this->httpClient->post($url, $payload);
        $json = $response->getJson();
        if ($json === null || !is_array($json)) {
            throw new ApiException('Kart silme yanıtı geçerli JSON değil.', null, []);
        }
        $dto = DeleteCardResponse::fromArray($json);
        if (!$dto->isSuccess()) {
            throw new ApiException('Kart silme hatası: ' . $dto->getErrorMessage(), $dto->getErrorMessage(), $json);
        }
        return $dto;
    }

    /**
     * Kayıtlı kart ile tekrarlayan ödeme. POST /odeme (recurring_payment=1); JSON yanıt.
     *
     * @throws ApiException status "failed" veya "error" dönerse
     */
    public function recurringPayment(RecurringPaymentRequest $request): RecurringPaymentResponse
    {
        $options = $this->options ?? $this->configToOptions();
        $payload = $request->toPayload($options);
        $url = rtrim($options->getBaseUrl(), '/') . self::PAYMENT_PATH;
        $response = $this->httpClient->post($url, $payload);
        $json = $response->getJson();
        if ($json === null || !is_array($json)) {
            throw new ApiException('Tekrarlayan ödeme yanıtı geçerli JSON değil.', null, []);
        }
        $dto = RecurringPaymentResponse::fromArray($json);
        if ($dto->isFailed()) {
            throw new ApiException('Tekrarlayan ödeme hatası: ' . $dto->getErrorMessage(), $dto->getErrorMessage(), $json);
        }
        return $dto;
    }

    /**
     * Ödeme formu action URL'i. sync_mode=0 ile addCard / payWithRegisteredCard için
     * toPayload() ile aldığınız veriyi bu adrese POST edin. Varsayılan: https://www.paytr.com/odeme
     */
    public function getPaymentFormUrl(): string
    {
        $options = $this->options ?? $this->configToOptions();
        return rtrim($options->getBaseUrl(), '/') . self::PAYMENT_PATH;
    }

    private function configToOptions(): Options
    {
        return (new Options())
            ->setMerchantId($this->config->merchantId)
            ->setMerchantKey($this->config->merchantKey)
            ->setMerchantSalt($this->config->merchantSalt)
            ->setTestMode($this->config->testMode);
    }
}
