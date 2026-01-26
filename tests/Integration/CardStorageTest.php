<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Integration;

use Done\PayTR\Config;
use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Model\Card;
use Done\PayTR\Options;
use Done\PayTR\Request\CardStorage\AddCardRequest;
use Done\PayTR\Request\CardStorage\DeleteCardRequest;
use Done\PayTR\Request\CardStorage\ListCardsRequest;
use Done\PayTR\Request\CardStorage\PayWithRegisteredCardRequest;
use Done\PayTR\Request\CardStorage\RecurringPaymentRequest;
use Done\PayTR\Resources\CardStorage;
use Done\PayTR\Tests\FakeHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * CardStorage listCards, deleteCard, addCard, payWithRegisteredCard, recurringPayment — FakeHttpClient ile.
 */
final class CardStorageTest extends TestCase
{
    private function options(): Options
    {
        return (new Options())
            ->setMerchantId('100')
            ->setMerchantKey('key')
            ->setMerchantSalt('salt')
            ->setBaseUrl('https://www.paytr.com');
    }

    public function test_list_cards_success_returns_cards(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '[{"ctoken":"c1","last_4":"1111","require_cvv":"0","month":"12","year":"30","c_bank":"Test","c_name":"Ad","c_brand":"bonus","c_type":"credit","businessCard":"n","initial":"4","schema":"VISA"}]');
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $req = (new ListCardsRequest())->setUtoken('u1');

