# done6666/paytr-sdk

[![CI](https://github.com/done6666/paytr-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/done6666/paytr-sdk/actions/workflows/ci.yml)
[![Static Analysis](https://github.com/done6666/paytr-sdk/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/done6666/paytr-sdk/actions/workflows/static-analysis.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/done6666/paytr-sdk)](https://packagist.org/packages/done6666/paytr-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/done6666/paytr-sdk)](https://packagist.org/packages/done6666/paytr-sdk)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/done6666/paytr-sdk)](https://packagist.org/packages/done6666/paytr-sdk)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![codecov](https://codecov.io/gh/done6666/paytr-sdk/graph/badge.svg)](https://codecov.io/gh/done6666/paytr-sdk)

**Türkçe** | [English](README.en.md)

PayTR ödeme altyapısı için framework bağımsız PHP SDK. **PSR-4**, **PSR-12**, **PHP 8.0+**.

* Iframe (Token + iframe URL)
* Direkt API (Ödeme, BIN, Taksit Oranları)
* Kart Saklama API (CAPI) (Yeni kart ekleme, kayıtlı karttan ödeme, kart listesi, kart silme, tekrarlayan ödeme)
* Callback doğrulama (Iframe + Direkt API + CAPI ortak bildirim)
* Durum sorgulama
* İade

## Neden bu SDK?

- **Framework bağımsız** — Laravel, Symfony veya düz PHP; hiçbirine bağımlılık zorunluluğu yok.
- **Değiştirilebilir HTTP katmanı** — Guzzle adapter hazır; `Contracts\HttpClient` arayüzüyle kendi client'ınızı bağlayın.
- **Tip güvenli ve fluent** — Request/Model sınıflarıyla IDE autocomplete dostu, doğrulanabilir istekler.
- **Hash/signature karmaşası yok** — PayTR'nin tüm imza formülleri SDK içinde; callback doğrulaması tek satır.
- **Test edilebilir** — `FakeHttpClient` ile gerçek API çağrısı yapmadan test; %100 CI kapsamında geliştirilir.
- **Kuruş/TL dönüşümü dahili** — `setAmountFromTL('34.56')` → `3456` kuruş; en klasik entegrasyon hatasını engeller.

## İçindekiler

- [Kurulum](#kurulum)
- [Hızlı Başlangıç](#hızlı-başlangıç)
- [Framework Entegrasyonu](#framework-entegrasyonu)
- [Önemli Notlar](#önemli-notlar)
- [Desteklenen Entegrasyonlar](#desteklenen-entegrasyonlar)
- [1) Iframe ile ödeme başlatma (token)](#1-iframe-ile-ödeme-başlatma-token)
- [1b) Direkt API](#1b-direkt-api)
- [1c) Kart Saklama API (CAPI)](#1c-kart-saklama-api-capi)
- [2) Bildirim URL (Callback) doğrulama](#2-bildirim-url-callback-doğrulama)
- [3) Durum sorgulama](#3-durum-sorgulama)
- [4) İade](#4-iade)
- [Hata Yönetimi](#hata-yönetimi)
- [Geriye Dönük Uyumluluk](#geriye-dönük-uyumluluk)
- [Örnekler](#örnekler)
- [Test](#test)
- [Katkı](#katkı)
- [Güvenlik](#güvenlik)
- [Lisans](#lisans)

---

## Kurulum

```bash
composer require done6666/paytr-sdk
```

HTTP istekleri için Guzzle adapter (opsiyonel):

```bash
composer require guzzlehttp/guzzle
```

---

## Hızlı Başlangıç

Önerilen kurulum `Options` ile yapılır. (`Options` fluent builder'dır; ileri kullanımda `Config` de kullanılabilir.)

```php
use Done\PayTR\Options;
use Done\PayTR\Client;
use Done\PayTR\Adapters\GuzzleHttpClient;

$options = (new Options())
    ->setMerchantId('MAĞAZA_NO')
    ->setMerchantKey('MAĞAZA_PAROLA')
    ->setMerchantSalt('MAĞAZA_GİZLİ_ANAHTAR')
    ->setTestMode(true);

$client = new Client($options, GuzzleHttpClient::default());
```

Kendi HTTP client'ınız için `Done\PayTR\Contracts\HttpClient` arayüzünü implement edebilirsiniz.

---

## Framework Entegrasyonu

### Laravel

`config/services.php`:

```php
'paytr' => [
    'merchant_id'  => env('PAYTR_MERCHANT_ID'),
    'merchant_key' => env('PAYTR_MERCHANT_KEY'),
    'merchant_salt'=> env('PAYTR_MERCHANT_SALT'),
],
```

`AppServiceProvider@register` içinde singleton olarak kaydedin:

```php
use Done\PayTR\Client;
use Done\PayTR\Adapters\GuzzleHttpClient;
use Done\PayTR\Options;

$this->app->singleton(Client::class, function () {
    $options = (new Options())
        ->setMerchantId(config('services.paytr.merchant_id'))
        ->setMerchantKey(config('services.paytr.merchant_key'))
        ->setMerchantSalt(config('services.paytr.merchant_salt'));

    return new Client($options, GuzzleHttpClient::default());
});
```

Controller'da method injection ile kullanın:

```php
public function pay(Request $request, Client $paytr)
{
    // $paytr->iframe()->createToken(...)
}
```

### Symfony

`config/services.yaml`:

```yaml
Done\PayTR\Options:
    class: Done\PayTR\Options
    calls:
        - setMerchantId: ['%env(PAYTR_MERCHANT_ID)%']
        - setMerchantKey: ['%env(PAYTR_MERCHANT_KEY)%']
        - setMerchantSalt: ['%env(PAYTR_MERCHANT_SALT)%']

Done\PayTR\Adapters\GuzzleHttpClient:
    factory: ['Done\PayTR\Adapters\GuzzleHttpClient', 'default']

Done\PayTR\Client:
    arguments:
        $configOrOptions: '@Done\PayTR\Options'
        $httpClient: '@Done\PayTR\Adapters\GuzzleHttpClient'
```

---

## Önemli Notlar

### 1) user_ip (kritik)

Direkt API / Kart Saklama API çağrılarında `user_ip` alanı **mutlaka gerçek müşteri IP'si** olmalıdır.
Örneğin reverse proxy arkasındaysanız doğru header'dan almayı unutmayın.

### 2) Idempotency (kritik)

PayTR bildirimleri (callback) aynı `merchant_oid` için birden fazla kez gelebilir. Siparişinizi **idempotent** işleyin:

* Sipariş daha önce "paid/failed" durumuna alınmışsa tekrar işlemeyin.
* Önce DB'de sipariş durumunu kontrol edin.

### 3) sync_mode farkı

* **sync_mode=0 (varsayılan):** SDK payload üretir, siz HTML form ile PayTR'ye POST edersiniz.
* **sync_mode=1:** SDK HTTP çağrısını kendi yapar (JSON yanıt alırsınız). Bunun için mağazada ilgili yetkilerin açık olması gerekir.

### 4) Kart verisi güvenliği

Ham kart numarası / CVV / tam kart bilgisini:

* loglamayın,
* gereksiz yere saklamayın,
* HTTPS kullanın,
* PCI-DSS yükümlülüklerinizi değerlendirin.

---

## Desteklenen Entegrasyonlar

* **Iframe API**

  * Token oluşturma (ödeme formu başlatma)
* **Direkt API**

  * Ödeme isteği
  * BIN sorgulama
  * Taksit oranları
* **Kart Saklama API (CAPI)**

  * Yeni kart ekleme (ödeme sırasında, `store_card=1`)
  * Kayıtlı karttan ödeme
  * Kayıtlı kart listesi (CAPI LIST)
  * Kayıtlı kart silme (CAPI DELETE)
  * Kayıtlı kartla tekrarlayan ödeme (`recurring_payment=1`)
* **Callback doğrulama**

  * Iframe + Direkt API + CAPI aynı doğrulama ile çalışır
* **Durum sorgulama**
* **İade**

---

## 1) Iframe ile ödeme başlatma (token)

`Buyer`, `Basket`, `CreateTokenRequest` ile token alıp iframe URL'i üretin.

> PayTR `payment_amount` alanını **kuruş** bazlı kullanır.
> SDK'da:
>
> * `setAmountFromTL('34.56')` → SDK 3456 olarak gönderir
> * direkt kuruş için `setAmountKurus(3456)`

```php
use Done\PayTR\Model\Buyer;
use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Request\Iframe\CreateTokenRequest;

$buyer = new Buyer(
    email: 'musteri@ornek.com',
    name: 'Ad Soyad',
    phone: '5551234567',
    ip: $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
    addressLine: 'Teslimat adresi'
);

$basket = (new Basket())
    ->addItem(new BasketItem('Ürün adı 1', '18.00', 1))
    ->addItem(new BasketItem('Ürün adı 2', '33.25', 2));

$request = (new CreateTokenRequest())
    ->setMerchantOid('SIPARIS-' . uniqid())
    ->setAmountFromTL('34.56')
    ->setCurrency('TL')
    ->setBuyer($buyer)
    ->setBasket($basket)
    ->setOkUrl('https://siteniz.com/odeme-basarili')
    ->setFailUrl('https://siteniz.com/odeme-hata')
    ->setInstallmentPolicy(noInstallment: false, maxInstallment: 12);

$response = $client->iframe()->createToken($request);
$iframeSrc = $client->iframe()->iframeUrl($response->token);
```

HTML iframe:

```html
<script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
<iframe src="<?= htmlspecialchars($iframeSrc) ?>" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%;"></iframe>
<script>iFrameResize({}, '#paytriframe');</script>
```

> Sipariş onay/iptal işlemleri **bildirim URL** (callback) üzerinden yapılmalıdır.
> `merchant_ok_url` / `merchant_fail_url` yalnızca müşteri yönlendirmesidir.

---

## 1b) Direkt API

Direkt API'de ödeme formu sizin sunucunuzdadır; kart bilgileri PayTR'ye gönderilir.

### Akışlar

* **sync_mode=0 (varsayılan)**

  * `$request->toPayload($options)` ile payload alırsınız.
  * Kendi HTML formunuzdan `https://www.paytr.com/odeme` adresine POST edersiniz.
  * Sonuç, PayTR'nin yönlendirdiği `merchant_ok_url` / `merchant_fail_url` sayfalarında görülür.
* **sync_mode=1**

  * SDK `createPayment()` ile POST atar.
  * JSON yanıt döner: `success | wait_callback | failed`
  * Mağazada Non3D ve sync yetkisi gerekebilir.

### Örnek — Direkt API ödeme (sync_mode=1)

```php
use Done\PayTR\Model\Buyer;
use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Card;
use Done\PayTR\Request\Direct\CreatePaymentRequest;

$buyer = new Buyer(
    email: 'musteri@ornek.com',
    name: 'Ad Soyad',
    phone: '5551234567',
    ip: $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
    addressLine: 'Teslimat adresi'
);

$basket = (new Basket())
    ->addItem(new BasketItem('Ürün 1', '50.00', 1))
    ->addItem(new BasketItem('Ürün 2', '25.50', 2));

$card = new Card('KART SAHİBİ ADI', '4111111111111111', '12', '30', '000');

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

$response = $client->directApi()->createPayment($request);

if ($response->isSuccess()) {
    // Ödeme başarılı
} elseif ($response->isWaitCallback()) {
    // Sonuç callback üzerinden gelecek
}
```

### BIN sorgulama ve taksit oranları

```php
use Done\PayTR\Request\Direct\BinLookupRequest;
use Done\PayTR\Request\Direct\InstallmentRatesRequest;

$binRequest = (new BinLookupRequest())->setBinNumber('411111');
$binResult = $client->directApi()->binLookup($binRequest);
if ($binResult->isSuccess()) {
    $brand = $binResult->brand; // bonus, axess, vb.
}

$taksitRequest = (new InstallmentRatesRequest())->setRequestId('taksit-' . uniqid());
$taksitResult = $client->directApi()->installmentRates($taksitRequest);
if ($taksitResult->isSuccess()) {
    $oranlar = $taksitResult->oranlar;
    $maxTaksit = $taksitResult->maxInstNonBus;
}
```

> sync_mode=0 için: `$request->toPayload($options)` alıp kendi formunuzla
> `$client->directApi()->getPaymentFormUrl()` (varsayılan `https://www.paytr.com/odeme`) adresine POST edin.

---

## 1c) Kart Saklama API (CAPI)

Kart Saklama ile kullanıcıların kartlarını PayTR'de saklayabilir; kayıtlı karttan ödeme alabilir, listeleyebilir, silebilir ve tekrarlayan ödeme yapabilirsiniz.

Erişim:

```php
$client->cardStorage();
```

### Endpoint / token özeti

* **CAPI LIST:** `POST /odeme/capi/list`
  Token: `utoken + merchant_salt`
* **CAPI DELETE:** `POST /odeme/capi/delete`
  Token: `ctoken + utoken + merchant_salt`
* **Yeni kart / kayıtlı karttan ödeme / tekrarlayan ödeme:** `POST /odeme`
  Token formülü Direkt API ile aynıdır:
  `merchant_id + user_ip + merchant_oid + email + payment_amount + payment_type + installment_count + currency + test_mode + non_3d + merchant_salt`

> Tekrarlayan ödemede `recurring_payment` alanı token string'ine dahil edilmez.

---

### a) Yeni kart ekleme (ödeme sırasında) — `store_card=1`

İlk kartta `utoken` göndermeyin; PayTR **callback** üzerinden `utoken/ctoken` döner.
Aynı kullanıcıya yeni kart eklerken mevcut `utoken`'ı gönderin.

```php
use Done\PayTR\Request\CardStorage\AddCardRequest;

$request = (new AddCardRequest())
    ->setMerchantOid('SIP-' . uniqid())
    ->setPaymentAmount('50.00')
    ->setBuyer($buyer)
    ->setBasket($basket)
    ->setCard($card)
    ->setOkUrl('https://siteniz.com/ok')
    ->setFailUrl('https://siteniz.com/fail')
    ->setSyncMode(true);
// ->setUtoken($mevcutUtoken)  // aynı kullanıcıya ek kart için

$response = $client->cardStorage()->addCard($request);
```

> sync_mode=0 için HTTP çağrısı SDK tarafından yapılmaz;
> `toPayload($options)` alıp `$client->cardStorage()->getPaymentFormUrl()` adresine form POST edin.

---

### b) Kayıtlı kart listesi (CAPI LIST)

```php
use Done\PayTR\Request\CardStorage\ListCardsRequest;

$listReq = (new ListCardsRequest())->setUtoken($utoken);
$listResp = $client->cardStorage()->listCards($listReq);

foreach ($listResp->cards as $kart) {
    // $kart->ctoken
    // $kart->last4
    // $kart->requireCvv (1 ise ödeme sırasında CVV iste)
}
```

---

### c) Kayıtlı karttan ödeme

CAPI LIST'ten gelen `require_cvv=1` ise kullanıcıdan CVV alıp gönderin.

```php
use Done\PayTR\Request\CardStorage\PayWithRegisteredCardRequest;

$req = (new PayWithRegisteredCardRequest())
    ->setMerchantOid('SIP-' . uniqid())
    ->setPaymentAmount('75.00')
    ->setBuyer($buyer)
    ->setBasket($basket)
    ->setOkUrl('https://siteniz.com/ok')
    ->setFailUrl('https://siteniz.com/fail')
    ->setUtoken($utoken)
    ->setCtoken($ctoken)
    ->setRequireCvv(true)
    ->setCvv('000')
    ->setSyncMode(true);

$response = $client->cardStorage()->payWithRegisteredCard($req);
```

---

### d) Kart silme (CAPI DELETE)

```php
use Done\PayTR\Request\CardStorage\DeleteCardRequest;

$delReq = (new DeleteCardRequest())
    ->setUtoken($utoken)
    ->setCtoken($ctoken);

$client->cardStorage()->deleteCard($delReq);
```

---

### e) Tekrarlayan ödeme (kayıtlı kart)

Kullanıcı etkileşimi olmadan tahsilat. `non_3d=1`, `recurring_payment=1`.

```php
use Done\PayTR\Request\CardStorage\RecurringPaymentRequest;

$recReq = (new RecurringPaymentRequest())
    ->setMerchantOid('ABO-' . uniqid())
    ->setPaymentAmount('99.00')
    ->setBuyer($buyer)
    ->setBasket($basket)
    ->setOkUrl('https://siteniz.com/ok')
    ->setFailUrl('https://siteniz.com/fail')
    ->setUtoken($utoken)
    ->setCtoken($ctoken);

$recResp = $client->cardStorage()->recurringPayment($recReq);

if ($recResp->isSuccess()) {
    // Tahsilat başarılı
} elseif ($recResp->isWaitCallback()) {
    // Sonuç callback ile netleşecek
} else {
    // failed/error
    // $recResp->tryAgain === true ise, devam eden işlem var; daha sonra tekrar deneyin.
}
```

---

## 2) Bildirim URL (Callback) doğrulama

Iframe + Direkt API + Kart Saklama bildirimleri **aynı doğrulama** ile çalışır.

Zorunlu alanlar:

* `merchant_oid`
* `status`
* `total_amount`
* `hash`

> Yanıt olarak **yalnızca** düz metin `OK` yazdırın.
> Öncesinde/sonrasında HTML veya ekstra çıktı olmamalıdır.

```php
use Done\PayTR\Exceptions\SignatureException;

try {
    $notification = $client->callback()->verify($_POST);
} catch (SignatureException $e) {
    http_response_code(400);
    exit('Geçersiz bildirim.');
}

if ($notification->isSuccess()) {
    // siparisOnayla($notification->merchantOid, $notification->totalAmount);
} else {
    // siparisIptalEt($notification->merchantOid, $notification->failedReasonCode, $notification->failedReasonMsg);
}

echo $client->callback()->ok();
exit;
```

---

## 3) Durum sorgulama

```php
use Done\PayTR\Request\Query\StatusRequest;

$request = (new StatusRequest())->setMerchantOid('SIPARIS-123');
$result = $client->query()->status($request);

// $result->paymentAmount, $result->paymentTotal, $result->currency, $result->paymentDate, $result->returns
```

---

## 4) İade

```php
use Done\PayTR\Request\Refund\RefundRequest;

$request = (new RefundRequest())
    ->setMerchantOid('SIPARIS-123')
    ->setReturnAmount('11.97')
    ->setReferenceNo('IADE-REF-001');

$result = $client->refundCancel()->refund($request);
```

---

## Hata Yönetimi

| İstisna               | Açıklama                                                             |
| --------------------- | -------------------------------------------------------------------- |
| `PayTRException`      | Tüm SDK hatalarının tabanı                                           |
| `ValidationException` | Eksik/geçersiz parametre (örn. amount formatı, eksik callback alanı) |
| `HttpException`       | Bağlantı / zaman aşımı                                               |
| `SignatureException`  | Callback hash uyuşmazlığı                                            |
| `ApiException`        | PayTR `status: failed/error`; `getReason()`, `getPayload()`          |

---

## Geriye Dönük Uyumluluk

Eski DTO tabanlı API çalışmaya devam eder; yeni kod için Request/Model stili önerilir.

* `iframePayment()->getToken(IframeTokenRequest)` → `iframe()->createToken(CreateTokenRequest)`
* `query()->query(QueryRequest)` → `query()->status(StatusRequest)`
* `refundCancel()->refund(RefundRequestDto)` / `refundWith(RefundRequest)` → `refundCancel()->refund(RefundRequest)`
* `UserBasket::encode()` → `Model\Basket` + `BasketItem`

---

## Örnekler

Kopyala-yapıştır çalıştırılabilir senaryolar [`examples/`](examples/) klasöründe:

* [`iframe-payment.php`](examples/iframe-payment.php) — Iframe token üretimi
* [`direct-payment.php`](examples/direct-payment.php) — Direkt API (Non3D, sync)
* [`callback.php`](examples/callback.php) — Bildirim URL endpoint'i
* [`recurring-payment.php`](examples/recurring-payment.php) — Kayıtlı kartla tekrarlayan ödeme

---

## Test

```bash
composer install
composer test
```

Gerçek PayTR çağrısı yapılmaz; testler `FakeHttpClient` ile çalışır.

---

## Katkı

Katkılarınızı bekliyoruz! Lütfen önce [CONTRIBUTING.md](CONTRIBUTING.md) dosyasını okuyun.

## Güvenlik

Güvenlik açığı bildirimleri için lütfen public issue açmayın; [SECURITY.md](SECURITY.md) dosyasındaki süreci izleyin.

## Lisans

MIT. Bkz. `LICENSE`.

---

## Referans

* PayTR Geliştirici Merkezi: [https://dev.paytr.com/](https://dev.paytr.com/)
* Direkt API: [https://dev.paytr.com/direkt-api](https://dev.paytr.com/direkt-api)
* Kart Saklama API: [https://dev.paytr.com/direkt-api/kart-saklama-api](https://dev.paytr.com/direkt-api/kart-saklama-api)
* iFrame API 1. Adım: [https://dev.paytr.com/iframe-api/iframe-api-1-adim](https://dev.paytr.com/iframe-api/iframe-api-1-adim)
* iFrame API 2. Adım (Bildirim URL): [https://dev.paytr.com/iframe-api/iframe-api-2-adim](https://dev.paytr.com/iframe-api/iframe-api-2-adim)
* Durum Sorgu API: [https://dev.paytr.com/durum-sorgu](https://dev.paytr.com/durum-sorgu)
* İade API: [https://dev.paytr.com/iade-api](https://dev.paytr.com/iade-api)
