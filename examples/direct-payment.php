<?php

/**
 * Örnek 2 — Direkt API ile ödeme (sync_mode=1, Non3D)
 *
 * Çalıştırma:
 *   PAYTR_MERCHANT_ID=... PAYTR_MERCHANT_KEY=... PAYTR_MERCHANT_SALT=... php examples/direct-payment.php
 *
 * Non3D ve sync_mode yetkileri mağazanızda açık olmalıdır.
 * Test kartı numarası kullanın; gerçek kart bilgisi göndermeyin.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Done\PayTR\Adapters\GuzzleHttpClient;
use Done\PayTR\Client;
use Done\PayTR\Exceptions\PayTRException;
use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Model\Card;
use Done\PayTR\Request\Direct\CreatePaymentRequest;
use Done\PayTR\Options;

$merchantId = getenv('PAYTR_MERCHANT_ID') ?: '';
$merchantKey = getenv('PAYTR_MERCHANT_KEY') ?: '';
$merchantSalt = getenv('PAYTR_MERCHANT_SALT') ?: '';

if ($merchantId === '' || $merchantKey === '' || $merchantSalt === '') {
    fwrite(STDERR, "PAYTR_MERCHANT_ID, PAYTR_MERCHANT_KEY ve PAYTR_MERCHANT_SALT ortam değişkenlerini ayarlayın.\n");
    exit(1);
}

$options = (new Options())
    ->setMerchantId($merchantId)
    ->setMerchantKey($merchantKey)
    ->setMerchantSalt($merchantSalt)
    ->setTestMode(true);

$client = new Client($options, GuzzleHttpClient::default());

$buyer = new Buyer(
    email: 'musteri@ornek.com',
    name: 'Ad Soyad',
    phone: '5551234567',
    ip: $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    addressLine: 'Teslimat adresi'
);

$basket = (new Basket())
    ->addItem(new BasketItem('Ürün 1', '50.00', 1))
    ->addItem(new BasketItem('Ürün 2', '25.50', 2));

// PayTR test kartlarından biri; üretimde asla sabit kart göndermeyin
$card = new Card('TEST KARTI', '4111111111111111', '12', '30', '000');

$request = (new CreatePaymentRequest())
    ->setMerchantOid('DIR-' . uniqid())
    ->setPaymentAmount('101.00')
    ->setBuyer($buyer)
    ->setBasket($basket)
    ->setCard($card)
    ->setOkUrl('https://siteniz.com/basarili')
    ->setFailUrl('https://siteniz.com/hata')
    ->setInstallmentCount(0)
    ->setNon3d(true)
    ->setSyncMode(true);

try {
    $response = $client->directApi()->createPayment($request);
} catch (PayTRException $e) {
    fwrite(STDERR, 'Hata: ' . $e->getMessage() . "\n");
    exit(1);
}

if ($response->isSuccess()) {
    echo "Ödeme başarılı.\n";
} elseif ($response->isWaitCallback()) {
    // Nihai sonucu callback endpoint'inizden doğrulayın (bkz. examples/callback.php)
    echo "Sonuç callback üzerinden gelecek.\n";
} else {
    echo "Ödeme başarısız.\n";
}
