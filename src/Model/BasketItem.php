<?php

declare(strict_types=1);

namespace Done\PayTR\Model;

/**
 * Sepet kalemi: ürün adı, birim fiyat (örn. "18.00"), adet.
 */
final class BasketItem
{
    public function __construct(
        public string $name,
        public string $unitPrice,
        public int $quantity = 1,
    ) {
    }
}
