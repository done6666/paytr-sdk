<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Request\CardStorage;

use Done\PayTR\Options;
use Done\PayTR\Request\CardStorage\ListCardsRequest;
use PHPUnit\Framework\TestCase;

final class ListCardsRequestTest extends TestCase
{
    public function test_to_payload_includes_merchant_id_utoken_paytr_token(): void
    {
        $options = (new Options())
            ->setMerchantId('100')
            ->setMerchantKey('key')
            ->setMerchantSalt('salt');
        $req = (new ListCardsRequest())->setUtoken('u_abc123');
        $payload = $req->toPayload($options);
        self::assertSame('100', $payload['merchant_id']);
        self::assertSame('u_abc123', $payload['utoken']);
        self::assertArrayHasKey('paytr_token', $payload);
        self::assertNotEmpty($payload['paytr_token']);
    }

    public function test_to_payload_throws_without_utoken(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = new ListCardsRequest();
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('utoken');
        $req->toPayload($options);
    }

    public function test_to_payload_token_uses_list_formula(): void
    {
        $options = (new Options())->setMerchantKey('mk')->setMerchantSalt('ms')->setMerchantId('1');
        $req = (new ListCardsRequest())->setUtoken('ut');
        $payload = $req->toPayload($options);
        $expected = \Done\PayTR\Crypto\Signature::cardStorageListToken('mk', 'ms', 'ut');
        self::assertSame($expected, $payload['paytr_token']);
    }
}
