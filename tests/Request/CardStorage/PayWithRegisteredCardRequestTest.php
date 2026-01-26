<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Request\CardStorage;

use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Options;
use Done\PayTR\Request\CardStorage\PayWithRegisteredCardRequest;
use PHPUnit\Framework\TestCase;

final class PayWithRegisteredCardRequestTest extends TestCase
{
    private function validRequest(): PayWithRegisteredCardRequest
    {
        $basket = (new Basket())->addItem(new BasketItem('Ürün', '75.00', 1));
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        return (new PayWithRegisteredCardRequest())
            ->setMerchantOid('SIP-RC-1')
            ->setPaymentAmount('75.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setUtoken('u_tok')
            ->setCtoken('c_tok')
            ->setRequireCvv(false)
            ->setSyncMode(true);
    }

    public function test_to_payload_includes_utoken_ctoken_require_cvv_no_card_number(): void
    {
        $options = (new Options())->setMerchantId('100')->setMerchantKey('key')->setMerchantSalt('salt');
        $payload = $this->validRequest()->toPayload($options);
        self::assertSame('u_tok', $payload['utoken']);
        self::assertSame('c_tok', $payload['ctoken']);
        self::assertSame('0', $payload['require_cvv']);
        self::assertArrayNotHasKey('cc_owner', $payload);
        self::assertArrayNotHasKey('card_number', $payload);
        self::assertArrayHasKey('paytr_token', $payload);
    }

    public function test_to_payload_includes_cvv_when_require_cvv(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = $this->validRequest()->setRequireCvv(true)->setCvv('123');
        $payload = $req->toPayload($options);
        self::assertSame('1', $payload['require_cvv']);
        self::assertSame('123', $payload['cvv']);
    }

    public function test_to_payload_throws_when_require_cvv_without_cvv(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $req = $this->validRequest()->setRequireCvv(true);
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('cvv');
        $req->toPayload($options);
    }

    public function test_set_payment_amount_rejects_invalid_format(): void
    {
        $req = $this->validRequest();
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('payment_amount');
        $req->setPaymentAmount('50,00');
    }

    public function test_set_installment_count_rejects_invalid_range(): void
    {
        $req = $this->validRequest();
        $this->expectException(\Done\PayTR\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('installment_count');
        $req->setInstallmentCount(13);
    }
}
