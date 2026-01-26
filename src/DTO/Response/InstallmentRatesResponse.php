<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Response;

/**
 * Direkt API taksit oranları sorgulama yanıtı.
 * Doküman: status, request_id, err_msg, max_inst_non_bus, oranlar (kart tipine göre array).
 */
final class InstallmentRatesResponse
{
    /** @var array<string, mixed> */
    public array $oranlar = [];

    public function __construct(
        public string $status,
        public string $requestId = '',
        public ?string $errMsg = null,
        public ?int $maxInstNonBus = null,
        ?array $oranlar = null,
    ) {
        $this->oranlar = $oranlar ?? [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $status = isset($data['status']) ? (string) $data['status'] : 'error';
        $requestId = isset($data['request_id']) ? (string) $data['request_id'] : '';
        $errMsg = isset($data['err_msg']) && $data['err_msg'] !== '' ? (string) $data['err_msg'] : null;
        $maxInst = isset($data['max_inst_non_bus']) ? (int) $data['max_inst_non_bus'] : null;
        $oranlar = isset($data['oranlar']) && is_array($data['oranlar']) ? $data['oranlar'] : [];
        return new self($status, $requestId, $errMsg, $maxInst, $oranlar);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}
