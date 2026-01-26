<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Request;

use Done\PayTR\Exceptions\ValidationException;

/**
 * Durum sorgu isteği parametreleri.
 *
 * @deprecated Request\Query\StatusRequest kullanın
 */
final class QueryRequest
{
    public function __construct(
        public string $merchantOid,
    ) {
        if ($this->merchantOid === '') {
            throw new ValidationException('merchant_oid zorunludur.');
        }
    }

    /**
     * PayTR durum-sorgu POST body (snake_case). merchant_id ve paytr_token caller ekler.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'merchant_oid' => $this->merchantOid,
        ];
    }
}
