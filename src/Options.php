<?php

declare(strict_types=1);

namespace Done\PayTR;

use Done\PayTR\Exceptions\ValidationException;

/**
 * Fluent SDK yapılandırması. Merchant bilgileri, test modu ve opsiyonel URL/timeout.
 */
final class Options
{
    private const DEFAULT_BASE_URL = 'https://www.paytr.com';

    private string $merchantId = '';
    private string $merchantKey = '';
    private string $merchantSalt = '';
    private string $baseUrl = self::DEFAULT_BASE_URL;
    private bool $testMode = false;
    private int $timeout = 20;
    private string $userAgent = '';

    public function setMerchantId(string $merchantId): self
    {
        $this->merchantId = $merchantId;
        return $this;
    }

    public function setMerchantKey(string $merchantKey): self
    {
        $this->merchantKey = $merchantKey;
        return $this;
    }

    public function setMerchantSalt(string $merchantSalt): self
    {
        $this->merchantSalt = $merchantSalt;
        return $this;
    }

    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    public function setTestMode(bool $testMode): self
    {
        $this->testMode = $testMode;
        return $this;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getMerchantKey(): string
    {
        return $this->merchantKey;
    }

    public function getMerchantSalt(): string
    {
        return $this->merchantSalt;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * İç kullanım: Config örneği üretir (Resources/Client ile uyum).
     */
    public function toConfig(): Config
    {
        if ($this->merchantId === '' || $this->merchantKey === '' || $this->merchantSalt === '') {
            throw new ValidationException('merchant_id, merchant_key ve merchant_salt zorunludur.');
        }
        return new Config($this->merchantId, $this->merchantKey, $this->merchantSalt, $this->isTestMode());
    }
}
