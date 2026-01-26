<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Response;

/**
 * get-token API yanıtı.
 */
final class IframeTokenResponse
{
    public function __construct(
        public string $status,
        public ?string $token = null,
        public ?string $reason = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $status = isset($data['status']) ? (string) $data['status'] : 'failed';
        $token = isset($data['token']) ? (string) $data['token'] : null;
        $reason = isset($data['reason']) ? (string) $data['reason'] : null;
        return new self($status, $token, $reason);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success' && $this->token !== null;
    }
}
