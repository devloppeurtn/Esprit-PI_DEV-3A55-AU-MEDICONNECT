<?php

namespace App\DTO;

final class OneTimeBuyerSummary
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly ?\DateTimeInterface $lastOrder,
        public readonly ?float $orderValue,
    ) {
    }

    /**
     * @return array<string, int|string|float|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'lastOrder' => $this->lastOrder?->format('Y-m-d H:i:s'),
            'orderValue' => $this->orderValue,
        ];
    }
}
