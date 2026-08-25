# Katkı Rehberi

PayTR PHP SDK'ya katkılarınız hoş geldiniz! 🎉

## Geliştirme Ortamı

Gereksinimler: **PHP 8.0+** ve **Composer**

```bash
git clone git@github.com:done6666/paytr-sdk.git
cd paytr-sdk
composer install
composer test
```

Gerçek PayTR çağrısı yapılmadan testler çalışır (`FakeHttpClient` kullanılır).

## Kod Standartları

- **PSR-4** otoload, **PSR-12** kod stili
- Yeni özellikler için mutlaka test yazın
- Public API değişikliklerinde README ve CHANGELOG'u güncelleyin
- Geriye dönük uyumluluğu kırıcı değişikliklerden kaçının (gerekliyse major sürüm + CHANGELOG notu)

## Süreç

1. Bu depoyu fork'layın
2. Feature dalı açın: `git checkout -b feature/harika-ozellik`
3. Değişikliklerinizi yapın ve testleri çalıştırın: `composer test`
4. Anlamlı commit mesajlarıyla commit'leyin
5. Fork'unuzdan bu depoya Pull Request açın

## Pull Request Kuralları

- PR şablonunu doldurun
- Tüm CI kontrollerinin (test + statik analiz) geçtiğinden emin olun
- Tek PR = tek amaç; büyük refactorleri ayrı PR'lara bölün

## Hata Bildirimi

[Hata bildirim şablonunu](.github/ISSUE_TEMPLATE/bug_report.md) kullanarak:

- Kullandığınız SDK ve PHP sürümünü,
- Minimum yeniden üretim kodunu (**asla gerçek mağaza anahtarı/salt paylaşmayın**),
- Beklenen ve gerçekleşen davranışı ekleyin.

## Lisans

Katkılarınız [MIT lisansı](LICENSE) altında yayınlanır.
