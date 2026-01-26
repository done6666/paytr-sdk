<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Response;

/**
 * Direkt API ödeme yanıtı (sync_mode=1 kullanıldığında).
 * Doküman: status failed | wait_callback | success; msg; utoken/ctoken (kart saklama varsa).
 */
final class DirectPaymentResponse
{
    public function __construct(
        public string $status,
        public string $msg = '',
        public ?string $utoken = null,
        public ?string $ctoken = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $status = isset($data['status']) ? (string) $data['status'] : 'failed';
        $msg = isset($data['msg']) ? (string) $data['msg'] : '';
        $utoken = isset($data['utoken']) && $data['utoken'] !== '' ? (string) $data['utoken'] : null;
        $ctoken = isset($data['ctoken']) && $data['ctoken'] !== '' ? (string) $data['ctoken'] : null;
        return new self($status, $msg, $utoken, $ctoken);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isWaitCallback(): bool
    {
        return $this->status === 'wait_callback';
    }

    /**
     * PayTR status "failed" veya "error" ise true.
     * Kart Saklama ve Direkt API çağrılarında tutarlı ApiException için kullanılır.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed' || $this->status === 'error';
    }
}
