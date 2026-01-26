<?php

declare(strict_types=1);

namespace Done\PayTR\Exceptions;

/**
 * PayTR API "failed" / "error" döndüğünde reason ve payload ile fırlatılır.
 */
class ApiException extends PayTRException
{
    /** @var array<string, mixed> */
    private array $payload = [];

    /** @var string|null */
    private $reason;

    public function __construct(
        string $message,
        ?string $reason = null,
        ?array $payload = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->reason = $reason;
        if ($payload !== null) {
            $this->payload = $payload;
        }
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
