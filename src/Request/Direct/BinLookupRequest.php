<?php

declare(strict_types=1);

namespace Done\PayTR\Request\Direct;

use Done\PayTR\Options;
use Done\PayTR\Crypto\Signature;
use Done\PayTR\Exceptions\ValidationException;

/**
 * Direkt API BIN sorgulama isteği.
 * POST https://www.paytr.com/odeme/api/bin-detail — Doküman: bin-sorgulama-servisi
 */
final class BinLookupRequest
{
    private string $binNumber = '';

    public function setBinNumber(string $binNumber): self
    {
        $this->binNumber = preg_replace('/\D/', '', $binNumber);
        return $this;
    }

    /**
     * PayTR bin-detail POST body.
     *
     * @return array<string, string>
     */
    public function toPayload(Options $options): array
    {
        if (strlen($this->binNumber) < 6 || strlen($this->binNumber) > 8) {
            throw new ValidationException('bin_number 6 veya 8 hane olmalıdır.');
        }

        $paytrToken = Signature::binDetailToken(
            $options->getMerchantKey(),
            $options->getMerchantSalt(),
            $options->getMerchantId(),
            $this->binNumber
        );

        return [
            'merchant_id' => $options->getMerchantId(),
            'bin_number' => $this->binNumber,
            'paytr_token' => $paytrToken,
        ];
    }
}
