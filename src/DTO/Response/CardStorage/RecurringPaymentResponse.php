<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Response\CardStorage;

/**
 * Kart Saklama API — tekrarlayan ödeme yanıtı.
 * Doküman: status failed|wait_callback|success; msg; try_again (opsiyonel).
 */
final class RecurringPaymentResponse
{
    public function __construct(
        public string $status,
        public string $msg = '',
        public bool $tryAgain = false,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $status = isset($data['status']) ? (string) $data['status'] : 'failed';
        $msg = isset($data['msg']) ? (string) $data['msg'] : '';
        $tryAgain = isset($data['try_again']) && $data['try_again'];
        return new self($status, $msg, $tryAgain);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isWaitCallback(): bool
    {
        return $this->status === 'wait_callback';
    }

    /** status "failed" veya "error" ise true; tutarlı ApiException için. */
    public function isFailed(): bool
    {
        return $this->status === 'failed' || $this->status === 'error';
    }

    public function getErrorMessage(): string
    {
        return $this->msg;
    }
}
