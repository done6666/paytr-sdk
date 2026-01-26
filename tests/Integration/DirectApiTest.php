<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Integration;

use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Model\Card;
use Done\PayTR\Options;
use Done\PayTR\Request\Direct\BinLookupRequest;
use Done\PayTR\Request\Direct\CreatePaymentRequest;
use Done\PayTR\Request\Direct\InstallmentRatesRequest;
use Done\PayTR\Resources\DirectApi;
use Done\PayTR\Tests\FakeHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * DirectApi createPayment (sync_mode=1), binLookup, installmentRates — FakeHttpClient ile.
 */
final class DirectApiTest extends TestCase
{
    public function test_create_payment_sync_mode_success_returns_dto(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"success","msg":"Ödeme Başarılı.","utoken":null,"ctoken":null}');
        $options = (new Options())->setMerchantId('100')->setMerchantKey('key')->setMerchantSalt('salt');
        $config = $options->toConfig();
        $direct = new DirectApi($config, $fake, $options);

        $req = (new CreatePaymentRequest())
            ->setMerchantOid('DIR-SYNC-1')
            ->setPaymentAmount('99.99')
            ->setBuyer(new Buyer('t@t.com', 'Ad', '5', '127.0.0.1', 'Adres'))
            ->setBasket((new Basket())->addItem(new BasketItem('X', '99.99', 1)))
            ->setCard(new Card('OWNER', '4111111111111111', '12', '30', '000'))
            ->setOkUrl('https://ok')
            ->setFailUrl('https://fail')
            ->setSyncMode(true);

        $response = $direct->createPayment($req);
        self::assertTrue($response->isSuccess());
        self::assertSame('success', $response->status);
        $reqs = $fake->getRequests();
        self::assertCount(1, $reqs);
        self::assertStringContainsString('/odeme', $reqs[0]['url']);
        self::assertSame('1', $reqs[0]['body']['sync_mode']);
    }

    public function test_create_payment_sync_mode_failed_throws_api_exception(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"failed","msg":"Kart reddedildi."}');
        $options = (new Options())->setMerchantId('100')->setMerchantKey('key')->setMerchantSalt('salt');
        $direct = new DirectApi($options->toConfig(), $fake, $options);

        $req = (new CreatePaymentRequest())
            ->setMerchantOid('DIR-FAIL')
            ->setPaymentAmount('10.00')
            ->setBuyer(new Buyer('a@b.c', 'A', '5', '1.2.3.4', 'Adres'))
            ->setBasket((new Basket())->addItem(new BasketItem('Y', '10.00', 1)))
            ->setCard(new Card('O', '4111111111111111', '12', '30', '000'))
            ->setOkUrl('https://ok')
            ->setFailUrl('https://f')
            ->setSyncMode(true);

        $this->expectException(\Done\PayTR\Exceptions\ApiException::class);
        $this->expectExceptionMessage('Kart reddedildi');
        $direct->createPayment($req);
    }

    public function test_bin_lookup_returns_dto(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"success","cardType":"credit","bank":"Test","brand":"bonus","schema":"VISA"}');
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $direct = new DirectApi($options->toConfig(), $fake, $options);
        $req = (new BinLookupRequest())->setBinNumber('411111');

        $response = $direct->binLookup($req);
        self::assertTrue($response->isSuccess());
        self::assertSame('bonus', $response->brand);
        $reqs = $fake->getRequests();
        self::assertStringContainsString('bin-detail', $reqs[0]['url']);
    }

    public function test_installment_rates_returns_dto(): void
    {
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"success","request_id":"r1","max_inst_non_bus":12,"oranlar":{}}');
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $direct = new DirectApi($options->toConfig(), $fake, $options);
        $req = (new InstallmentRatesRequest())->setRequestId('r1');

        $response = $direct->installmentRates($req);
        self::assertTrue($response->isSuccess());
        self::assertSame('r1', $response->requestId);
    }

    public function test_callback_verify_same_as_iframe(): void
    {
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $client = new \Done\PayTR\Client($options, new FakeHttpClient());
        $payload = $client->callback()->verify([
            'merchant_oid' => 'O1',
            'status' => 'success',
            'total_amount' => '10000',
            'hash' => \Done\PayTR\Crypto\Signature::hmacBase64('k', 'O1' . 's' . 'success' . '10000'),
        ]);
        self::assertTrue($payload->isSuccess());
    }
}
