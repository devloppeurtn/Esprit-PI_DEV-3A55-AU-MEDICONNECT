<?php

namespace App\DTO;

final class ProductPurchaseSummary
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly ?string $image,
        public readonly int $totalQuantity,
        public readonly int $orderCount,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->productId,
            'nom' => $this->productName,
            'image' => $this->image,
            'totalQuantity' => $this->totalQuantity,
            'orderCount' => $this->orderCount,
        ];
    }
}
