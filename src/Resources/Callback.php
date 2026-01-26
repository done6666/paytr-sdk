<?php

declare(strict_types=1);

namespace Done\PayTR\Resources;

use Done\PayTR\Config;
use Done\PayTR\Crypto\Signature;
use Done\PayTR\DTO\Response\CallbackPayload;
use Done\PayTR\Exceptions\SignatureException;
use Done\PayTR\Exceptions\ValidationException;

/**
 * Bildirim URL POST doğrulama (merchant_oid, status, total_amount, hash zorunlu).
 */
final class Callback
{
    public function __construct(
        private Config $config
    ) {
    }

    /**
     * @param array<string, string|null> $post $_POST
     *
     * @throws ValidationException Zorunlu alan eksikse
     * @throws SignatureException Hash uyuşmazsa
     */
    public function verify(array $post): CallbackPayload
    {
        return $this->verifyAndParse($post);
    }

    /**
     * @param array<string, string|null> $post $_POST
     *
     * @throws ValidationException Zorunlu alan eksikse (merchant_oid, status, total_amount, hash)
     * @throws SignatureException Hash uyuşmazsa
     *
     * @deprecated verify() kullanın
     */
    public function verifyAndParse(array $post): CallbackPayload
    {
        $merchantOid = isset($post['merchant_oid']) ? (string) $post['merchant_oid'] : '';
        $status = isset($post['status']) ? (string) $post['status'] : '';
        $totalAmount = isset($post['total_amount']) ? (string) $post['total_amount'] : '';
        $hash = isset($post['hash']) ? (string) $post['hash'] : '';

        if ($merchantOid === '' || $status === '' || $totalAmount === '' || $hash === '') {
            throw new ValidationException('Callback için merchant_oid, status, total_amount ve hash zorunludur.');
        }

        Signature::verifyCallbackHash(
            $this->config->merchantKey,
            $this->config->merchantSalt,
            $merchantOid,
            $status,
            $totalAmount,
            $hash
        );

        return CallbackPayload::fromPost($post);
    }

    /**
     * PayTR'ye dönülmesi gereken başarı yanıt metni.
     */
    public function ok(): string
    {
        return 'OK';
    }

    /**
     * @deprecated ok() kullanın
     */
    public static function successResponse(): string
    {
        return 'OK';
    }
}
