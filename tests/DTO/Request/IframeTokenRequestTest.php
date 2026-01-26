<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\DTO\Request;

use Done\PayTR\DTO\Request\IframeTokenRequest;
use Done\PayTR\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * IframeTokenRequest toArray ve validasyon testleri.
 */
final class IframeTokenRequestTest extends TestCase
{
    public function test_to_array_produces_snake_case(): void
    {
        $r = new IframeTokenRequest(
            merchantId: '100',
            userIp: '127.0.0.1',
            merchantOid: 'ORD-1',
            email: 'a@b.c',
            paymentAmount: 999,
            userBasket: 'e30='
        );
        $arr = $r->toArray();
        self::assertArrayHasKey('merchant_id', $arr);
        self::assertArrayHasKey('user_ip', $arr);
        self::assertArrayHasKey('merchant_oid', $arr);
        self::assertArrayHasKey('payment_amount', $arr);
        self::assertArrayHasKey('user_basket', $arr);
        self::assertSame('100', $arr['merchant_id']);
        self::assertSame(999, $arr['payment_amount']);
        self::assertSame('ORD-1', $arr['merchant_oid']);
    }

    public function test_empty_merchant_oid_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('merchant_oid');

        new IframeTokenRequest(
            merchantId: '1',
            userIp: '1.2.3.4',
            merchantOid: '',
            email: 'a@b.c',
            paymentAmount: 100,
            userBasket: 'e30='
        );
    }

    public function test_payment_amount_zero_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('payment_amount');

        new IframeTokenRequest(
            merchantId: '1',
            userIp: '1.2.3.4',
            merchantOid: 'X',
            email: 'a@b.c',
            paymentAmount: 0,
            userBasket: 'e30='
        );
    }
}
