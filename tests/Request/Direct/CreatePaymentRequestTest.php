<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Request\Direct;

use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Model\Card;
use Done\PayTR\Options;
use Done\PayTR\Request\Direct\CreatePaymentRequest;
use PHPUnit\Framework\TestCase;

/**
 * CreatePaymentRequest toPayload ve validasyon testleri.
 */
final class CreatePaymentRequestTest extends TestCase
{
    private function validRequest(): CreatePaymentRequest
    {
        $basket = (new Basket())->addItem(new BasketItem('Ürün', '10.00', 1));
        $buyer = new Buyer('a@b.c', 'Ad Soyad', '555', '1.2.3.4', 'Adres');
        $card = new Card('KART SAHİBİ', '4111111111111111', '12', '30', '000');

        return (new CreatePaymentRequest())
            ->setMerchantOid('DIR-001')
            ->setPaymentAmount('100.99')
            ->setCurrency('TL')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setCard($card)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setInstallmentCount(0)
            ->setNon3d(false)
            ->setSyncMode(true);
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
        self::assertSame('100.99', $payload['payment_amount']);
        self::assertSame('card', $payload['payment_type']);
        self::assertSame('1', $payload['sync_mode']);
        self::assertArrayHasKey('user_basket', $payload);
        self::assertArrayHasKey('user_address', $payload);
        self::assertSame('Adres', $payload['user_address']);
    }

    public function test_to_payload_throws_without_buyer(): void
    {
        $req = (new CreatePaymentRequest())
            ->setMerchantOid('X')
            ->setPaymentAmount('50.00')
            ->setBasket((new Basket())->addItem(new BasketItem('A', '1', 1)))
            ->setCard(new Card('X', '4111111111111111', '12', '30', '000'))
            ->setOkUrl('https://o')
            ->setFailUrl('https://f');
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('Buyer');
        $req->toPayload((new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s'));
    }

    public function test_to_payload_throws_without_card(): void
    {
        $basket = (new Basket())->addItem(new BasketItem('A', '1', 1));
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        $req = (new CreatePaymentRequest())
            ->setMerchantOid('X')
            ->setPaymentAmount('50.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setOkUrl('https://o')
            ->setFailUrl('https://f');
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('Card');
        $req->toPayload((new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s'));
    }

    public function test_set_payment_amount_rejects_comma(): void
    {
        $req = new CreatePaymentRequest();
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('nokta ile ondalık');
        $req->setPaymentAmount('34,56');
    }

    public function test_installment_count_valid_range(): void
    {
        $req = new CreatePaymentRequest();
        $req->setInstallmentCount(6);
        $this->expectNotToPerformAssertions();
        $req->setInstallmentCount(0);
    }

    public function test_installment_count_invalid_throws(): void
    {
        $req = new CreatePaymentRequest();
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('installment_count');
        $req->setInstallmentCount(1);
    }
}
