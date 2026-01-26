<?php

declare(strict_types=1);

namespace Done\PayTR\Model;

/**
 * Direkt API ödeme isteğinde kullanılan kart bilgileri.
 * Doküman: cc_owner, card_number, expiry_month, expiry_year, cvv.
 * Güvenlik: Bu veriler yalnızca ödeme isteği oluşturulurken kullanılmalı, loglanmamalıdır.
 */
final class Card
{
    public function __construct(
        public string $ccOwner,
        public string $cardNumber,
        public string $expiryMonth,
        public string $expiryYear,
        public string $cvv,
    ) {
    }
}
