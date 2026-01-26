<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Integration;

use Done\PayTR\Config;
use Done\PayTR\DTO\Request\IframeTokenRequest;
use Done\PayTR\Exceptions\ApiException;
use Done\PayTR\Resources\IframePayment;
use Done\PayTR\Tests\FakeHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * PayTR "failed" yanıtında ApiException ve reason/payload testi.
 */
final class IframePaymentApiExceptionTest extends TestCase
{
    public function test_failed_response_throws_api_exception_with_reason_and_payload(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"failed","reason":"Zorunlu alan degeri gecersiz: merchant_id"}');

        $config = new Config('x', 'key', 'salt');
        $resource = new IframePayment($config, $fake);

        $request = new IframeTokenRequest(
            merchantId: 'x',
            userIp: '1.2.3.4',
            merchantOid: 'ORD-1',
            email: 'a@b.c',
            paymentAmount: 100,
            userBasket: 'e30='
        );

        try {
            $resource->getToken($request);
            $this->fail('ApiException bekleniyordu.');
        } catch (ApiException $e) {
            self::assertStringContainsString('Zorunlu alan', $e->getMessage());
            self::assertSame('Zorunlu alan degeri gecersiz: merchant_id', $e->getReason());
            self::assertSame('failed', $e->getPayload()['status'] ?? null);
            self::assertArrayHasKey('reason', $e->getPayload());
        }
    }
}
