<?php

declare(strict_types=1);

namespace Done\PayTR\Tests\Integration;

use Done\PayTR\Model\Basket;
use Done\PayTR\Model\BasketItem;
use Done\PayTR\Model\Buyer;
use Done\PayTR\Options;
use Done\PayTR\Request\Iframe\CreateTokenRequest;
use Done\PayTR\Resources\Iframe;
use Done\PayTR\Tests\FakeHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * iframe()->createToken(CreateTokenRequest) akışı (FakeHttpClient ile).
 */
final class IframeCreateTokenTest extends TestCase
{
    public function test_create_token_returns_token(): void
    {
        $token = 'fluent-token-123';
        $fake = new FakeHttpClient();
        $fake->setNextResponse(200, '{"status":"success","token":"' . $token . '"}');

        $options = (new Options())
            ->setMerchantId('100')
            ->setMerchantKey('key')
            ->setMerchantSalt('salt');
        $config = $options->toConfig();
        $iframe = new Iframe($config, $fake, $options);

        $basket = (new Basket())->addItem(new BasketItem('Ürün', '34.56', 1));
        $request = (new CreateTokenRequest())
            ->setMerchantOid('SIP-001')
            ->setAmountFromTL('34.56')
            ->setBuyer(new Buyer('test@example.com', 'Ad Soyad', '555', '127.0.0.1', 'Teslimat adresi'))
            ->setBasket($basket)
            ->setOkUrl('https://example.com/ok')
            ->setFailUrl('https://example.com/fail');

        $response = $iframe->createToken($request);

        self::assertTrue($response->isSuccess());
        self::assertSame($token, $response->token);
        $reqs = $fake->getRequests();
        self::assertCount(1, $reqs);
        self::assertStringContainsString('get-token', $reqs[0]['url']);
        self::assertSame(3456, $reqs[0]['body']['payment_amount']);
    }

    public function test_iframe_url(): void
    {
        self::assertSame(
            'https://www.paytr.com/odeme/guvenli/abc',
            Iframe::iframeUrlForToken('abc')
        );
        $options = (new Options())->setMerchantId('1')->setMerchantKey('k')->setMerchantSalt('s');
        $iframe = new Iframe($options->toConfig(), new FakeHttpClient(), $options);
        self::assertSame('https://www.paytr.com/odeme/guvenli/xyz', $iframe->iframeUrl('xyz'));
    }
}
