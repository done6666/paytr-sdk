<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Request;

use Done\PayTR\Exceptions\ValidationException;

/**
 * İade isteği parametreleri.
 * return_amount nokta ile ondalık (örn. "11.97").
 *
 * @deprecated Request\Refund\RefundRequest kullanın
 */
final class RefundRequest
{
    public function __construct(
        public string $merchantOid,
        /** İade tutarı, nokta ile (örn. "11.97") */
        public string $returnAmount,
        public ?string $referenceNo = null,
    ) {
        if ($this->merchantOid === '') {
            throw new ValidationException('merchant_oid zorunludur.');
        }
        if ($this->returnAmount === '' || (float) $this->returnAmount <= 0) {
            throw new ValidationException('return_amount geçerli bir tutar olmalıdır.');
        }
        if ($this->referenceNo !== null && strlen($this->referenceNo) > 64) {
            throw new ValidationException('reference_no en fazla 64 karakter olmalıdır.');
        }
    }

    /**
     * PayTR iade POST body (snake_case). merchant_id ve paytr_token caller ekler.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $arr = [
            'merchant_oid' => $this->merchantOid,
            'return_amount' => $this->returnAmount,
        ];
        if ($this->referenceNo !== null) {
            $arr['reference_no'] = $this->referenceNo;
        }
        return $arr;
    }
}
