<?php

declare(strict_types=1);

namespace Done\PayTR\Request\CardStorage;

use Done\PayTR\Crypto\Signature;
use Done\PayTR\Exceptions\ValidationException;
use Done\PayTR\Options;

/**
 * Kart Saklama API — kayıtlı kart listesi (CAPI LIST).
 * POST /odeme/capi/list — Doküman: https://dev.paytr.com/direkt-api/kart-saklama-api/kayitli-kart-listesi
 */
final class ListCardsRequest
{
    private string $utoken = '';

    public function setUtoken(string $utoken): self
    {
        $this->utoken = $utoken;
        return $this;
    }

    /**
     * PayTR POST body (merchant_id, utoken, paytr_token).
     *
     * @return array<string, string>
     */
    public function toPayload(Options $options): array
    {
        if ($this->utoken === '') {
            throw new ValidationException('utoken zorunludur.');
        }

        $paytrToken = Signature::cardStorageListToken(
            $options->getMerchantKey(),
            $options->getMerchantSalt(),
            $this->utoken
        );

        return [
            'merchant_id' => $options->getMerchantId(),
            'utoken' => $this->utoken,
            'paytr_token' => $paytrToken,
        ];
    }
}
