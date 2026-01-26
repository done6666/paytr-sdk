<?php

declare(strict_types=1);

namespace Done\PayTR;

/**
 * PayTR mağaza kimlik bilgileri.
 */
final class Config
{
    public function __construct(
        public string $merchantId,
        public string $merchantKey,
        public string $merchantSalt,
        public bool $testMode = false,
    ) {
        if ($merchantId === '' || $merchantKey === '' || $merchantSalt === '') {
            throw new \Done\PayTR\Exceptions\ValidationException('merchant_id, merchant_key ve merchant_salt zorunludur.');
        }
    }
}
