# Güvenlik Politikası

Bu SDK ödeme işlemleriyle çalışır; güvenlik raporlarınızı ciddiye alıyoruz.

## Desteklenen Sürümler

| Sürüm  | Destek     |
| ------ | ---------- |
| 1.1.x  | ✅ Aktif   |
| < 1.1  | ❌ Sonu    |

## Güvenlik Açığı Bildirimi

**Lütfen güvenlik açıklarını public issue olarak bildirmeyin.**

GitHub üzerinden gizli güvenlik açığı bildirimi kullanın:

1. Depo sayfasında **Security** sekmesine gidin
2. **Report a vulnerability** bağlantısına tıklayın
3. Açığı mümkün olduğunca detaylı açıklayın (etkilenen sürüm, yeniden üretim adımları, olası etki)

Alternatif olarak [GitHub Private Vulnerability Reporting](https://docs.github.com/en/code-security/security-advisories/guidance-on-reporting-and-writing/privately-reporting-a-security-vulnerability) dokümanındaki adımları izleyebilirsiniz.

Bildirimlerinize en kısa sürede (genellikle 72 saat içinde) yanıt vermeye çalışırız.

## Güvenlik Önerileri (kullanıcılar için)

- `merchant_key` ve `merchant_salt` değerlerini asla repoya commitlemeyin; ortam değişkenlerinde saklayın.
- Callback doğrulamasını atlamayın; her bildirimde hash kontrolü yapın (`SignatureException`).
- Ham kart verisi (PAN/CVV) loglamayın — PCI-DSS yükümlülüklerinizi gözden geçirin.
- HTTPS zorunludur; callback endpoint'inizi imzasız isteklere karşı koruyun.
