<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Request\Iframe;

use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Options;
use Done\PayTR\Request\Iframe\CreateTokenRequest;
use PHPUnit\Framework\TestCase;

/**
 * CreateTokenRequest amount conversion ve toPayload testleri.
 */
final class CreateTokenRequestTest extends TestCase
{
    private function validRequest(): CreateTokenRequest
    {
        $basket = (new Basket())->addItem(new BasketItem('Test', '10.00', 1));
        return (new CreateTokenRequest())
            ->setMerchantOid('ORD-1')
            ->setAmountFromTL('34.56')
            ->setCurrency('TL')
            ->setBuyer(new Buyer('a@b.c', 'Ad', '555', '1.2.3.4', 'Adres satırı'))
            ->setBasket($basket)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail');
    }

    public function test_set_amount_from_tl_converts_to_kurus(): void
    {
        $options = (new Options())
            ->setMerchantId('1')
            ->setMerchantKey('k')
            ->setMerchantSalt('s');
        $req = $this->validRequest();
        $payload = $req->toPayload($options);
        self::assertSame(3456, $payload['payment_amount']);
    }

    public function test_set_amount_kurus_used_directly(): void
    {
        $options = (new Options())
            ->setMerchantId('1')
            ->setMerchantKey('k')
            ->setMerchantSalt('s');
        $req = (new CreateTokenRequest())
            ->setMerchantOid('X')
            ->setAmountKurus(999)
            ->setBuyer(new Buyer('x@y.z', addressLine: 'Adres'))
            ->setBasket((new Basket())->addItem(new BasketItem('A', '9.99', 1)))
            ->setOkUrl('https://o')
            ->setFailUrl('https://f');
        $payload = $req->toPayload($options);
        self::assertSame(999, $payload['payment_amount']);
    }

    public function test_to_payload_includes_paytr_token_and_snake_case(): void
    {
        $options = (new Options())
            ->setMerchantId('100')
            ->setMerchantKey('key')
            ->setMerchantSalt('salt');
        $payload = $this->validRequest()->toPayload($options);
        self::assertArrayHasKey('paytr_token', $payload);
        self::assertArrayHasKey('merchant_id', $payload);
        self::assertSame('100', $payload['merchant_id']);
        self::assertArrayHasKey('user_basket', $payload);
    }

    public function test_set_amount_from_tl_rejects_comma_decimal(): void
    {
        $req = new CreateTokenRequest();
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('nokta ile ondalık');
        $req->setAmountFromTL('34,56');
    }

    public function test_to_payload_throws_without_buyer(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = (new CreateTokenRequest())
            ->setMerchantOid('X')
            ->setAmountKurus(100)
            ->setBasket((new Basket())->addItem(new BasketItem('A', '1', 1)))
            ->setOkUrl('https://o')
            ->setFailUrl('https://f');
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('Buyer');
        $req->toPayload($options);
    }

    public function test_to_payload_throws_when_buyer_address_line_missing(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = (new CreateTokenRequest())
            ->setMerchantOid('X')
            ->setAmountKurus(100)
            ->setBuyer(new Buyer('x@y.z', 'Ad', '555', '1.2.3.4'))
            ->setBasket((new Basket())->addItem(new BasketItem('A', '1', 1)))
            ->setOkUrl('https://o')
            ->setFailUrl('https://f');
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('addressLine');
        $req->toPayload($options);
    }
}
