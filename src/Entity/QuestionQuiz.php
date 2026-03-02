<?php

namespace App\Entity;

use App\Enum\StatutQuestion;
use App\Repository\QuestionQuizRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: QuestionQuizRepository::class)]
#[ORM\Table(name: 'question_quiz')]
class QuestionQuiz
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private ?string $enonce = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $optionsReponses = null; // Stored as JSON or delimited string

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $reponseCorrecte = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $explication = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: StatutQuestion::class)]
    private ?StatutQuestion $statut = null;

    #[ORM\ManyToOne(targetEntity: CoursEducatif::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CoursEducatif $coursEducatif = null;

    #[ORM\ManyToOne(targetEntity: Medecin::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Medecin $medecinValidateur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->statut = StatutQuestion::IA_PROPOSE;
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEnonce(): ?string
    {
        return $this->enonce;
    }

    public function setEnonce(string $enonce): self
    {
        $this->enonce = $enonce;

        return $this;
    }

    public function getOptionsReponses(): ?string
    {
        return $this->optionsReponses;
    }

    public function setOptionsReponses(string $optionsReponses): self
    {
        $this->optionsReponses = $optionsReponses;

        return $this;
    }

    /**
     * Get options as array
     */
    public function getOptionsReponsesArray(): array
    {
        if (empty($this->optionsReponses)) {
            return [];
        }

        // Try to decode as JSON first
        $decoded = json_decode($this->optionsReponses, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Otherwise, split by delimiter
        return explode('|', $this->optionsReponses);
    }

    /**
     * Set options from array
     */
    public function setOptionsReponsesArray(array $options): self
    {
        $this->optionsReponses = json_encode($options);

        return $this;
    }

    public function getReponseCorrecte(): ?string
    {
        return $this->reponseCorrecte;
    }

    public function setReponseCorrecte(string $reponseCorrecte): self
    {
        $this->reponseCorrecte = $reponseCorrecte;

        return $this;
    }

    public function getExplication(): ?string
    {
        return $this->explication;
    }

    public function setExplication(?string $explication): self
    {
        $this->explication = $explication;

        return $this;
    }

    public function getStatut(): ?StatutQuestion
    {
        return $this->statut;
    }

    public function setStatut(StatutQuestion $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCoursEducatif(): ?CoursEducatif
    {
        return $this->coursEducatif;
    }

    public function setCoursEducatif(?CoursEducatif $coursEducatif): self
    {
        $this->coursEducatif = $coursEducatif;

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

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Validate the question by a doctor
     */
    public function validerParMedecin(Medecin $medecin): self
    {
        $this->statut = StatutQuestion::VALIDE_MEDECIN;
        $this->medecinValidateur = $medecin;

        return $this;
    }

    /**
     * Check if a given answer is correct
     */
    public function verifierReponse(string $reponse): bool
    {
        return $this->reponseCorrecte === $reponse;
    }
}
