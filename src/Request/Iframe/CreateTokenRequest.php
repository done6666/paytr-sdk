<?php

declare(strict_types=1);

namespace Done\PayTR\Request\Iframe;

use Done\PayTR\Model\Basket;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Options;
use Done\PayTR\Crypto\Signature;
use Done\PayTR\Exceptions\ValidationException;

/**
 * Iframe token isteği; fluent setter ile hazırlanır, toPayload() PayTR snake_case üretir.
 */
final class CreateTokenRequest
{
    private string $merchantOid = '';
    private int $amountKurus = 0;
    private string $currency = 'TL';
    private ?Buyer $buyer = null;
    private ?Basket $basket = null;
    private string $okUrl = '';
    private string $failUrl = '';
    private int $noInstallment = 0;
    private int $maxInstallment = 0;
    private int $debugOn = 0;
    private int $timeoutLimit = 30;
    private string $lang = 'tr';

    public function setMerchantOid(string $merchantOid): self
    {
        $this->merchantOid = $merchantOid;
        return $this;
    }

    /**
     * TL cinsinden tutar. Yalnızca nokta ile ondalık: digits + isteğe bağlı "." + 1–2 hane (örn. "34.56").
     *
     * @throws ValidationException Virgül veya geçersiz format
     */
    public function setAmountFromTL(string $amountTL): self
    {
        if (!preg_match('/^\d+(\.\d{1,2})?$/', trim($amountTL))) {
            throw new ValidationException('setAmountFromTL yalnızca nokta ile ondalık kabul eder (örn. "34.56").');
        }
        $amount = (float) $amountTL;
        $this->amountKurus = (int) round($amount * 100);
        return $this;
    }

    public function setAmountKurus(int $amountKurus): self
    {
        $this->amountKurus = $amountKurus;
        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function setBuyer(Buyer $buyer): self
    {
        $this->buyer = $buyer;
        return $this;
    }

    public function setBasket(Basket $basket): self
    {
        $this->basket = $basket;
        return $this;
    }

    public function setOkUrl(string $url): self
    {
        $this->okUrl = $url;
        return $this;
    }

    public function setFailUrl(string $url): self
    {
        $this->failUrl = $url;
        return $this;
    }

    /**
     * @param bool $noInstallment Taksit yok (tek çekim) ise true
     * @param int  $maxInstallment En fazla taksit (0 = limit yok)
     */
    public function setInstallmentPolicy(bool $noInstallment, int $maxInstallment = 0): self
    {
        $this->noInstallment = $noInstallment ? 1 : 0;
        $this->maxInstallment = $maxInstallment;
        return $this;
    }

    public function setDebugOn(int $debugOn): self
    {
        $this->debugOn = $debugOn;
        return $this;
    }

    public function setTimeoutLimit(int $minutes): self
    {
        $this->timeoutLimit = $minutes;
        return $this;
    }

    public function setLang(string $lang): self
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * PayTR get-token POST body (snake_case) + paytr_token. Options'tan merchant bilgisi ve test_mode alınır.
     *
     * @return array<string, string|int>
     */
    public function toPayload(Options $options): array
    {
        if ($this->buyer === null) {
            throw new ValidationException('Buyer zorunludur.');
        }
        if ($this->buyer->addressLine === '') {
            throw new ValidationException('Buyer addressLine (adres) zorunludur.');
        }
        if ($this->basket === null || count($this->basket->getItems()) === 0) {
            throw new ValidationException('En az bir kalem içeren Basket zorunludur.');
        }
        if ($this->merchantOid === '') {
            throw new ValidationException('merchant_oid zorunludur.');
        }
        if ($this->amountKurus < 1) {
            throw new ValidationException('Tutar en az 1 kuruş olmalıdır.');
        }

        $userBasket = $this->basket->toEncodedUserBasket();
        $userIp = $this->buyer->ip !== '' ? $this->buyer->ip : '0.0.0.0';
        $testMode = $options->isTestMode() ? '1' : '0';

        $paytrToken = Signature::iframeToken(
            $options->getMerchantKey(),
            $options->getMerchantSalt(),
            $options->getMerchantId(),
            $userIp,
            $this->merchantOid,
            $this->buyer->email,
            $this->amountKurus,
            $userBasket,
            $this->noInstallment,
            $this->maxInstallment,
            $this->currency,
            $testMode
        );

        return [
            'merchant_id' => $options->getMerchantId(),
            'user_ip' => $userIp,
            'merchant_oid' => $this->merchantOid,
            'email' => $this->buyer->email,
            'payment_amount' => $this->amountKurus,
            'user_basket' => $userBasket,
            'no_installment' => $this->noInstallment,
            'max_installment' => $this->maxInstallment,
            'currency' => $this->currency,
            'test_mode' => $testMode,
            'user_name' => $this->buyer->name,
            'user_address' => $this->buyer->addressLine,
            'user_phone' => $this->buyer->phone,
            'merchant_ok_url' => $this->okUrl,
            'merchant_fail_url' => $this->failUrl,
            'debug_on' => $this->debugOn,
            'timeout_limit' => $this->timeoutLimit,
            'lang' => $this->lang,
            'paytr_token' => $paytrToken,
        ];
    }
}
