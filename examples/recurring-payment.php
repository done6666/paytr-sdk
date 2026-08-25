<?php

/**
 * Örnek 4 — Kayıtlı kartla tekrarlayan ödeme (abonelik / otomatik tahsilat)
 *
 * Çalıştırma:
 *   PAYTR_MERCHANT_ID=... PAYTR_MERCHANT_KEY=... PAYTR_MERCHANT_SALT=... \
 *   PAYTR_UTOKEN=... PAYTR_CTOKEN=... php examples/recurring-payment.php
 *
 * Ön koşullar:
 * - Kullanıcının kayıtlı kartı olmalı (utoken/ctoken daha önce callback ile alınmış olmalı,
 *   bkz. README "Kart Saklama API (CAPI)" bölümü).
 * - Tekrarlayan ödeme Non3D çalışır (`non_3d=1`, `recurring_payment=1` SDK tarafından set edilir).
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Done\PayTR\Adapters\GuzzleHttpClient;
use Done\PayTR\Client;
use Done\PayTR\Exceptions\PayTRException;
use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Request\CardStorage\RecurringPaymentRequest;
use Done\PayTR\Options;

$merchantId = getenv('PAYTR_MERCHANT_ID') ?: '';
$merchantKey = getenv('PAYTR_MERCHANT_KEY') ?: '';
$merchantSalt = getenv('PAYTR_MERCHANT_SALT') ?: '';
$utoken = getenv('PAYTR_UTOKEN') ?: '';
$ctoken = getenv('PAYTR_CTOKEN') ?: '';

if ($merchantId === '' || $merchantKey === '' || $merchantSalt === '' || $utoken === '' || $ctoken === '') {
    fwrite(STDERR, "Zorunlu ortam değişkenleri: PAYTR_MERCHANT_ID, PAYTR_MERCHANT_KEY, PAYTR_MERCHANT_SALT, PAYTR_UTOKEN, PAYTR_CTOKEN\n");
    exit(1);
}

$options = (new Options())
    ->setMerchantId($merchantId)
    ->setMerchantKey($merchantKey)
    ->setMerchantSalt($merchantSalt)
    ->setTestMode(false);

$client = new Client($options, GuzzleHttpClient::default());

$buyer = new Buyer(
    email: 'musteri@ornek.com',
    name: 'Ad Soyad',
    phone: '5551234567',
    ip: $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    addressLine: 'Teslimat adresi'
);

$basket = (new Basket())
    ->addItem(new BasketItem('Aylık abonelik', '99.00', 1));

$request = (new RecurringPaymentRequest())
    ->setMerchantOid('ABO-' . uniqid())
    ->setPaymentAmount('99.00')
    ->setBuyer($buyer)
    ->setBasket($basket)
    ->setOkUrl('https://siteniz.com/ok')
    ->setFailUrl('https://siteniz.com/fail')
    ->setUtoken($utoken)
    ->setCtoken($ctoken);

try {
    $response = $client->cardStorage()->recurringPayment($request);
} catch (PayTRException $e) {
    fwrite(STDERR, 'Hata: ' . $e->getMessage() . "\n");
    exit(1);
}

if ($response->isSuccess()) {
    echo "Tahsilat başarılı.\n";
} elseif ($response->isWaitCallback()) {
    echo "Sonuç callback ile netleşecek.\n";
} else {
    echo "Tahsilat başarısız.\n";
    // Devam eden bir işlem varsa daha sonra tekrar deneyin:
    if ($response->tryAgain === true) {
        echo "try_again=true: kısa süre sonra tekrar deneyin.\n";
    }
}
