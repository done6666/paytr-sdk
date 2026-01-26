<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Request;

use Done\PayTR\Exceptions\ValidationException;

/**
 * Iframe token isteği (get-token) için request DTO.
 * Property'ler camelCase; toArray() PayTR snake_case gönderir.
 *
 * @deprecated Request\Iframe\CreateTokenRequest ve Model\Buyer, Basket kullanın
 */
final class IframeTokenRequest
{
    public function __construct(
        public string $merchantId,
        public string $userIp,
        public string $merchantOid,
        public string $email,
        /** Tutar x 100 (örn. 34.56 TL = 3456) */
        public int $paymentAmount,
        /** base64(json_encode([[name, unitPrice, qty], ...])) */
        public string $userBasket,
        public int $noInstallment = 0,
        public int $maxInstallment = 0,
        public string $currency = 'TL',
        public string $testMode = '0',
        public string $userName = '',
        public string $userAddress = '',
        public string $userPhone = '',
        public string $merchantOkUrl = '',
        public string $merchantFailUrl = '',
        public int $debugOn = 0,
        public int $timeoutLimit = 30,
        public string $lang = 'tr',
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->merchantOid === '') {
            throw new ValidationException('merchant_oid zorunludur.');
        }
        if ($this->paymentAmount < 1) {
            throw new ValidationException('payment_amount en az 1 olmalıdır (örn. 0.01 TL = 1).');
        }
        if (strlen($this->userIp) > 39) {
            throw new ValidationException('user_ip en fazla 39 karakter olmalıdır.');
        }
    }

    /**
     * PayTR get-token POST body (snake_case).
     *
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        $arr = [
            'merchant_id' => $this->merchantId,
            'user_ip' => $this->userIp,
            'merchant_oid' => $this->merchantOid,
            'email' => $this->email,
            'payment_amount' => $this->paymentAmount,
            'user_basket' => $this->userBasket,
            'no_installment' => $this->noInstallment,
            'max_installment' => $this->maxInstallment,
            'currency' => $this->currency,
            'test_mode' => $this->testMode,
            'user_name' => $this->userName,
            'user_address' => $this->userAddress,
            'user_phone' => $this->userPhone,
            'merchant_ok_url' => $this->merchantOkUrl,
            'merchant_fail_url' => $this->merchantFailUrl,
            'debug_on' => $this->debugOn,
            'timeout_limit' => $this->timeoutLimit,
            'lang' => $this->lang,
        ];
        return $arr;
    }

    /**
     * Token hesaplamasında kullanılan alanlar (test_mode string).
     */
    public function getTestModeForHash(): string
    {
        return $this->testMode;
    }
}
