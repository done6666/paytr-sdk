<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Response\CardStorage;

/**
 * Kart Saklama API — kayıtlı kart silme yanıtı (CAPI DELETE).
 * Doküman: status success|error, err_msg (hata durumunda).
 */
final class DeleteCardResponse
{
    public function __construct(
        public string $status,
        public string $errMsg = '',
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $status = isset($data['status']) ? (string) $data['status'] : 'error';
        $errMsg = isset($data['err_msg']) ? (string) $data['err_msg'] : '';
        return new self($status, $errMsg);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function getErrorMessage(): string
    {
        return $this->errMsg;
    }
}
