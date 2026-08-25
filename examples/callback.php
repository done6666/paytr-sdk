<?php

/**
 * Örnek 3 — Bildirim URL (callback) endpoint'i
 *
 * PayTR mağaza panelinde Bildirim URL olarak bu script'i gösterin:
 *   https://siteniz.com/paytr/callback.php
 *
 * Kurallar:
 * - Yanıt olarak YALNIZCA düz metin "OK" yazdırılmalıdır (başarılı doğrulamada).
 * - Geçersiz hash durumunda HTTP 400 döndürün.
 * - Aynı merchant_oid için bildirim birden fazla kez gelebilir; idempotent işleyin.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Done\PayTR\Adapters\GuzzleHttpClient;
use Done\PayTR\Client;
use Done\PayTR\Exceptions\SignatureException;
use Done\PayTR\Options;

$merchantId = getenv('PAYTR_MERCHANT_ID') ?: '';
$merchantKey = getenv('PAYTR_MERCHANT_KEY') ?: '';
$merchantSalt = getenv('PAYTR_MERCHANT_SALT') ?: '';

if ($merchantId === '' || $merchantKey === '' || $merchantSalt === '') {
    http_response_code(500);
    exit('Sunucu yapılandırması eksik.');
}

$options = (new Options())
    ->setMerchantId($merchantId)
    ->setMerchantKey($merchantKey)
    ->setMerchantSalt($merchantSalt)
    ->setTestMode(false); // callback doğrulamasında test_mode önemi yoktur; ortamınıza göre ayarlayın

$client = new Client($options, GuzzleHttpClient::default());

try {
    $notification = $client->callback()->verify($_POST);
} catch (SignatureException $e) {
    http_response_code(400);
    exit('Geçersiz bildirim.');
}

// TODO: Kendi sipariş iş mantığınızı burada çalıştırın.
// Idempotency için önce siparişin mevcut durumunu DB'den kontrol edin:
//
// $order = $db->findOrderByOid($notification->merchantOid);
// if ($order !== null && $order->isFinalized()) { echo $client->callback()->ok(); exit; }

if ($notification->isSuccess()) {
    // siparisOnayla($notification->merchantOid, $notification->totalAmount);
} else {
    // siparisIptalEt($notification->merchantOid, $notification->failedReasonCode, $notification->failedReasonMsg);
}

echo $client->callback()->ok();
exit;
