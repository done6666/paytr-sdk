# done6666/paytr-sdk

[![CI](https://github.com/done6666/paytr-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/done6666/paytr-sdk/actions/workflows/ci.yml)
[![Static Analysis](https://github.com/done6666/paytr-sdk/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/done6666/paytr-sdk/actions/workflows/static-analysis.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/done6666/paytr-sdk)](https://packagist.org/packages/done6666/paytr-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/done6666/paytr-sdk)](https://packagist.org/packages/done6666/paytr-sdk)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/done6666/paytr-sdk)](https://packagist.org/packages/done6666/paytr-sdk)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

[**Türkçe**](README.md) | **English**

A framework-agnostic PHP SDK for the PayTR payment platform (Turkey). **PSR-4**, **PSR-12**, **PHP 8.0+**.

* iFrame (Token + iframe URL)
* Direct API (Payment, BIN lookup, installment rates)
* Card Storage API (CAPI) (Add card, pay with registered card, list cards, delete card, recurring payments)
* Callback verification (shared by iFrame + Direct API + CAPI notifications)
* Status inquiry
* Refunds

## Why this SDK?

- **Framework agnostic** — Laravel, Symfony or plain PHP; no framework dependency required.
- **Swappable HTTP layer** — Guzzle adapter included; plug in your own client via the `Contracts\HttpClient` interface.
- **Type-safe and fluent** — Request/Model classes are IDE-autocomplete friendly with built-in validation.
- **No hash/signature headaches** — all PayTR signature formulas live inside the SDK; callback verification in one line.
- **Testable** — run your tests without hitting the real API using `FakeHttpClient`; developed under full CI.
- **Built-in kuruş conversion** — `setAmountFromTL('34.56')` → `3456` kuruş; prevents the most classic integration bug.

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Framework Integration](#framework-integration)
- [Important Notes](#important-notes)
- [Supported Integrations](#supported-integrations)
- [1) Starting an iFrame payment (token)](#1-starting-an-iframe-payment-token)
- [1b) Direct API](#1b-direct-api)
- [1c) Card Storage API (CAPI)](#1c-card-storage-api-capi)
- [2) Notification URL (callback) verification](#2-notification-url-callback-verification)
- [3) Status inquiry](#3-status-inquiry)
- [4) Refund](#4-refund)
- [Error Handling](#error-handling)
- [Backward Compatibility](#backward-compatibility)
- [Examples](#examples)
- [Testing](#testing)
- [Contributing](#contributing)
- [Security](#security)
- [License](#license)

---

## Installation

```bash
composer require done6666/paytr-sdk
```

Optional Guzzle adapter for HTTP requests:

```bash
composer require guzzlehttp/guzzle
```

---

## Quick Start

The recommended setup uses `Options` (`Options` is a fluent builder; `Config` is also available for advanced use).

```php
use Done\PayTR\Options;
use Done\PayTR\Client;
use Done\PayTR\Adapters\GuzzleHttpClient;

$options = (new Options())
    ->setMerchantId('MERCHANT_ID')
    ->setMerchantKey('MERCHANT_KEY')
    ->setMerchantSalt('MERCHANT_SALT')
    ->setTestMode(true);

$client = new Client($options, GuzzleHttpClient::default());
```

To use your own HTTP client, implement the `Done\PayTR\Contracts\HttpClient` interface.

---

## Framework Integration

### Laravel

`config/services.php`:

```php
'paytr' => [
    'merchant_id'  => env('PAYTR_MERCHANT_ID'),
    'merchant_key' => env('PAYTR_MERCHANT_KEY'),
    'merchant_salt'=> env('PAYTR_MERCHANT_SALT'),
],
```

Register as a singleton in `AppServiceProvider@register`:

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

Use it in controllers via method injection:

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

## Important Notes

### 1) user_ip (critical)

In Direct API / Card Storage API calls, `user_ip` must be the **real customer IP address**.
If you are behind a reverse proxy, make sure to read it from the correct header.

### 2) Idempotency (critical)

PayTR notifications (callbacks) may arrive multiple times for the same `merchant_oid`. Process your orders **idempotently**:

* Do not process an order again if it has already been marked "paid/failed".
* Check the order status in your database first.

### 3) sync_mode difference

* **sync_mode=0 (default):** The SDK builds the payload; you POST it to PayTR via your own HTML form.
* **sync_mode=1:** The SDK makes the HTTP call itself (you get a JSON response). Your merchant account needs the relevant permissions.

### 4) Card data security

Never:

* log raw card numbers / CVV,
* store them unnecessarily,

and always use HTTPS. Review your PCI-DSS obligations.

---

## Supported Integrations

* **iFrame API**

  * Token creation (start payment form)
* **Direct API**

  * Payment request
  * BIN lookup
  * Installment rates
* **Card Storage API (CAPI)**

  * Add new card (during payment, `store_card=1`)
  * Pay with a registered card
  * List registered cards (CAPI LIST)
  * Delete registered card (CAPI DELETE)
  * Recurring payment with a registered card (`recurring_payment=1`)
* **Callback verification**

  * iFrame + Direct API + CAPI all work with the same verification
* **Status inquiry**
* **Refunds**

---

## 1) Starting an iFrame payment (token)

Build a token with `Buyer`, `Basket`, `CreateTokenRequest`, then produce the iframe URL.

> PayTR expects `payment_amount` in **kuruş** (1 TL = 100 kuruş).
> In the SDK:
>
> * `setAmountFromTL('34.56')` → SDK sends `3456`
> * or set kuruş directly with `setAmountKurus(3456)`

```php
use Done\PayTR\Model\Buyer;
use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Request\Iframe\CreateTokenRequest;

$buyer = new Buyer(
    email: 'customer@example.com',
    name: 'John Doe',
    phone: '5551234567',
    ip: $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
    addressLine: 'Delivery address'
);

$basket = (new Basket())
    ->addItem(new BasketItem('Product 1', '18.00', 1))
    ->addItem(new BasketItem('Product 2', '33.25', 2));

$request = (new CreateTokenRequest())
    ->setMerchantOid('ORDER-' . uniqid())
    ->setAmountFromTL('34.56')
    ->setCurrency('TL')
    ->setBuyer($buyer)
    ->setBasket($basket)
    ->setOkUrl('https://yoursite.com/payment-success')
    ->setFailUrl('https://yoursite.com/payment-failed')
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

> Order approval/cancellation must be handled via the **notification URL** (callback).
> `merchant_ok_url` / `merchant_fail_url` are only customer redirects.

---

## 1b) Direct API

With the Direct API the payment form lives on your server; card data is sent to PayTR.

### Flows

* **sync_mode=0 (default)**

  * Get the payload with `$request->toPayload($options)`.
  * POST it from your own HTML form to `https://www.paytr.com/odeme`.
  * The result appears on the `merchant_ok_url` / `merchant_fail_url` pages PayTR redirects to.
* **sync_mode=1**

  * The SDK POSTs via `createPayment()`.
  * Returns JSON: `success | wait_callback | failed`
  * Non3D and sync permissions may be required on your merchant account.

### Example — Direct API payment (sync_mode=1)

```php
use Done\PayTR\Model\Buyer;
use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Card;
use Done\PayTR\Request\Direct\CreatePaymentRequest;

$buyer = new Buyer(
    email: 'customer@example.com',
    name: 'John Doe',
    phone: '5551234567',
    ip: $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
    addressLine: 'Delivery address'
);

$basket = (new Basket())
    ->addItem(new BasketItem('Product 1', '50.00', 1))
    ->addItem(new BasketItem('Product 2', '25.50', 2));

$card = new Card('CARDHOLDER NAME', '4111111111111111', '12', '30', '000');

$request = (new CreatePaymentRequest())
    ->setMerchantOid('DIR-' . uniqid())
    ->setPaymentAmount('101.00')
    ->setBuyer($buyer)
    ->setBasket($basket)
    ->setCard($card)
    ->setOkUrl('https://yoursite.com/success')
    ->setFailUrl('https://yoursite.com/fail')
    ->setInstallmentCount(0)
    ->setNon3d(true)
    ->setSyncMode(true);

$response = $client->directApi()->createPayment($request);

if ($response->isSuccess()) {
    // Payment successful
} elseif ($response->isWaitCallback()) {
    // Result will arrive via callback
}
```

### BIN lookup and installment rates

```php
use Done\PayTR\Request\Direct\BinLookupRequest;
use Done\PayTR\Request\Direct\InstallmentRatesRequest;

$binRequest = (new BinLookupRequest())->setBinNumber('411111');
$binResult = $client->directApi()->binLookup($binRequest);
if ($binResult->isSuccess()) {
    $brand = $binResult->brand; // bonus, axess, etc.
}

$taksitRequest = (new InstallmentRatesRequest())->setRequestId('installment-' . uniqid());
$taksitResult = $client->directApi()->installmentRates($taksitRequest);
if ($taksitResult->isSuccess()) {
    $rates = $taksitResult->oranlar;
    $maxInstallment = $taksitResult->maxInstNonBus;
}
```

> For sync_mode=0: get `$request->toPayload($options)` and POST it from your own form to
> `$client->directApi()->getPaymentFormUrl()` (default `https://www.paytr.com/odeme`).

---

## 1c) Card Storage API (CAPI)

Card Storage lets you keep users' cards at PayTR; charge stored cards, list them, delete them and take recurring payments.

Access:

```php
$client->cardStorage();
```

### Endpoint / token summary

* **CAPI LIST:** `POST /odeme/capi/list`
  Token: `utoken + merchant_salt`
* **CAPI DELETE:** `POST /odeme/capi/delete`
  Token: `ctoken + utoken + merchant_salt`
* **New card / pay with stored card / recurring payment:** `POST /odeme`
  Same token formula as the Direct API:
  `merchant_id + user_ip + merchant_oid + email + payment_amount + payment_type + installment_count + currency + test_mode + non_3d + merchant_salt`

> In recurring payments the `recurring_payment` field is not included in the token string.

---

### a) Add a new card (during payment) — `store_card=1`

Do not send `utoken` for the first card; PayTR returns `utoken/ctoken` via **callback**.
When adding another card for the same user, send the existing `utoken`.

```php
use Done\PayTR\Request\CardStorage\AddCardRequest;

$request = (new AddCardRequest())
    ->setMerchantOid('ORD-' . uniqid())
    ->setPaymentAmount('50.00')
    ->setBuyer($buyer)
    ->setBasket($basket)
    ->setCard($card)
    ->setOkUrl('https://yoursite.com/ok')
    ->setFailUrl('https://yoursite.com/fail')
    ->setSyncMode(true);
// ->setUtoken($existingUtoken)  // extra card for the same user

$response = $client->cardStorage()->addCard($request);
```

> For sync_mode=0 the SDK does not make the HTTP call;
> get `toPayload($options)` and form-POST it to `$client->cardStorage()->getPaymentFormUrl()`.

---

### b) List registered cards (CAPI LIST)

```php
use Done\PayTR\Request\CardStorage\ListCardsRequest;

$listReq = (new ListCardsRequest())->setUtoken($utoken);
$listResp = $client->cardStorage()->listCards($listReq);

foreach ($listResp->cards as $card) {
    // $card->ctoken
    // $card->last4
    // $card->requireCvv (if 1, ask for CVV during payment)
}
```

---

### c) Pay with a registered card

If CAPI LIST returned `require_cvv=1`, collect CVV from the user and send it.

```php
use Done\PayTR\Request\CardStorage\PayWithRegisteredCardRequest;

$req = (new PayWithRegisteredCardRequest())
    ->setMerchantOid('ORD-' . uniqid())
    ->setPaymentAmount('75.00')
    ->setBuyer($buyer)
    ->setBasket($basket)
    ->setOkUrl('https://yoursite.com/ok')
    ->setFailUrl('https://yoursite.com/fail')
    ->setUtoken($utoken)
    ->setCtoken($ctoken)
    ->setRequireCvv(true)
    ->setCvv('000')
    ->setSyncMode(true);

$response = $client->cardStorage()->payWithRegisteredCard($req);
```

---

### d) Delete card (CAPI DELETE)

```php
use Done\PayTR\Request\CardStorage\DeleteCardRequest;

$delReq = (new DeleteCardRequest())
    ->setUtoken($utoken)
    ->setCtoken($ctoken);

$client->cardStorage()->deleteCard($delReq);
```

---

### e) Recurring payment (registered card)

Charging without user interaction. `non_3d=1`, `recurring_payment=1`.

```php
use Done\PayTR\Request\CardStorage\RecurringPaymentRequest;

$recReq = (new RecurringPaymentRequest())
    ->setMerchantOid('SUB-' . uniqid())
    ->setPaymentAmount('99.00')
    ->setBuyer($buyer)
    ->setBasket($basket)
    ->setOkUrl('https://yoursite.com/ok')
    ->setFailUrl('https://yoursite.com/fail')
    ->setUtoken($utoken)
    ->setCtoken($ctoken);

$recResp = $client->cardStorage()->recurringPayment($recReq);

if ($recResp->isSuccess()) {
    // Charge successful
} elseif ($recResp->isWaitCallback()) {
    // Result will be finalized via callback
} else {
    // failed/error
    // If $recResp->tryAgain === true, a transaction is in progress; retry later.
}
```

---

## 2) Notification URL (callback) verification

iFrame + Direct API + Card Storage notifications all work with the **same verification**.

Required fields:

* `merchant_oid`
* `status`
* `total_amount`
* `hash`

> Respond with **plain text only**: `OK`.
> There must be no HTML or extra output before/after.

```php
use Done\PayTR\Exceptions\SignatureException;

try {
    $notification = $client->callback()->verify($_POST);
} catch (SignatureException $e) {
    http_response_code(400);
    exit('Invalid notification.');
}

if ($notification->isSuccess()) {
    // approveOrder($notification->merchantOid, $notification->totalAmount);
} else {
    // cancelOrder($notification->merchantOid, $notification->failedReasonCode, $notification->failedReasonMsg);
}

echo $client->callback()->ok();
exit;
```

---

## 3) Status inquiry

```php
use Done\PayTR\Request\Query\StatusRequest;

$request = (new StatusRequest())->setMerchantOid('ORDER-123');
$result = $client->query()->status($request);

// $result->paymentAmount, $result->paymentTotal, $result->currency, $result->paymentDate, $result->returns
```

---

## 4) Refund

```php
use Done\PayTR\Request\Refund\RefundRequest;

$request = (new RefundRequest())
    ->setMerchantOid('ORDER-123')
    ->setReturnAmount('11.97')
    ->setReferenceNo('REFUND-REF-001');

$result = $client->refundCancel()->refund($request);
```

---

## Error Handling

| Exception            | Description                                                              |
| -------------------- | ------------------------------------------------------------------------ |
| `PayTRException`     | Base class of all SDK errors                                             |
| `ValidationException`| Missing/invalid parameter (e.g. amount format, missing callback field)   |
| `HttpException`      | Connection / timeout                                                     |
| `SignatureException` | Callback hash mismatch                                                   |
| `ApiException`       | PayTR `status: failed/error`; `getReason()`, `getPayload()`              |

---

## Backward Compatibility

The legacy DTO-based API keeps working; Request/Model style is recommended for new code.

* `iframePayment()->getToken(IframeTokenRequest)` → `iframe()->createToken(CreateTokenRequest)`
* `query()->query(QueryRequest)` → `query()->status(StatusRequest)`
* `refundCancel()->refund(RefundRequestDto)` / `refundWith(RefundRequest)` → `refundCancel()->refund(RefundRequest)`
* `UserBasket::encode()` → `Model\Basket` + `BasketItem`

---

## Examples

Copy-paste runnable scenarios live in [`examples/`](examples/):

* [`iframe-payment.php`](examples/iframe-payment.php) — iFrame token creation
* [`direct-payment.php`](examples/direct-payment.php) — Direct API (Non3D, sync)
* [`callback.php`](examples/callback.php) — Notification URL endpoint
* [`recurring-payment.php`](examples/recurring-payment.php) — Recurring charge on a stored card

---

## Testing

```bash
composer install
composer test
```

No real PayTR calls are made; tests run against `FakeHttpClient`.

---

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) first.

## Security

Please do not open public issues for security vulnerabilities; follow the process in [SECURITY.md](SECURITY.md).

## License

MIT. See `LICENSE`.

---

## Reference

* PayTR Developer Center: [https://dev.paytr.com/](https://dev.paytr.com/)
* Direct API: [https://dev.paytr.com/direkt-api](https://dev.paytr.com/direkt-api)
* Card Storage API: [https://dev.paytr.com/direkt-api/kart-saklama-api](https://dev.paytr.com/direkt-api/kart-saklama-api)
* iFrame API Step 1: [https://dev.paytr.com/iframe-api/iframe-api-1-adim](https://dev.paytr.com/iframe-api/iframe-api-1-adim)
* iFrame API Step 2 (Notification URL): [https://dev.paytr.com/iframe-api/iframe-api-2-adim](https://dev.paytr.com/iframe-api/iframe-api-2-adim)
* Status Inquiry API: [https://dev.paytr.com/durum-sorgu](https://dev.paytr.com/durum-sorgu)
* Refund API: [https://dev.paytr.com/iade-api](https://dev.paytr.com/iade-api)
