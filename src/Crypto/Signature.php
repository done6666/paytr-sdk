<?php

declare(strict_types=1);

namespace Done\PayTR\Crypto;

use Done\PayTR\Exceptions\SignatureException;

/**
 * PayTR imza ve token üretimi / doğrulaması.
 * Tüm birleştirmelerde string kullanılır; hash_equals ile timing-safe karşılaştırma yapılır.
 */
final class Signature
{
    /**
     * Iframe token isteği için paytr_token üretir.
     * Doküman: hash_str = merchant_id + user_ip + merchant_oid + email + payment_amount + user_basket
     *          + no_installment + max_installment + currency + test_mode + merchant_salt
     *          paytr_token = base64(hmac_sha256(merchant_key, hash_str))
     */
    public static function iframeToken(
        string $merchantKey,
        string $merchantSalt,
        string $merchantId,
        string $userIp,
        string $merchantOid,
        string $email,
        int $paymentAmount,
        string $userBasket,
        int $noInstallment,
        int $maxInstallment,
        string $currency,
        string $testMode
    ): string {
        $hashStr = $merchantId
            . $userIp
            . $merchantOid
            . $email
            . (string) $paymentAmount
            . $userBasket
            . (string) $noInstallment
            . (string) $maxInstallment
            . $currency
            . $testMode
            . $merchantSalt;

        return self::hmacBase64($merchantKey, $hashStr);
    }

    /**
     * Callback POST'taki hash değerini doğrular.
     * Doküman: hash_str = merchant_oid + merchant_salt + status + total_amount
     */
    public static function verifyCallbackHash(
        string $merchantKey,
        string $merchantSalt,
        string $merchantOid,
        string $status,
        string $totalAmount,
        string $receivedHash
    ): void {
        $hashStr = $merchantOid . $merchantSalt . $status . $totalAmount;
        $expected = self::hmacBase64($merchantKey, $hashStr);

        if (!hash_equals($expected, $receivedHash)) {
            throw new SignatureException('Callback hash doğrulaması başarısız.');
        }
    }

    /**
     * Durum sorgu isteği için paytr_token üretir.
     * Doküman: hash_str = merchant_id + merchant_oid + merchant_salt
     */
    public static function queryToken(
        string $merchantKey,
        string $merchantSalt,
        string $merchantId,
        string $merchantOid
    ): string {
        $hashStr = $merchantId . $merchantOid . $merchantSalt;
        return self::hmacBase64($merchantKey, $hashStr);
    }

    /**
     * İade isteği için paytr_token üretir.
     * Doküman: hash_str = merchant_id + merchant_oid + return_amount + merchant_salt
     * return_amount string formatında (örn. "11.97")
     */
    public static function refundToken(
        string $merchantKey,
        string $merchantSalt,
        string $merchantId,
        string $merchantOid,
        string $returnAmount
    ): string {
        $hashStr = $merchantId . $merchantOid . $returnAmount . $merchantSalt;
        return self::hmacBase64($merchantKey, $hashStr);
    }

    /**
     * Direkt API ödeme isteği için paytr_token üretir.
     * hash_str = merchant_id + user_ip + merchant_oid + email + payment_amount + payment_type
     *            + installment_count + currency + test_mode + non_3d + merchant_salt
     * paytr_token = base64(hmac_sha256(merchant_key, hash_str))
     * payment_amount string formatında (örn. "100.99")
     */
    public static function directPaymentToken(
        string $merchantKey,
        string $merchantSalt,
        string $merchantId,
        string $userIp,
        string $merchantOid,
        string $email,
        string $paymentAmount,
        string $paymentType,
        string $installmentCount,
        string $currency,
        string $testMode,
        string $non3d
    ): string {
        $hashStr = $merchantId
            . $userIp
            . $merchantOid
            . $email
            . $paymentAmount
            . $paymentType
            . $installmentCount
            . $currency
            . $testMode
            . $non3d
            . $merchantSalt;

        return self::hmacBase64($merchantKey, $hashStr);
    }

    /**
     * Direkt API BIN sorgulama için paytr_token üretir.
     * Doküman: hash_str = bin_number + merchant_id + merchant_salt
     */
    public static function binDetailToken(
        string $merchantKey,
        string $merchantSalt,
        string $merchantId,
        string $binNumber
    ): string {
        $hashStr = $binNumber . $merchantId . $merchantSalt;
        return self::hmacBase64($merchantKey, $hashStr);
    }

    /**
     * Direkt API taksit oranları sorgulama için paytr_token üretir.
     * Doküman: hash_str = merchant_id + request_id + merchant_salt
     */
    public static function installmentRatesToken(
        string $merchantKey,
        string $merchantSalt,
        string $merchantId,
        string $requestId
    ): string {
        $hashStr = $merchantId . $requestId . $merchantSalt;
        return self::hmacBase64($merchantKey, $hashStr);
    }

    /**
     * Kart Saklama API — kayıtlı kart listesi (CAPI LIST) için paytr_token.
     * Doküman: hash_str = utoken + merchant_salt
     */
    public static function cardStorageListToken(
        string $merchantKey,
        string $merchantSalt,
        string $utoken
    ): string {
        $hashStr = $utoken . $merchantSalt;
        return self::hmacBase64($merchantKey, $hashStr);
    }

    /**
     * Kart Saklama API — kayıtlı kart silme (CAPI DELETE) için paytr_token.
     * Doküman: hash_str = ctoken + utoken + merchant_salt
     */
    public static function cardStorageDeleteToken(
        string $merchantKey,
        string $merchantSalt,
        string $utoken,
        string $ctoken
    ): string {
        $hashStr = $ctoken . $utoken . $merchantSalt;
        return self::hmacBase64($merchantKey, $hashStr);
    }

    /**
     * HMAC-SHA256 ile imza üretir ve base64 döner.
     */
    public static function hmacBase64(string $key, string $data): string
    {
        $raw = hash_hmac('sha256', $data, $key, true);
        return base64_encode($raw);
    }
}
