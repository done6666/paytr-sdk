<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Response\CardStorage;

/**
 * CAPI LIST'te dönen tek kart öğesi.
 * Doküman: ctoken, last_4, require_cvv, month, year, c_bank, c_name, c_brand, c_type, businessCard, initial, schema
 */
final class ListCardsResponseItem
{
    public function __construct(
        public string $ctoken,
        public string $last4,
        public string $requireCvv,
        public string $month,
        public string $year,
        public string $cBank,
        public string $cName,
        public string $cBrand,
        public string $cType,
        public string $businessCard,
        public string $initial,
        public string $schema,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $str = fn ($k, $default = '') => isset($data[$k]) && $data[$k] !== null ? (string) $data[$k] : $default;
        return new self(
            $str('ctoken'),
            $str('last_4'),
            $str('require_cvv'),
            $str('month'),
            $str('year'),
            $str('c_bank'),
            $str('c_name'),
            $str('c_brand'),
            $str('c_type'),
            $str('businessCard'),
            $str('initial'),
            $str('schema'),
        );
    }
}
