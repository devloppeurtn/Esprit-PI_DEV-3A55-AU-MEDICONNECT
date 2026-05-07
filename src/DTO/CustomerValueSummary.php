<?php

namespace App\DTO;

final class CustomerValueSummary
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly float $totalSpent,
        public readonly int $orderCount,
    ) {
    }

    /**
     * @return array<string, int|float|string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'totalSpent' => $this->totalSpent,
            'orderCount' => $this->orderCount,
        ];
    }
}
