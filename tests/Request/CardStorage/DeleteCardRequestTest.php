<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Request\CardStorage;

use Done\PayTR\Options;
use Done\PayTR\Request\CardStorage\DeleteCardRequest;
use PHPUnit\Framework\TestCase;

final class DeleteCardRequestTest extends TestCase
{
    public function test_to_payload_includes_merchant_id_utoken_ctoken_paytr_token(): void
    {
        $options = (new Options())
            ->setMerchantId('100')
            ->setMerchantKey('key')
            ->setMerchantSalt('salt');
        $req = (new DeleteCardRequest())->setUtoken('u_xyz')->setCtoken('c_456');
        $payload = $req->toPayload($options);
        self::assertSame('100', $payload['merchant_id']);
        self::assertSame('u_xyz', $payload['utoken']);
        self::assertSame('c_456', $payload['ctoken']);
        self::assertArrayHasKey('paytr_token', $payload);
        self::assertNotEmpty($payload['paytr_token']);
    }

    public function test_to_payload_throws_without_utoken(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = (new DeleteCardRequest())->setCtoken('c1');
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('utoken');
        $req->toPayload($options);
    }

    public function test_to_payload_throws_without_ctoken(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = (new DeleteCardRequest())->setUtoken('u1');
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('ctoken');
        $req->toPayload($options);
    }

    public function test_to_payload_token_uses_delete_formula(): void
    {
        $options = (new Options())->setMerchantKey('mk')->setMerchantSalt('ms')->setMerchantId('1');
        $req = (new DeleteCardRequest())->setUtoken('ut')->setCtoken('ct');
        $payload = $req->toPayload($options);
        $expected = \Done\PayTR\Crypto\Signature::cardStorageDeleteToken('mk', 'ms', 'ut', 'ct');
        self::assertSame($expected, $payload['paytr_token']);
    }
}
