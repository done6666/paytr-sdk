<?php

declare(strict_types=1);

namespace Done\PayTR\Request\Refund;

use Done\PayTR\Exceptions\ValidationException;

/**
 * İade isteği; fluent setter. returnAmount nokta ile ondalık (örn. "11.97").
 */
final class RefundRequest
{
    private string $merchantOid = '';
    private string $returnAmount = '';
    private ?string $referenceNo = null;

    public function setMerchantOid(string $merchantOid): self
    {
        $this->merchantOid = $merchantOid;
        return $this;
    }

    public function setReturnAmount(string $returnAmount): self
    {
        $this->returnAmount = $returnAmount;
        return $this;
    }

    public function setReferenceNo(?string $referenceNo): self
    {
        $this->referenceNo = $referenceNo;
        return $this;
    }

    public function getMerchantOid(): string
    {
        return $this->merchantOid;
    }

    public function getReturnAmount(): string
    {
        return $this->returnAmount;
    }

    public function getReferenceNo(): ?string
    {
        return $this->referenceNo;
    }

    /**
     * @throws ValidationException Eksik/geçersiz alan varsa
     */
    public function validate(): void
    {
        if ($this->merchantOid === '') {
            throw new ValidationException('merchant_oid zorunludur.');
        }
        if ($this->returnAmount === '' || (float) str_replace(',', '.', $this->returnAmount) <= 0) {
            throw new ValidationException('return_amount geçerli bir tutar olmalıdır.');
        }
        if ($this->referenceNo !== null && strlen($this->referenceNo) > 64) {
            throw new ValidationException('reference_no en fazla 64 karakter olmalıdır.');
        }
    }
}
