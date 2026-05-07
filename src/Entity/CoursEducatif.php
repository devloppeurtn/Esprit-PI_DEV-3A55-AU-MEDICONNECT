<?php

namespace App\Entity;

use App\Repository\CoursEducatifRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CoursEducatifRepository::class)]
#[ORM\Table(name: 'cours_educatif')]
class CoursEducatif
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $scorePourBadge = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\ManyToOne(targetEntity: CategorieSante::class, inversedBy: 'coursEducatifs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategorieSante $categorieSante = null;

    #[ORM\OneToMany(mappedBy: 'coursEducatif', targetEntity: QuestionQuiz::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $questions;

    #[ORM\ManyToOne(targetEntity: Medecin::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Medecin $medecinValidateur = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->dateCreation = new \DateTime();
        $this->questions = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getScorePourBadge(): int
    {
        return $this->scorePourBadge;
    }

    public function setScorePourBadge(int $scorePourBadge): self
    {
        $this->scorePourBadge = $scorePourBadge;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getCategorieSante(): ?CategorieSante
    {
        return $this->categorieSante;
    }

    public function setCategorieSante(?CategorieSante $categorieSante): self
    {
        $this->categorieSante = $categorieSante;

        return $this;
    }

    /**
     * @return Collection<int, QuestionQuiz>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(QuestionQuiz $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setCoursEducatif($this);
        }

        return $this;
    }

    public function removeQuestion(QuestionQuiz $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getCoursEducatif() === $this) {
                $question->setCoursEducatif(null);
            }
        }

        return $this;
    }

    public function getMedecinValidateur(): ?Medecin
    {
        return $this->medecinValidateur;
    }

    public function setMedecinValidateur(?Medecin $medecinValidateur): self
    {
        $this->medecinValidateur = $medecinValidateur;

        return $this;
    }

    /**
     * Generate quiz questions using AI
     * This method will be implemented with AI service integration
     */
    public function genererQuizIA(): void
    {
        // TODO: Implement AI-based quiz generation
        // This will use an AI service to automatically generate quiz questions
        // based on the course content
    }
}
