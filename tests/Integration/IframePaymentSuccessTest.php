<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Integration;

use Done\PayTR\Config;
use Done\PayTR\DTO\Request\IframeTokenRequest;
use Done\PayTR\Resources\IframePayment;
use Done\PayTR\Tests\FakeHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Iframe token başarı akışı (FakeHttpClient ile).
 */
final class IframePaymentSuccessTest extends TestCase
{
    public function test_get_token_returns_token_on_success(): void
    {
        $expectedToken = '28cc613c3d7633cfa4ed0956fdf901e05cf9d9cc0c2ef8db54fa';
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"success","token":"' . $expectedToken . '"}');

        $config = new Config('100', 'key', 'salt');
        $resource = new IframePayment($config, $fake);

        $request = new IframeTokenRequest(
            merchantId: '100',
            userIp: '1.2.3.4',
            merchantOid: 'ORD-001',
            email: 'test@example.com',
            paymentAmount: 3456,
            userBasket: base64_encode(json_encode([['Ürün 1', '34.56', 1]]))
        );

        $response = $resource->getToken($request);

        self::assertTrue($response->isSuccess());
        self::assertSame($expectedToken, $response->token);
        self::assertSame('success', $response->status);

        $reqs = $fake->getRequests();
        self::assertCount(1, $reqs);
        self::assertStringContainsString('get-token', $reqs[0]['url']);
        self::assertArrayHasKey('paytr_token', $reqs[0]['body']);
        self::assertSame('100', $reqs[0]['body']['merchant_id']);
        self::assertSame(3456, $reqs[0]['body']['payment_amount']);
    }

    public function test_iframe_src_url(): void
    {
        $url = IframePayment::iframeSrcUrl('abc123');
        self::assertSame('https://www.paytr.com/odeme/guvenli/abc123', $url);
    }
}
