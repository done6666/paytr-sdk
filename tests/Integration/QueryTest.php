<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Integration;

use Done\PayTR\Config;
use Done\PayTR\DTO\Request\QueryRequest;
use Done\PayTR\Resources\Query;
use Done\PayTR\Tests\FakeHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Durum sorgu akışı (FakeHttpClient ile).
 */
final class QueryTest extends TestCase
{
    public function test_query_returns_response_on_success(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{
            "status":"success",
            "net_tutar":"9.76",
            "kesinti_tutari":"0.24",
            "payment_amount":"10.8",
            "payment_total":"10.8",
            "payment_date":"2021-01-01",
            "currency":"TL",
            "taksit":"0",
            "kart_marka":"WORD",
            "masked_pan":"455359***6747",
            "odeme_tipi":"KART",
            "test_mode":"0",
            "returns":[]
        }');

        $config = new Config('100', 'key', 'salt');
        $resource = new Query($config, $fake);
        $request = new QueryRequest(merchantOid: 'ORD-999');

        $result = $resource->query($request);

        self::assertTrue($result->isSuccess());
        self::assertSame('10.8', $result->paymentAmount);
        self::assertSame('TL', $result->currency);
        self::assertSame('2021-01-01', $result->paymentDate);

        $reqs = $fake->getRequests();
        self::assertCount(1, $reqs);
        self::assertStringContainsString('durum-sorgu', $reqs[0]['url']);
        self::assertSame('100', $reqs[0]['body']['merchant_id']);
        self::assertSame('ORD-999', $reqs[0]['body']['merchant_oid']);
        self::assertArrayHasKey('paytr_token', $reqs[0]['body']);
    }

    public function test_query_throws_on_error_status(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"error","err_no":"004","err_msg":"merchant_oid ile basarili odeme bulunamadi"}');

        $config = new Config('100', 'key', 'salt');
        $resource = new Query($config, $fake);
        $request = new QueryRequest(merchantOid: 'INVALID');

        $this->expectException(\Done\PayTR\Exceptions\ApiException::class);
        $this->expectExceptionMessage('merchant_oid ile basarili odeme bulunamadi');
        $resource->query($request);
    }
}
