# Changelog

Tüm önemli değişiklikler bu dosyada listelenir.

## [1.1.0] - 2026-08-25

### Kart Saklama API (CAPI) tamamlama

- **Resource:** `listCards` → POST /odeme/capi/list; `deleteCard` → POST /odeme/capi/delete; `addCard` / `payWithRegisteredCard` / `recurringPayment` → POST /odeme (store_card=1, utoken/ctoken, require_cvv, recurring_payment=1). `getPaymentFormUrl()` form action adresi döner (sync_mode=0 için).
- **Token:** CAPI LIST `utoken+merchant_salt`; CAPI DELETE `ctoken+utoken+merchant_salt`; POST /odeme Direkt API ile aynı formül, recurring_payment hash’e dahil değil.
- **Request/Response:** AddCardRequest (store_card=1, utoken opsiyonel; kart alanları zorunlu); PayWithRegisteredCardRequest (utoken, ctoken, require_cvv; require_cvv=1 ise cvv zorunlu); RecurringPaymentRequest (non_3d=1, recurring_payment=1); ListCardsResponse/DeleteCardResponse; RecurringPaymentResponse try_again alanı. status=error/failed tüm CAPI çağrılarında ApiException.
- **Doğrulama:** payment_amount nokta ile ondalık; installment_count 0 veya 2–12; eksik zorunlu alan için ValidationException.
- **Test:** SignatureTest (CAPI list/delete + POST/odeme token); request validation (amount, installment, require_cvv); CardStorage FakeHttpClient ile success/error/failed senaryoları.

## [1.0.0] - 2026-01-26

### Eklenen

- Framework bağımsız PayTR PHP SDK.
- **HTTP katmanı:** `Contracts\HttpClient` / `HttpClientResponse`; opsiyonel `Adapters\GuzzleHttpClient`.
- **Kaynaklar:** `IframePayment` (token + iframe), `Callback` (bildirim doğrulama), `Query` (durum sorgu), `RefundCancel` (iade).
- **DTO:** `DTO\Request\*` ve `DTO\Response\*`; camelCase property, PayTR için snake_case `toArray()`.
- **Crypto:** `Crypto\Signature` — iframe token, callback hash doğrulama, durum sorgu token, iade token.
- **İstisnalar:** `PayTRException`, `ValidationException`, `HttpException`, `SignatureException`, `ApiException` (reason + payload).
- **Yardımcı:** `Util\UserBasket::encode()` ile sepet base64 üretimi.
- PHPUnit testleri: imza, request payload, ApiException, callback doğrulama, iframe başarı akışı.
- Türkçe README, örnek kullanım ve idempotency önerisi.
- CI: `composer validate` + `phpunit` (`.github/workflows/ci.yml`).

[1.0.0]: https://github.com/done6666/paytr-sdk/releases/tag/v1.0.0
[1.1.0]: https://github.com/done6666/paytr-sdk/releases/tag/v1.1.0