        $resp = $storage->listCards($req);
        self::assertTrue($resp->isSuccess());
        self::assertCount(1, $resp->cards);
        self::assertSame('c1', $resp->cards[0]->ctoken);
        self::assertSame('1111', $resp->cards[0]->last4);
        self::assertSame('bonus', $resp->cards[0]->cBrand);
        $reqs = $fake->getRequests();
        self::assertCount(1, $reqs);
        self::assertStringContainsString('capi/list', $reqs[0]['url']);
    }

    public function test_list_cards_error_throws_api_exception(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"error","err_msg":"Bağlantı hatası"}');
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $req = (new ListCardsRequest())->setUtoken('u1');

        $this->expectException(\Done\PayTR\Exceptions\ApiException::class);
        $this->expectExceptionMessage('Bağlantı hatası');
        $storage->listCards($req);
    }

    public function test_delete_card_success(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"success"}');
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $req = (new DeleteCardRequest())->setUtoken('u1')->setCtoken('c1');

        $resp = $storage->deleteCard($req);
        self::assertTrue($resp->isSuccess());
        $reqs = $fake->getRequests();
        self::assertStringContainsString('capi/delete', $reqs[0]['url']);
        self::assertSame('c1', $reqs[0]['body']['ctoken']);
    }

    public function test_delete_card_error_throws_api_exception(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"error","err_msg":"Kart yok"}');
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $req = (new DeleteCardRequest())->setUtoken('u1')->setCtoken('c1');

        $this->expectException(\Done\PayTR\Exceptions\ApiException::class);
        $this->expectExceptionMessage('Kart yok');
        $storage->deleteCard($req);
    }

    public function test_get_payment_form_url_returns_odeme_url(): void
    {
        $storage = new CardStorage($this->options()->toConfig(), new FakeHttpClient(), $this->options());
        $url = $storage->getPaymentFormUrl();
        self::assertStringEndsWith('/odeme', $url);
        self::assertStringContainsString('paytr.com', $url);
    }

    /** Config ile kurulunca configToOptions() testMode taşır; addCard payload test_mode Config ile aynı olur. */
    public function test_add_card_with_config_test_mode_true_sends_test_mode_one(): void
    {
        $config = new Config('100', 'key', 'salt', true);
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"success","msg":"OK"}');
        $storage = new CardStorage($config, $fake, null);
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        $basket = (new Basket())->addItem(new BasketItem('Ürün', '50.00', 1));
        $card = new Card('O', '4111111111111111', '12', '30', '000');
        $req = (new AddCardRequest())
            ->setMerchantOid('SIP-CFG')
            ->setPaymentAmount('50.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setCard($card)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setSyncMode(true);

        $storage->addCard($req);

        self::assertSame('1', $fake->getRequests()[0]['body']['test_mode']);
    }

    /** sync_mode=0 ile payWithRegisteredCard çağrıldığında exception mesajında getPaymentFormUrl geçer. */
    public function test_pay_with_registered_card_sync_zero_throws_with_get_payment_form_url_in_message(): void
    {
        $fake = new FakeHttpClient();
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        $basket = (new Basket())->addItem(new BasketItem('Ürün', '75.00', 1));
        $req = (new PayWithRegisteredCardRequest())
            ->setMerchantOid('SIP-RC0')
            ->setPaymentAmount('75.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setUtoken('u1')
            ->setCtoken('c1')
            ->setRequireCvv(false)
            ->setSyncMode(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('getPaymentFormUrl');
        $storage->payWithRegisteredCard($req);
    }

    public function test_add_card_sync_success(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"success","msg":"OK","utoken":null,"ctoken":null}');
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        $basket = (new Basket())->addItem(new BasketItem('Ürün', '50.00', 1));
        $card = new Card('O', '4111111111111111', '12', '30', '000');
        $req = (new AddCardRequest())
            ->setMerchantOid('SIP-1')
            ->setPaymentAmount('50.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setCard($card)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setSyncMode(true);

        $resp = $storage->addCard($req);
        self::assertTrue($resp->isSuccess());
        self::assertSame('1', $fake->getRequests()[0]['body']['store_card']);
    }

    public function test_add_card_status_failed_throws_api_exception(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"failed","msg":"Ödeme reddedildi"}');
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        $basket = (new Basket())->addItem(new BasketItem('Ürün', '50.00', 1));
        $card = new Card('O', '4111111111111111', '12', '30', '000');
        $req = (new AddCardRequest())
            ->setMerchantOid('SIP-F')
            ->setPaymentAmount('50.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setCard($card)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setSyncMode(true);

        $this->expectException(\Done\PayTR\Exceptions\ApiException::class);
        $this->expectExceptionMessage('Ödeme reddedildi');
        $storage->addCard($req);
    }

    public function test_add_card_status_error_throws_api_exception(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"error","msg":"Geçersiz token"}');
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        $basket = (new Basket())->addItem(new BasketItem('Ürün', '50.00', 1));
        $card = new Card('O', '4111111111111111', '12', '30', '000');
        $req = (new AddCardRequest())
            ->setMerchantOid('SIP-E')
            ->setPaymentAmount('50.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setCard($card)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setSyncMode(true);

        $this->expectException(\Done\PayTR\Exceptions\ApiException::class);
        $this->expectExceptionMessage('Geçersiz token');
        $storage->addCard($req);
    }

    public function test_pay_with_registered_card_sync_success(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"wait_callback","msg":"Bekleyin"}');
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        $basket = (new Basket())->addItem(new BasketItem('Ürün', '75.00', 1));
        $req = (new PayWithRegisteredCardRequest())
            ->setMerchantOid('SIP-RC-1')
            ->setPaymentAmount('75.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setUtoken('u1')
            ->setCtoken('c1')
            ->setSyncMode(true);

        $resp = $storage->payWithRegisteredCard($req);
        self::assertTrue($resp->isWaitCallback());
        self::assertSame('u1', $fake->getRequests()[0]['body']['utoken']);
        self::assertSame('c1', $fake->getRequests()[0]['body']['ctoken']);
    }

    public function test_pay_with_registered_card_status_failed_throws_api_exception(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"failed","msg":"Kart reddetti"}');
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        $basket = (new Basket())->addItem(new BasketItem('Ürün', '75.00', 1));
        $req = (new PayWithRegisteredCardRequest())
            ->setMerchantOid('SIP-RCF')
            ->setPaymentAmount('75.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setUtoken('u1')
            ->setCtoken('c1')
            ->setRequireCvv(false)
            ->setSyncMode(true);

        $this->expectException(\Done\PayTR\Exceptions\ApiException::class);
        $this->expectExceptionMessage('Kart reddetti');
        $storage->payWithRegisteredCard($req);
    }

    public function test_pay_with_registered_card_status_error_throws_api_exception(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"error","msg":"Sistem hatası"}');
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        $basket = (new Basket())->addItem(new BasketItem('Ürün', '75.00', 1));
        $req = (new PayWithRegisteredCardRequest())
            ->setMerchantOid('SIP-RCE')
            ->setPaymentAmount('75.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setUtoken('u1')
            ->setCtoken('c1')
            ->setRequireCvv(false)
            ->setSyncMode(true);

        $this->expectException(\Done\PayTR\Exceptions\ApiException::class);
        $this->expectExceptionMessage('Sistem hatası');
        $storage->payWithRegisteredCard($req);
    }

    public function test_recurring_payment_success(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"success","msg":"Ödeme Başarılı."}');
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        $basket = (new Basket())->addItem(new BasketItem('Abonelik', '99.00', 1));
        $req = (new RecurringPaymentRequest())
            ->setMerchantOid('ABO-1')
            ->setPaymentAmount('99.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setUtoken('u1')
            ->setCtoken('c1');

        $resp = $storage->recurringPayment($req);
        self::assertTrue($resp->isSuccess());
        self::assertSame('1', $fake->getRequests()[0]['body']['recurring_payment']);
        self::assertSame('1', $fake->getRequests()[0]['body']['non_3d']);
    }

    public function test_recurring_payment_failed_throws_api_exception(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"failed","msg":"Kart kapatılmış","try_again":false}');
        $storage = new CardStorage($this->options()->toConfig(), $fake, $this->options());
        $buyer = new Buyer('a@b.c', 'Ad', '5', '1.2.3.4', 'Adres');
        $basket = (new Basket())->addItem(new BasketItem('X', '10.00', 1));
        $req = (new RecurringPaymentRequest())
            ->setMerchantOid('ABO-2')
            ->setPaymentAmount('10.00')
            ->setBuyer($buyer)
            ->setBasket($basket)
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setUtoken('u1')
            ->setCtoken('c1');

        $this->expectException(\Done\PayTR\Exceptions\ApiException::class);
        $this->expectExceptionMessage('Kart kapatılmış');
        $storage->recurringPayment($req);
    }

    public function test_recurring_payment_parses_try_again(): void
    {
        $dto = \Done\PayTR\DTO\Response\CardStorage\RecurringPaymentResponse::fromArray([
            'status' => 'failed',
            'msg' => 'Henüz devam eden bir işleminiz bulunmaktadır',
            'try_again' => true,
        ]);
        self::assertTrue($dto->tryAgain);
        self::assertTrue($dto->isFailed());
    }

    public function test_client_card_storage_returns_card_storage(): void
    {
        $options = $this->options();
        $client = new \Done\PayTR\Client($options, new FakeHttpClient());
        $storage = $client->cardStorage();
        self::assertInstanceOf(CardStorage::class, $storage);
    }
}
