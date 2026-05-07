<?php

namespace App\DTO;

final class CustomerAtRiskSummary
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly ?\DateTimeInterface $lastOrder,
        public readonly int $orderCount,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'lastOrder' => $this->lastOrder?->format('Y-m-d H:i:s'),
            'orderCount' => $this->orderCount,
        ];
    }
}
