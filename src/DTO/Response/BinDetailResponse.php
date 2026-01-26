<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Response;

/**
 * Direkt API BIN sorgulama yanıtı.
 * Doküman: status success|failed|error; cardType, businessCard, bank, brand, schema, bankCode, allow_non3d; err_msg.
 */
final class BinDetailResponse
{
    public function __construct(
        public string $status,
        public ?string $cardType = null,
        public ?string $businessCard = null,
        public ?string $bank = null,
        public ?string $brand = null,
        public ?string $schema = null,
        public ?string $bankCode = null,
        public ?string $allowNon3d = null,
        public ?string $errMsg = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $str = fn ($k) => isset($data[$k]) && $data[$k] !== null && $data[$k] !== '' ? (string) $data[$k] : null;
        $status = $str('status') ?? 'error';
        return new self(
            $status,
            $str('cardType'),
            $str('businessCard'),
            $str('bank'),
            $str('brand'),
            $str('schema'),
            isset($data['bankCode']) ? (string) $data['bankCode'] : null,
            $str('allow_non3d'),
            $str('err_msg'),
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isError(): bool
    {
        return $this->status === 'error';
    }
}
