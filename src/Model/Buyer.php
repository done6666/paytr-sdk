<?php

declare(strict_types=1);

namespace Done\PayTR\Model;

/**
 * Alıcı bilgileri: e-posta, tam ad, telefon, adres, IP. PayTR iframe dokümanında ülke/şehir/posta kodu yok; adres tek metin alanıdır.
 */
final class Buyer
{
    public function __construct(
        public string $email,
        public string $name = '',
        public string $phone = '',
        public string $ip = '',
        public string $addressLine = '',
    ) {
    }

    public function setAddressLine(string $line): self
    {
        return new self(
            $this->email,
            $this->name,
            $this->phone,
            $this->ip,
            $line
        );
    }
}
