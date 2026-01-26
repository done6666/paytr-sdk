<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Request\CardStorage;

use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Options;
use Done\PayTR\Request\CardStorage\RecurringPaymentRequest;
use PHPUnit\Framework\TestCase;

final class RecurringPaymentRequestTest extends TestCase
{
    private function validRequest(): RecurringPaymentRequest
    {
        $basket = (new Basket())->addItem(new BasketItem('Abonelik', '99.00', 1));
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        return (new RecurringPaymentRequest())
            ->setMerchantOid('ABO-1')
            ->setPaymentAmount('99.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setUtoken('u_tok')
            ->setCtoken('c_tok');
    }

    public function test_to_payload_includes_recurring_payment_and_non_3d(): void
    {
        $options = (new Options())->setMerchantId('100')->setMerchantKey('key')->setMerchantSalt('salt');
        $payload = $this->validRequest()->toPayload($options);
        self::assertSame('1', $payload['recurring_payment']);
        self::assertSame('1', $payload['non_3d']);
        self::assertSame('u_tok', $payload['utoken']);
        self::assertSame('c_tok', $payload['ctoken']);
        self::assertArrayHasKey('paytr_token', $payload);
    }

    public function test_to_payload_throws_without_utoken(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = (new RecurringPaymentRequest())
            ->setMerchantOid('X')
            ->setPaymentAmount('10.00')
            ->setBuyer(new Buyer('a@b.c', 'A', '5', '1.2.3.4', 'Adres'))
            ->setBasket((new Basket())->addItem(new BasketItem('X', '10.00', 1)))
            ->setOkUrl('https://o')
            ->setFailUrl('https://f')
            ->setCtoken('ct');
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('utoken');
        $req->toPayload($options);
    }

    public function test_to_payload_throws_without_ctoken(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = (new RecurringPaymentRequest())
            ->setMerchantOid('X')
            ->setPaymentAmount('10.00')
            ->setBuyer(new Buyer('a@b.c', 'A', '5', '1.2.3.4', 'Adres'))
            ->setBasket((new Basket())->addItem(new BasketItem('X', '10.00', 1)))
            ->setOkUrl('https://o')
            ->setFailUrl('https://f')
            ->setUtoken('ut');
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('ctoken');
        $req->toPayload($options);
    }

    public function test_set_payment_amount_rejects_invalid_format(): void
    {
        $req = $this->validRequest();
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('payment_amount');
        $req->setPaymentAmount('99,00');
    }

    public function test_set_installment_count_rejects_invalid_range(): void
    {
        $req = $this->validRequest();
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('installment_count');
        $req->setInstallmentCount(1);
    }
}
