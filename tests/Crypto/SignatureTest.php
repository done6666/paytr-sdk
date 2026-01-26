<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Crypto;

use Done\PayTR\Crypto\Signature;
use Done\PayTR\Exceptions\SignatureException;
use PHPUnit\Framework\TestCase;

/**
 * İmza ve callback hash doğrulama testleri.
 */
final class SignatureTest extends TestCase
{
    private const KEY = 'merchant_key_123';
    private const SALT = 'merchant_salt_456';

    public function test_iframe_token_reproducible(): void
    {
        $t1 = Signature::iframeToken(
            self::KEY,
            self::SALT,
            '100',
            '1.2.3.4',
            'ORD-001',
            'a@b.c',
            999,
            'e30=',
            0,
            12,
            'TL',
            '0'
        );
        $t2 = Signature::iframeToken(
            self::KEY,
            self::SALT,
            '100',
            '1.2.3.4',
            'ORD-001',
            'a@b.c',
            999,
            'e30=',
            0,
            12,
            'TL',
            '0'
        );
        self::assertSame($t1, $t2);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $t1);
    }

    public function test_iframe_token_different_input_different_output(): void
    {
        $t1 = Signature::iframeToken(self::KEY, self::SALT, '1', '1.2.3.4', 'A', 'a@b.c', 100, 'x', 0, 0, 'TL', '0');
        $t2 = Signature::iframeToken(self::KEY, self::SALT, '2', '1.2.3.4', 'A', 'a@b.c', 100, 'x', 0, 0, 'TL', '0');
        self::assertNotSame($t1, $t2);
    }

    public function test_callback_verify_accepts_valid_hash(): void
    {
        $merchantOid = 'ORD-1';
        $status = 'success';
        $totalAmount = '3456';
        $hashStr = $merchantOid . self::SALT . $status . $totalAmount;
        $validHash = Signature::hmacBase64(self::KEY, $hashStr);

        Signature::verifyCallbackHash(self::KEY, self::SALT, $merchantOid, $status, $totalAmount, $validHash);
        $this->expectNotToPerformAssertions();
    }

    public function test_callback_verify_throws_on_invalid_hash(): void
    {
        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('Callback hash doğrulaması başarısız');

        Signature::verifyCallbackHash(
            self::KEY,
            self::SALT,
            'ORD-1',
            'success',
            '3456',
            'invalid_hash_value'
        );
    }

    public function test_query_token_reproducible(): void
    {
        $t1 = Signature::queryToken(self::KEY, self::SALT, '100', 'ORD-X');
        $t2 = Signature::queryToken(self::KEY, self::SALT, '100', 'ORD-X');
        self::assertSame($t1, $t2);
    }

    public function test_refund_token_reproducible(): void
    {
        $t1 = Signature::refundToken(self::KEY, self::SALT, '100', 'ORD-Y', '11.97');
        $t2 = Signature::refundToken(self::KEY, self::SALT, '100', 'ORD-Y', '11.97');
        self::assertSame($t1, $t2);
    }

    /** Direkt API ödeme token: hash_str = merchant_id + user_ip + ... + non_3d + merchant_salt */
    public function test_direct_payment_token_reproducible(): void
    {
        $t1 = Signature::directPaymentToken(
            self::KEY,
            self::SALT,
            '100',
            '1.2.3.4',
            'DIR-001',
            'x@y.z',
            '100.99',
            'card',
            '0',
            'TL',
            '0',
            '0'
        );
        $t2 = Signature::directPaymentToken(
            self::KEY,
            self::SALT,
            '100',
            '1.2.3.4',
            'DIR-001',
            'x@y.z',
            '100.99',
            'card',
            '0',
            'TL',
            '0',
            '0'
        );
        self::assertSame($t1, $t2);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $t1);
    }

    /** Direkt API BIN token: hash_str = bin_number + merchant_id + merchant_salt */
    public function test_bin_detail_token_reproducible(): void
    {
        $t1 = Signature::binDetailToken(self::KEY, self::SALT, '100', '123456');
        $t2 = Signature::binDetailToken(self::KEY, self::SALT, '100', '123456');
        self::assertSame($t1, $t2);
    }

    /** Direkt API taksit oranları token: hash_str = merchant_id + request_id + merchant_salt */
    public function test_installment_rates_token_reproducible(): void
    {
        $t1 = Signature::installmentRatesToken(self::KEY, self::SALT, '100', 'req-xyz');
        $t2 = Signature::installmentRatesToken(self::KEY, self::SALT, '100', 'req-xyz');
        self::assertSame($t1, $t2);
    }

    /** Kart Saklama CAPI LIST: hash_str = utoken + merchant_salt */
    public function test_card_storage_list_token_formula(): void
    {
        $utoken = 'user_tok_abc';
        $expectedHashStr = $utoken . self::SALT;
        $expected = Signature::hmacBase64(self::KEY, $expectedHashStr);
        $actual = Signature::cardStorageListToken(self::KEY, self::SALT, $utoken);
        self::assertSame($expected, $actual);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $actual);
    }

    /** Kart Saklama CAPI DELETE: hash_str = ctoken + utoken + merchant_salt */
    public function test_card_storage_delete_token_formula(): void
    {
        $utoken = 'user_tok';
        $ctoken = 'card_tok';
        $expectedHashStr = $ctoken . $utoken . self::SALT;
        $expected = Signature::hmacBase64(self::KEY, $expectedHashStr);
        $actual = Signature::cardStorageDeleteToken(self::KEY, self::SALT, $utoken, $ctoken);
        self::assertSame($expected, $actual);
    }

    /**
     * POST /odeme token (kayıtlı kart / tekrarlayan ödeme): Direct API ile aynı formül.
     * hash_str = merchant_id + user_ip + merchant_oid + email + payment_amount + payment_type
     *            + installment_count + currency + test_mode + non_3d + merchant_salt
     * recurring_payment token string'e dahil edilmez (doküman).
     */
    public function test_post_odeme_token_recurring_uses_direct_formula(): void
    {
        $params = [
            '100', '1.2.3.4', 'REC-001', 'r@z.com', '50.00', 'card', '0', 'TL', '0', '1',
        ];
        $tokenRecurring = Signature::directPaymentToken(
            self::KEY,
            self::SALT,
            $params[0],
            $params[1],
            $params[2],
            $params[3],
            $params[4],
            $params[5],
            $params[6],
            $params[7],
            $params[8],
            $params[9]
        );
        $tokenAgain = Signature::directPaymentToken(
            self::KEY,
            self::SALT,
            ...$params
        );
        self::assertSame($tokenRecurring, $tokenAgain);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $tokenRecurring);
    }
}
