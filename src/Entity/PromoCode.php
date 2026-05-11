<?php

namespace App\Entity;

use App\Repository\PromoCodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PromoCodeRepository::class)]
#[ORM\Table(name: 'code_promo')]
class PromoCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(name: 'description', length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'type_reduction', length: 30)]
    private string $typeReduction = 'POURCENTAGE';

    #[ORM\Column(name: 'valeur', type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $rate = null; // percent e.g. 20.00

    #[ORM\Column(name: 'date_debut', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startAt = null;

    #[ORM\Column(name: 'date_fin', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endAt = null;

    #[ORM\Column(name: 'utilisation_max', nullable: true)]
    private ?int $usageLimit = null;

    #[ORM\Column(name: 'utilisation_actuelle', options: ['default' => 0])]
    private int $usedCount = 0;

    #[ORM\Column(name: 'montant_minimum', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $montantMinimum = '0.00';

    #[ORM\Column(name: 'actif', options: ['default' => true])]
    private bool $active = true;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->startAt = new \DateTime();
        $this->endAt = new \DateTime('+1 year');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtoupper(trim($code));
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getTypeReduction(): string
    {
        return $this->typeReduction;
    }

    public function setTypeReduction(string $typeReduction): static
    {
        $allowed = ['POURCENTAGE', 'MONTANT_FIXE'];
        $this->typeReduction = in_array($typeReduction, $allowed, true) ? $typeReduction : 'POURCENTAGE';
        return $this;
    }

    public function getRate(): ?string
    {
        return $this->rate;
    }

    public function setRate(string $rate): static
    {
        $this->rate = $rate;
        return $this;
    }

    public function getStartAt(): ?\DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(?\DateTimeInterface $startAt): static
    {
        $this->startAt = $startAt;
        return $this;
    }

    public function getEndAt(): ?\DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeInterface $endAt): static
    {
        $this->endAt = $endAt;
        return $this;
    }

    public function getUsageLimit(): ?int
    {
        return $this->usageLimit;
    }

    public function setUsageLimit(?int $usageLimit): static
    {
        $this->usageLimit = $usageLimit;
        return $this;
    }

    public function getUsedCount(): int
    {
        return $this->usedCount;
    }

    public function setUsedCount(int $usedCount): static
    {
        $this->usedCount = $usedCount;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function getMontantMinimum(): ?string
    {
        return $this->montantMinimum;
    }

    public function setMontantMinimum(?string $montantMinimum): static
    {
        $this->montantMinimum = $montantMinimum ?: '0.00';
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
