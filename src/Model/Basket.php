<?php

declare(strict_types=1);

namespace Done\PayTR\Model;

/**
 * Sepet. Kalem eklenir; PayTR user_basket base64 formatı SDK içinde üretilir.
 */
final class Basket
{
    /** @var list<BasketItem> */
    private array $items = [];

    public function addItem(BasketItem $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * PayTR user_basket değeri: base64(json_encode([[ad, birimFiyat, adet], ...])).
     * Iframe / get-token için.
     */
    public function toEncodedUserBasket(): string
    {
        $rows = [];
        foreach ($this->items as $item) {
            $rows[] = [$item->name, $item->unitPrice, $item->quantity];
        }
        return base64_encode((string) json_encode($rows));
    }

    /**
     * Direkt API user_basket değeri: json_encode([[ad, birimFiyat, adet], ...]).
     * Doküman: "JSON tipinde" (Direkt API 1. Adım).
     */
    public function toDirectApiUserBasket(): string
    {
        $rows = [];
        foreach ($this->items as $item) {
            $rows[] = [$item->name, $item->unitPrice, $item->quantity];
        }
        return (string) json_encode($rows);
    }

    /**
     * @return list<BasketItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
