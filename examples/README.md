# Örnekler

Kopyala-yapıştır çalıştırılabilir senaryolar. Hiçbiri gerçek mağaza anahtarı içermez; kimlik bilgilerini **ortam değişkenleriyle** verin.

| Dosya | Senaryo |
| --- | --- |
| [`iframe-payment.php`](iframe-payment.php) | Iframe token üretimi + iframe HTML |
| [`direct-payment.php`](direct-payment.php) | Direkt API ödeme (Non3D, `sync_mode=1`) |
| [`callback.php`](callback.php) | Bildirim URL endpoint'i (hash doğrulama + OK yanıtı) |
| [`recurring-payment.php`](recurring-payment.php) | Kayıtlı kartla tekrarlayan ödeme |

## Kurulum

```bash
composer install
```

## Çalıştırma

```bash
export PAYTR_MERCHANT_ID="..."
export PAYTR_MERCHANT_KEY="..."
export PAYTR_MERCHANT_SALT="..."

# Iframe token üret (HTML çıktısı verir)
php examples/iframe-payment.php

# Direkt API ödeme (Non3D yetkisi gerektirir)
php examples/direct-payment.php

# Tekrarlayan ödeme (utoken/ctoken gerekir)
export PAYTR_UTOKEN="..."
export PAYTR_CTOKEN="..."
php examples/recurring-payment.php

# Callback endpoint'i yerelde test etmek için
php -S localhost:8080 examples/callback.php
```

## Güvenlik notları

- Anahtarlarınızı asla dosyaya gömün veya repoya commitlemeyin.
- `callback.php` üretimde HTTPS arkasında ve imza doğrulamasıyla çalışır; doğrulamayı asla atmayın.
- Test modunda (`setTestMode(true)`) PayTR test kartlarını kullanın.
