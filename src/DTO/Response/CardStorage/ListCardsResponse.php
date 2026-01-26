<?php

declare(strict_types=1);

namespace Done\PayTR\DTO\Response\CardStorage;

/**
 * Kart Saklama API — kayıtlı kart listesi yanıtı (CAPI LIST).
 * Başarıda dizi kart öğesi; hata durumunda status=error, err_msg.
 *
 * @param list<ListCardsResponseItem> $cards
 */
final class ListCardsResponse
{
    public function __construct(
        public string $status,
        public string $errMsg = '',
        /** @var list<ListCardsResponseItem> */
        public array $cards = [],
    ) {
    }

    /**
     * @param array<int|string, mixed> $data JSON decode sonucu: nesne (status/err_msg) veya kart dizisi
     */
    public static function fromParsedResponse($data): self
    {
        if (is_array($data) && isset($data['status']) && (string) $data['status'] === 'error') {
            $errMsg = isset($data['err_msg']) ? (string) $data['err_msg'] : '';
            return new self('error', $errMsg, []);
        }

        $cards = [];
        if (is_array($data)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    $cards[] = ListCardsResponseItem::fromArray($item);
                }
            }
        }
        return new self('success', '', $cards);
    }

    public function isSuccess(): bool
    {
        return $this->status !== 'error';
    }

    public function getErrorMessage(): string
    {
        return $this->errMsg;
    }
}
