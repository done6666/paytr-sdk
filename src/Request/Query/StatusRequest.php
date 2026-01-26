<?php

declare(strict_types=1);

namespace Done\PayTR\Request\Query;

use Done\PayTR\Exceptions\ValidationException;

/**
 * Durum sorgu isteği; fluent setter.
 */
final class StatusRequest
{
    private string $merchantOid = '';

    public function setMerchantOid(string $merchantOid): self
    {
        $this->merchantOid = $merchantOid;
        return $this;
    }

    public function getMerchantOid(): string
    {
        return $this->merchantOid;
    }

    /**
     * @throws ValidationException merchant_oid boşsa
     */
    public function validate(): void
    {
        if ($this->merchantOid === '') {
            throw new ValidationException('merchant_oid zorunludur.');
        }
    }
}
