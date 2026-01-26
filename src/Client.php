<?php

declare(strict_types=1);

namespace Done\PayTR;

use Done\PayTR\Contracts\HttpClient;
use Done\PayTR\Resources\Callback;
use Done\PayTR\Resources\CardStorage;
use Done\PayTR\Resources\DirectApi;
use Done\PayTR\Resources\Iframe;
use Done\PayTR\Resources\IframePayment;
use Done\PayTR\Resources\Query;
use Done\PayTR\Resources\RefundCancel;

/**
 * PayTR SDK ana client. Options veya Config ile kurulur; resource'lara erişim sağlar.
 */
final class Client
{
    private Config $config;
    private ?Options $options;
    private HttpClient $httpClient;

    public function __construct(
        Config|Options $configOrOptions,
        HttpClient $httpClient
    ) {
        $this->httpClient = $httpClient;
        $this->config = $configOrOptions instanceof Options ? $configOrOptions->toConfig() : $configOrOptions;
        $this->options = $configOrOptions instanceof Options ? $configOrOptions : null;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getOptions(): ?Options
    {
        return $this->options;
    }

    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /** Iframe token (fluent CreateTokenRequest). Options ile kurulmuş client gerekir. */
    public function iframe(): Iframe
    {
        return new Iframe($this->config, $this->httpClient, $this->options);
    }

    /**
     * Iframe token + ödeme formu (eski DTO ile).
     *
     * @deprecated Yeni kullanım için iframe()->createToken(CreateTokenRequest) tercih edin
     */
    public function iframePayment(): IframePayment
    {
        return new IframePayment($this->config, $this->httpClient);
    }

    /** Bildirim URL callback doğrulama ve parsing */
    public function callback(): Callback
    {
        return new Callback($this->config);
    }

    /** Ödeme durum sorgulama */
    public function query(): Query
    {
        return new Query($this->config, $this->httpClient);
    }

    /** İade işlemi */
    public function refundCancel(): RefundCancel
    {
        return new RefundCancel($this->config, $this->httpClient);
    }

    /** Direkt API: ödeme isteği, BIN sorgulama, taksit oranları */
    public function directApi(): DirectApi
    {
        return new DirectApi($this->config, $this->httpClient, $this->options);
    }

    /** Kart Saklama API: yeni kart ekleme, kayıtlı karttan ödeme, liste, silme, tekrarlayan ödeme */
    public function cardStorage(): CardStorage
    {
        return new CardStorage($this->config, $this->httpClient, $this->options);
    }
}
