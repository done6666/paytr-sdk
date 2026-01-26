<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Integration;

use Done\PayTR\Config;
use Done\PayTR\Crypto\Signature;
use Done\PayTR\Resources\Callback;
use Done\PayTR\Tests\FakeHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Callback doğrulama ve parse testi.
 */
final class CallbackVerifyTest extends TestCase
{
    private const KEY = 'test_merchant_key';
    private const SALT = 'test_merchant_salt';

    public function test_verify_and_parse_returns_payload_on_valid_hash(): void
    {
        $merchantOid = 'ORD-123';
        $status = 'success';
        $totalAmount = '5000';
        $hashStr = $merchantOid . self::SALT . $status . $totalAmount;
        $hash = Signature::hmacBase64(self::KEY, $hashStr);

        $post = [
            'merchant_oid' => $merchantOid,
            'status' => $status,
            'total_amount' => $totalAmount,
            'hash' => $hash,
            'payment_type' => 'card',
            'currency' => 'TL',
            'payment_amount' => '5000',
        ];

        $config = new Config('1', self::KEY, self::SALT);
        $callback = new Callback($config);
        $payload = $callback->verifyAndParse($post);

        self::assertTrue($payload->isSuccess());
        self::assertSame($merchantOid, $payload->merchantOid);
        self::assertSame($totalAmount, $payload->totalAmount);
        self::assertSame('card', $payload->paymentType);
    }

    public function test_verify_throws_validation_exception_on_missing_field(): void
    {
        $post = ['merchant_oid' => 'ORD-1', 'status' => 'success'];
        $config = new Config('1', self::KEY, self::SALT);
        $callback = new Callback($config);
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('merchant_oid');
        $callback->verify($post);
    }

    public function test_verify_throws_on_tampered_hash(): void
    {
        $post = [
            'merchant_oid' => 'ORD-123',
            'status' => 'success',
            'total_amount' => '5000',
            'hash' => 'tampered_hash',
        ];

        $config = new Config('1', self::KEY, self::SALT);
        $callback = new Callback($config);

        $this->expectException(\Done\PayTR\Exceptions\SignatureException::class);
        $callback->verifyAndParse($post);
    }

    public function test_ok_returns_ok_string(): void
    {
        $config = new Config('1', self::KEY, self::SALT);
        $callback = new Callback($config);
        self::assertSame('OK', $callback->ok());
    }

    public function test_verify_alias_returns_same_as_verify_and_parse(): void
    {
        $merchantOid = 'ORD-456';
        $status = 'success';
        $totalAmount = '1000';
        $hashStr = $merchantOid . self::SALT . $status . $totalAmount;
        $hash = Signature::hmacBase64(self::KEY, $hashStr);
        $post = [
            'merchant_oid' => $merchantOid,
            'status' => $status,
            'total_amount' => $totalAmount,
            'hash' => $hash,
        ];
        $config = new Config('1', self::KEY, self::SALT);
        $callback = new Callback($config);
        $viaVerify = $callback->verify($post);
        $viaParse = $callback->verifyAndParse($post);
        self::assertSame($viaParse->merchantOid, $viaVerify->merchantOid);
        self::assertTrue($viaVerify->isSuccess());
    }
}
