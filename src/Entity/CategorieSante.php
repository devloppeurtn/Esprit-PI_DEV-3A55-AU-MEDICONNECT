<?php

namespace App\Entity;

use App\Repository\CategorieSanteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CategorieSanteRepository::class)]
#[ORM\Table(name: 'categorie_sante')]
class CategorieSante
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private ?string $type = null; // Culture Générale ou Spécialité Médicale

    #[ORM\OneToMany(mappedBy: 'categorieSante', targetEntity: CoursEducatif::class, cascade: ['persist', 'remove'])]
    private Collection $coursEducatifs;

    #[ORM\OneToMany(mappedBy: 'categorieSante', targetEntity: ProgressionUtilisateur::class)]
    private Collection $progressions;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->coursEducatifs = new ArrayCollection();
        $this->progressions = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, CoursEducatif>
     */
    public function getCoursEducatifs(): Collection
    {
        return $this->coursEducatifs;
    }

    public function addCoursEducatif(CoursEducatif $coursEducatif): self
    {
        if (!$this->coursEducatifs->contains($coursEducatif)) {
            $this->coursEducatifs->add($coursEducatif);
            $coursEducatif->setCategorieSante($this);
        }

        return $this;
    }

    public function removeCoursEducatif(CoursEducatif $coursEducatif): self
    {
        if ($this->coursEducatifs->removeElement($coursEducatif)) {
            if ($coursEducatif->getCategorieSante() === $this) {
                $coursEducatif->setCategorieSante(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProgressionUtilisateur>
     */
    public function getProgressions(): Collection
    {
        return $this->progressions;
    }

    public function addProgression(ProgressionUtilisateur $progression): self
    {
        if (!$this->progressions->contains($progression)) {
            $this->progressions->add($progression);
            $progression->setCategorieSante($this);
        }

        return $this;
    }

    public function removeProgression(ProgressionUtilisateur $progression): self
    {
        if ($this->progressions->removeElement($progression)) {
            if ($progression->getCategorieSante() === $this) {
                $progression->setCategorieSante(null);
            }
        }

        return $this;
    }
}
