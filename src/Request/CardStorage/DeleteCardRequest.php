<?php

declare(strict_types=1);

namespace Done\PayTR\Request\CardStorage;

use Done\PayTR\Crypto\Signature;
use Done\PayTR\Exceptions\ValidationException;
use Done\PayTR\Options;

/**
 * Kart Saklama API — kayıtlı kart silme (CAPI DELETE).
 * POST /odeme/capi/delete — Doküman: https://dev.paytr.com/direkt-api/kart-saklama-api/kayitli-kart-silme
 */
final class DeleteCardRequest
{
    private string $utoken = '';
    private string $ctoken = '';

    public function setUtoken(string $utoken): self
    {
        $this->utoken = $utoken;
        return $this;
    }

    public function setCtoken(string $ctoken): self
    {
        $this->ctoken = $ctoken;
        return $this;
    }

    /**
     * PayTR POST body (merchant_id, utoken, ctoken, paytr_token).
     *
     * @return array<string, string>
     */
    public function toPayload(Options $options): array
    {
        if ($this->utoken === '') {
            throw new ValidationException('utoken zorunludur.');
        }
        if ($this->ctoken === '') {
            throw new ValidationException('ctoken zorunludur.');
        }

        $paytrToken = Signature::cardStorageDeleteToken(
            $options->getMerchantKey(),
            $options->getMerchantSalt(),
            $this->utoken,
            $this->ctoken
        );

        return [
            'merchant_id' => $options->getMerchantId(),
            'utoken' => $this->utoken,
            'ctoken' => $this->ctoken,
            'paytr_token' => $paytrToken,
        ];
    }
}
