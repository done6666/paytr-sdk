<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Response;

/**
 * Bildirim URL'ye gelen POST verisinin parse edilmiş hali (camelCase).
 * Doğrulama yapıldıktan sonra kullanılmalıdır.
 */
final class CallbackPayload
{
    public function __construct(
        public string $merchantOid,
        public string $status,
        public string $totalAmount,
        public string $hash,
        public string $paymentType,
        public ?string $currency = null,
        public ?string $paymentAmount = null,
        public ?string $failedReasonCode = null,
        public ?string $failedReasonMsg = null,
        public ?string $testMode = null,
    ) {
    }

    /**
     * PayTR'nin POST ettiği ham veriden oluşturur.
     * Hash doğrulaması bu sınıftan önce Signature::verifyCallbackHash ile yapılmalıdır.
     *
     * @param array<string, string|null> $post
     */
    public static function fromPost(array $post): self
    {
        $str = fn ($k, $default = '') => isset($post[$k]) && $post[$k] !== null && $post[$k] !== '' ? (string) $post[$k] : $default;
        return new self(
            merchantOid: $str('merchant_oid'),
            status: $str('status'),
            totalAmount: $str('total_amount'),
            hash: $str('hash'),
            paymentType: $str('payment_type', 'card'),
            currency: $str('currency') ?: null,
            paymentAmount: $str('payment_amount') ?: null,
            failedReasonCode: $str('failed_reason_code') ?: null,
            failedReasonMsg: $str('failed_reason_msg') ?: null,
            testMode: $str('test_mode') ?: null,
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}
