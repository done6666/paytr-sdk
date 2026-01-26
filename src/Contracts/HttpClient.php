<?php

declare(strict_types=1);

namespace Done\PayTR\Contracts;

/**
 * HTTP isteklerini gönderen client arayüzü.
 * Gerçek implementasyon (Guzzle, cURL, vb.) kullanıcı tarafından sağlanır.
 */
interface HttpClient
{
    /**
     * Belirtilen URL'ye POST isteği gönderir.
     *
     * @param string $url     Hedef URL
     * @param array  $body    Form/body parametreleri (associative array)
     * @param array  $options Ek seçenekler (Content-Type vb.)
     *
     * @return HttpClientResponse Yanıt
     *
     * @throws \Done\PayTR\Exceptions\HttpException İstek hatası
     */
    public function post(string $url, array $body = [], array $options = []): HttpClientResponse;
}
