<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Model;

use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use PHPUnit\Framework\TestCase;

/**
 * Basket encode ve BasketItem testleri.
 */
final class BasketTest extends TestCase
{
    public function test_to_encoded_user_basket_format(): void
    {
        $basket = new Basket();
        $basket->addItem(new BasketItem('Ürün 1', '18.00', 1))
            ->addItem(new BasketItem('Ürün 2', '33.25', 2));

        $encoded = $basket->toEncodedUserBasket();
        self::assertNotEmpty($encoded);
        $decoded = json_decode(base64_decode($encoded, true), true);
        self::assertIsArray($decoded);
        self::assertCount(2, $decoded);
        self::assertSame(['Ürün 1', '18.00', 1], $decoded[0]);
        self::assertSame(['Ürün 2', '33.25', 2], $decoded[1]);
    }

    public function test_empty_basket_encodes_empty_array(): void
    {
        $basket = new Basket();
        $encoded = $basket->toEncodedUserBasket();
        $decoded = json_decode(base64_decode($encoded, true), true);
        self::assertSame([], $decoded);
    }
}
