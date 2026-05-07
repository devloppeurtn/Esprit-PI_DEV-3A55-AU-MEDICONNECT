<?php

namespace App\DTO;

final class CategoryPurchaseSummary
{
    public function __construct(
        public readonly int $categoryId,
        public readonly string $categoryName,
        public readonly int $purchaseCount,
        public readonly int $totalQuantity,
    ) {
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->categoryId,
            'categoryName' => $this->categoryName,
            'purchaseCount' => $this->purchaseCount,
            'totalQuantity' => $this->totalQuantity,
        ];
    }
}
