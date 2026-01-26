<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Request\Direct;

use Done\PayTR\Options;
use Done\PayTR\Request\Direct\InstallmentRatesRequest;
use PHPUnit\Framework\TestCase;

final class InstallmentRatesRequestTest extends TestCase
{
    public function test_to_payload_includes_merchant_id_request_id_paytr_token(): void
    {
        $options = (new Options())->setMerchantId('100')->setMerchantKey('k')->setMerchantSalt('s');
        $req = (new InstallmentRatesRequest())->setRequestId('req-1');
        $payload = $req->toPayload($options);
        self::assertArrayHasKey('merchant_id', $payload);
        self::assertArrayHasKey('request_id', $payload);
        self::assertArrayHasKey('paytr_token', $payload);
        self::assertSame('req-1', $payload['request_id']);
    }

    public function test_throws_when_request_id_empty(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = new InstallmentRatesRequest();
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('request_id');
        $req->toPayload($options);
    }
}
