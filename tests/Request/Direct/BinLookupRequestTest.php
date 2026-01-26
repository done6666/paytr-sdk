<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Request\Direct;

use Done\PayTR\Options;
use Done\PayTR\Request\Direct\BinLookupRequest;
use PHPUnit\Framework\TestCase;

final class BinLookupRequestTest extends TestCase
{
    public function test_to_payload_includes_merchant_id_bin_number_paytr_token(): void
    {
        $options = (new Options())->setMerchantId('100')->setMerchantKey('k')->setMerchantSalt('s');
        $req = (new BinLookupRequest())->setBinNumber('411111');
        $payload = $req->toPayload($options);
        self::assertArrayHasKey('merchant_id', $payload);
        self::assertArrayHasKey('bin_number', $payload);
        self::assertArrayHasKey('paytr_token', $payload);
        self::assertSame('411111', $payload['bin_number']);
    }

    public function test_bin_number_strips_non_digits(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = (new BinLookupRequest())->setBinNumber('4111-11');
        $payload = $req->toPayload($options);
        self::assertSame('411111', $payload['bin_number']);
    }

    public function test_throws_when_bin_not_6_or_8_digits(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = (new BinLookupRequest())->setBinNumber('12345');
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('bin_number');
        $req->toPayload($options);
    }
}
