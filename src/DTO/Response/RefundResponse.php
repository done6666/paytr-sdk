<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Response;

/**
 * İade API yanıtı.
 */
final class RefundResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $status,
        public ?string $isTest = null,
        public ?string $merchantOid = null,
        public ?string $returnAmount = null,
        public ?string $referenceNo = null,
        public ?string $errNo = null,
        public ?string $errMsg = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $get = fn (string $k) => isset($data[$k]) ? (string) $data[$k] : null;
        return new self(
            status: $get('status') ?? 'error',
            isTest: $get('is_test'),
            merchantOid: $get('merchant_oid'),
            returnAmount: $get('return_amount'),
            referenceNo: $get('reference_no'),
            errNo: $get('err_no'),
            errMsg: $get('err_msg'),
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}
