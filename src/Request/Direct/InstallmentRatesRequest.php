<?php

declare(strict_types=1);

namespace Done\PayTR\Request\Direct;

use Done\PayTR\Options;
use Done\PayTR\Crypto\Signature;
use Done\PayTR\Exceptions\ValidationException;

/**
 * Direkt API taksit oranları sorgulama isteği.
 * POST https://www.paytr.com/odeme/taksit-oranlari — Doküman: taksit-sorgulama
 */
final class InstallmentRatesRequest
{
    private const MAX_REQUEST_ID_LENGTH = 32;

    private string $requestId = '';
    private int $singleRatio = 0;
    private int $abroadRatio = 0;

    /**
     * En fazla 32 karakter. Yanıtta aynı değer döner.
     */
    public function setRequestId(string $requestId): self
    {
        $this->requestId = $requestId;
        return $this;
    }

    /** single_ratio: 1 = mağaza tek çekim oranı */
    public function setSingleRatio(bool $enabled): self
    {
        $this->singleRatio = $enabled ? 1 : 0;
        return $this;
    }

    /** abroad_ratio: 1 = yurtdışı tek çekim oranı */
    public function setAbroadRatio(bool $enabled): self
    {
        $this->abroadRatio = $enabled ? 1 : 0;
        return $this;
    }

    /**
     * PayTR taksit-oranlari POST body.
     *
     * @return array<string, string|int>
     */
    public function toPayload(Options $options): array
    {
        if ($this->requestId === '') {
            throw new ValidationException('request_id zorunludur.');
        }
        if (strlen($this->requestId) > self::MAX_REQUEST_ID_LENGTH) {
            throw new ValidationException('request_id en fazla 32 karakter olmalıdır.');
        }

        $paytrToken = Signature::installmentRatesToken(
            $options->getMerchantKey(),
            $options->getMerchantSalt(),
            $options->getMerchantId(),
            $this->requestId
        );

        $payload = [
            'merchant_id' => $options->getMerchantId(),
            'request_id' => $this->requestId,
            'paytr_token' => $paytrToken,
        ];
        if ($this->singleRatio !== 0) {
            $payload['single_ratio'] = $this->singleRatio;
        }
        if ($this->abroadRatio !== 0) {
            $payload['abroad_ratio'] = $this->abroadRatio;
        }
        return $payload;
    }
}
