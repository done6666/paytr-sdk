<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Response;

/**
 * Durum sorgu API yanıtı.
 */
final class QueryResponse
{
    /** @var list<array<string, mixed>> */
    public array $returns;

    /** @var list<array<string, mixed>> */
    public array $submerchantPayments;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $status,
        public ?string $netTutar = null,
        public ?string $kesintiTutari = null,
        public ?string $paymentAmount = null,
        public ?string $paymentTotal = null,
        public ?string $paymentDate = null,
        public ?string $currency = null,
        public ?string $taksit = null,
        public ?string $kartMarka = null,
        public ?string $maskedPan = null,
        public ?string $odemeTipi = null,
        public ?string $testMode = null,
        ?array $returns = null,
        ?array $submerchantPayments = null,
        public ?string $errNo = null,
        public ?string $errMsg = null,
    ) {
        $this->returns = $returns ?? [];
        $this->submerchantPayments = $submerchantPayments ?? [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $get = fn (string $k) => isset($data[$k]) ? (string) $data[$k] : null;
        $getInt = fn (string $k) => isset($data[$k]) ? (string) $data[$k] : null;
        $returns = isset($data['returns']) && is_array($data['returns']) ? $data['returns'] : [];
        $sub = isset($data['submerchant_payments']) && is_array($data['submerchant_payments']) ? $data['submerchant_payments'] : [];
        return new self(
            status: $get('status') ?? 'error',
            netTutar: $get('net_tutar'),
            kesintiTutari: $get('kesinti_tutari'),
            paymentAmount: $get('payment_amount'),
            paymentTotal: $get('payment_total'),
            paymentDate: $getInt('payment_date'),
            currency: $get('currency'),
            taksit: $get('taksit'),
            kartMarka: $get('kart_marka'),
            maskedPan: $get('masked_pan'),
            odemeTipi: $get('odeme_tipi'),
            testMode: $get('test_mode'),
            returns: $returns,
            submerchantPayments: $sub,
            errNo: $get('err_no'),
            errMsg: $get('err_msg'),
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}
