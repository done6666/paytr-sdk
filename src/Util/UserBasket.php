<?php

declare(strict_types=1);

namespace Done\PayTR\Util;

/**
 * PayTR user_basket formatı: base64(json_encode([[ürün_adı, birim_fiyat, adet], ...])).
 * Birim fiyat string veya number (örn. "18.00" veya 18.00).
 */
final class UserBasket
{
    /**
     * Sepet satırları: [ürünAdı, birimFiyat, adet], birimFiyat nokta ile (örn. "18.00").
     *
     * @param list<array{0: string, 1: string|float|int, 2: int}> $items
     */
    public static function encode(array $items): string
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [(string) $item[0], (string) $item[1], (int) $item[2]];
        }
        return base64_encode(json_encode($rows));
    }
}
