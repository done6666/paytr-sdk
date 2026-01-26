<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Request\CardStorage;

use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Model\Card;
use Done\PayTR\Options;
use Done\PayTR\Request\CardStorage\AddCardRequest;
use PHPUnit\Framework\TestCase;

final class AddCardRequestTest extends TestCase
{
    private function validRequest(): AddCardRequest
    {
        $basket = (new Basket())->addItem(new BasketItem('Ürün', '50.00', 1));
        $buyer = new Buyer('a@b.c', 'Ad Soyad', '555', '1.2.3.4', 'Adres');
        $card = new Card('KART SAHİBİ', '4111111111111111', '12', '30', '000');
        return (new AddCardRequest())
            ->setMerchantOid('SIP-ADD-1')
            ->setPaymentAmount('50.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setCard($card)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setSyncMode(true);
    }

    public function test_to_payload_includes_store_card_and_paytr_token(): void
    {
        $options = (new Options())->setMerchantId('100')->setMerchantKey('key')->setMerchantSalt('salt');
        $payload = $this->validRequest()->toPayload($options);
        self::assertSame('1', $payload['store_card']);
        self::assertArrayHasKey('paytr_token', $payload);
        self::assertArrayHasKey('user_basket', $payload);
        self::assertSame('50.00', $payload['payment_amount']);
    }

    public function test_to_payload_includes_utoken_when_set(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = $this->validRequest()->setUtoken('existing_user_tok');
        $payload = $req->toPayload($options);
        self::assertSame('existing_user_tok', $payload['utoken']);
    }

    public function test_to_payload_throws_without_buyer(): void
    {
        $req = (new AddCardRequest())
            ->setMerchantOid('X')
            ->setPaymentAmount('50.00')
            ->setBasket((new Basket())->addItem(new BasketItem('A', '50.00', 1)))
            ->setCard(new Card('O', '4111111111111111', '12', '30', '000'))
            ->setOkUrl('https://o')
            ->setFailUrl('https://f');
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('Buyer');
        $req->toPayload((new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s'));
    }

    public function test_set_payment_amount_rejects_invalid_format(): void
    {
        $req = $this->validRequest();
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('payment_amount');
        $req->setPaymentAmount('100,99');
    }

    public function test_set_installment_count_rejects_invalid_range(): void
    {
        $req = $this->validRequest();
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('installment_count');
        $req->setInstallmentCount(1);
    }
}
