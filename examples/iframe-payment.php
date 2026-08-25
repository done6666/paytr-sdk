<?php

/**
 * Örnek 1 — Iframe ile ödeme başlatma
 *
 * Çalıştırma:
 *   PAYTR_MERCHANT_ID=... PAYTR_MERCHANT_KEY=... PAYTR_MERCHANT_SALT=... php examples/iframe-payment.php
 *
 * Token üretir ve iframe HTML'ini ekrana basar.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Done\PayTR\Adapters\GuzzleHttpClient;
use Done\PayTR\Client;
use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Request\Iframe\CreateTokenRequest;
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
    ->setTestMode(true); // canlıya geçerken false yapın

$client = new Client($options, GuzzleHttpClient::default());

$buyer = new Buyer(
    email: 'musteri@ornek.com',
    name: 'Ad Soyad',
    phone: '5551234567',
    ip: $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    addressLine: 'Teslimat adresi'
);

$basket = (new Basket())
    ->addItem(new BasketItem('Ürün adı 1', '18.00', 1))
    ->addItem(new BasketItem('Ürün adı 2', '33.25', 2));

$request = (new CreateTokenRequest())
    ->setMerchantOid('SIPARIS-' . uniqid())
    ->setAmountFromTL('34.56') // 34.56 TL → 3456 kuruş olarak gönderilir
    ->setCurrency('TL')
    ->setBuyer($buyer)
    ->setBasket($basket)
    ->setOkUrl('https://siteniz.com/odeme-basarili')
    ->setFailUrl('https://siteniz.com/odeme-hata')
    ->setInstallmentPolicy(noInstallment: false, maxInstallment: 12);

try {
    $response = $client->iframe()->createToken($request);
} catch (\Done\PayTR\Exceptions\PayTRException $e) {
    fwrite(STDERR, 'Hata: ' . $e->getMessage() . "\n");
    exit(1);
}

$iframeSrc = $client->iframe()->iframeUrl($response->token);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>PayTR Iframe Ödeme</title>
</head>
<body>
<script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
<iframe src="<?= htmlspecialchars($iframeSrc) ?>" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%;"></iframe>
<script>iFrameResize({}, '#paytriframe');</script>
</body>
</html>

<!-- Not: Sipariş onayı/iptali callback endpoint'i üzerinden yapılır (bkz. examples/callback.php).
     merchant_ok_url / merchant_fail_url yalnızca müşteri yönlendirmesidir. -->
