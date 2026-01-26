<?php

declare(strict_types=1);

namespace Done\PayTR\Request\CardStorage;

use Done\PayTR\Model\Basket;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Model\Card;
use Done\PayTR\Options;
use Done\PayTR\Crypto\Signature;
use Done\PayTR\Exceptions\ValidationException;

/**
 * Kart Saklama API — ödeme sırasında yeni kart kaydetme (CAPI PAYMENT + store_card).
 * POST /odeme — Aynı token formülü ve alan seti; store_card=1 ve isteğe bağlı utoken eklenir.
 * Doküman: https://dev.paytr.com/direkt-api/kart-saklama-api/yeni-kart-ekleme
 */
final class AddCardRequest
{
    private const PAYMENT_TYPE_CARD = 'card';
    private const VALID_INSTALLMENTS = [0, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    private string $merchantOid = '';
    private string $paymentAmount = '';
    private string $currency = 'TL';
    private ?Buyer $buyer = null;
    private ?Basket $basket = null;
    private ?Card $card = null;
    private string $okUrl = '';
    private string $failUrl = '';
    private int $installmentCount = 0;
    private int $non3d = 0;
    private int $testMode = 0;
    private string $cardType = '';
    private string $clientLang = 'tr';
    private int $debugOn = 0;
    private int $non3dTestFailed = 0;
    private int $syncMode = 0;
    private string $utoken = '';

    public function setMerchantOid(string $merchantOid): self
    {
        $this->merchantOid = $merchantOid;
        return $this;
    }

    public function setPaymentAmount(string $amount): self
    {
        $t = trim($amount);
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $t)) {
            throw new ValidationException('payment_amount yalnızca nokta ile ondalık kabul eder (örn. "100.99").');
        }
        $this->paymentAmount = $t;
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

    public function setCard(Card $card): self
    {
        $this->card = $card;
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

    public function setInstallmentCount(int $count): self
    {
        if (!in_array($count, self::VALID_INSTALLMENTS, true)) {
            throw new ValidationException('installment_count 0, 2–12 arası olmalıdır.');
        }
        $this->installmentCount = $count;
        return $this;
    }

    public function setNon3d(bool $enabled): self
    {
        $this->non3d = $enabled ? 1 : 0;
        return $this;
    }

    public function setTestMode(bool $enabled): self
    {
        $this->testMode = $enabled ? 1 : 0;
        return $this;
    }

    public function setCardType(string $cardType): self
    {
        $this->cardType = $cardType;
        return $this;
    }

    public function setClientLang(string $lang): self
    {
        $this->clientLang = $lang;
        return $this;
    }

    public function setDebugOn(int $debugOn): self
    {
        $this->debugOn = $debugOn;
        return $this;
    }

    public function setNon3dTestFailed(int $value): self
    {
        $this->non3dTestFailed = $value;
        return $this;
    }

    public function setSyncMode(bool $enabled): self
    {
        $this->syncMode = $enabled ? 1 : 0;
        return $this;
    }

    /**
     * Mevcut kullanıcıya yeni kart eklenirken, CAPI bildiriminde dönen utoken ile eşleştirilmiş değer.
     */
    public function setUtoken(string $utoken): self
    {
        $this->utoken = $utoken;
        return $this;
    }

    /**
     * PayTR /odeme POST body; store_card=1 ve isteğe bağlı utoken dahil.
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
        if ($this->card === null) {
            throw new ValidationException('Card zorunludur.');
        }
        if ($this->merchantOid === '') {
            throw new ValidationException('merchant_oid zorunludur.');
        }
        if ($this->paymentAmount === '' || (float) $this->paymentAmount <= 0) {
            throw new ValidationException('payment_amount zorunlu ve pozitif olmalıdır.');
        }
        if ($this->okUrl === '' || $this->failUrl === '') {
            throw new ValidationException('merchant_ok_url ve merchant_fail_url zorunludur.');
        }

        $userIp = $this->buyer->ip !== '' ? $this->buyer->ip : '0.0.0.0';
        $testModeStr = $options->isTestMode() ? '1' : '0';
        $non3dStr = (string) $this->non3d;
        $installmentCountStr = (string) $this->installmentCount;

        $paytrToken = Signature::directPaymentToken(
            $options->getMerchantKey(),
            $options->getMerchantSalt(),
            $options->getMerchantId(),
            $userIp,
            $this->merchantOid,
            $this->buyer->email,
            $this->paymentAmount,
            self::PAYMENT_TYPE_CARD,
            $installmentCountStr,
            $this->currency,
            $testModeStr,
            $non3dStr
        );

        $payload = [
            'merchant_id' => $options->getMerchantId(),
            'paytr_token' => $paytrToken,
            'user_ip' => $userIp,
            'merchant_oid' => $this->merchantOid,
            'email' => $this->buyer->email,
            'payment_type' => self::PAYMENT_TYPE_CARD,
            'payment_amount' => $this->paymentAmount,
            'installment_count' => $this->installmentCount,
            'currency' => $this->currency,
            'test_mode' => $testModeStr,
            'non_3d' => $non3dStr,
            'cc_owner' => $this->card->ccOwner,
            'card_number' => $this->card->cardNumber,
            'expiry_month' => $this->card->expiryMonth,
            'expiry_year' => $this->card->expiryYear,
            'cvv' => $this->card->cvv,
            'merchant_ok_url' => $this->okUrl,
            'merchant_fail_url' => $this->failUrl,
            'user_name' => $this->buyer->name,
            'user_address' => $this->buyer->addressLine,
            'user_phone' => $this->buyer->phone,
            'user_basket' => $this->basket->toDirectApiUserBasket(),
            'client_lang' => $this->clientLang,
            'debug_on' => (string) $this->debugOn,
            'non3d_test_failed' => (string) $this->non3dTestFailed,
            'sync_mode' => (string) $this->syncMode,
            'store_card' => '1',
        ];

        if ($this->cardType !== '') {
            $payload['card_type'] = $this->cardType;
        }
        if ($this->utoken !== '') {
            $payload['utoken'] = $this->utoken;
        }

        return $payload;
    }
}
